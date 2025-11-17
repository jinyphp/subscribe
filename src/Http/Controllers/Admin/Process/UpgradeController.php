<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\Process;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribeUser;
use Jiny\Subscribe\Models\subscribePlan;
use Jiny\Subscribe\Models\subscribePayment;
use Jiny\Subscribe\Models\subscribeSubscriptionLog;
use Carbon\Carbon;

class UpgradeController extends Controller
{
    /**
     * 플랜 업그레이드
     */
    public function upgrade(Request $request, $subscribeUserId)
    {
        $request->validate([
            'new_plan_code' => 'required|exists:site_subscribe_plans,plan_code',
            'billing_cycle' => 'nullable|in:monthly,quarterly,yearly,lifetime',
            'payment_method' => 'nullable|string|max:50',
            'prorate_payment' => 'boolean',
            'immediate_upgrade' => 'boolean',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $subscribeUser = subscribeUser::findOrFail($subscribeUserId);
        $currentPlan = subscribePlan::where('plan_name', $subscribeUser->plan_name)
                                 ->where('subscribe_id', $subscribeUser->subscribe_id)
                                 ->first();

        $newPlan = subscribePlan::where('plan_code', $request->new_plan_code)
                             ->where('subscribe_id', $subscribeUser->subscribe_id)
                             ->where('is_active', true)
                             ->first();

        if (!$currentPlan || !$newPlan) {
            return response()->json([
                'success' => false,
                'message' => '플랜 정보를 찾을 수 없습니다.'
            ], 404);
        }

        // 업그레이드 가능 여부 확인
        if (!$newPlan->isUpgradeAvailable($currentPlan->plan_code)) {
            return response()->json([
                'success' => false,
                'message' => '해당 플랜으로의 업그레이드가 허용되지 않습니다.'
            ], 400);
        }

        $billingCycle = $request->billing_cycle ?: $subscribeUser->billing_cycle;

        try {
            \DB::transaction(function () use ($request, $subscribeUser, $currentPlan, $newPlan, $billingCycle, &$payment, &$upgradePrice) {

                // 업그레이드 비용 계산
                $upgradePrice = $this->calculateUpgradePrice($currentPlan, $newPlan, $subscribeUser, $billingCycle, $request->prorate_payment);

                $originalPlanName = $subscribeUser->plan_name;

                // 즉시 업그레이드 또는 다음 결제일에 업그레이드
                if ($request->immediate_upgrade || $newPlan->immediate_upgrade) {
                    // 즉시 업그레이드
                    $this->performImmediateUpgrade($subscribeUser, $newPlan, $billingCycle);
                } else {
                    // 다음 결제일에 업그레이드 예약
                    $subscribeUser->update([
                        'auto_upgrade' => true,
                        'admin_notes' => ($subscribeUser->admin_notes ?: '') . "\n다음 결제일에 '{$newPlan->plan_name}' 플랜으로 업그레이드 예정",
                    ]);
                }

                // 업그레이드 비용이 있는 경우 결제 레코드 생성
                if ($upgradePrice > 0) {
                    $payment = subscribePayment::create([
                        'subscribe_user_id' => $subscribeUser->id,
                        'user_uuid' => $subscribeUser->user_uuid,
                        'subscribe_id' => $subscribeUser->subscribe_id,
                        'order_id' => 'UPG-' . $subscribeUser->id . '-' . time(),
                        'amount' => $upgradePrice,
                        'tax_amount' => 0,
                        'discount_amount' => 0,
                        'final_amount' => $upgradePrice,
                        'currency' => 'KRW',
                        'payment_method' => $request->payment_method ?: 'manual',
                        'payment_provider' => 'manual',
                        'status' => 'completed',
                        'payment_type' => 'upgrade',
                        'billing_cycle' => $billingCycle,
                        'billing_period_start' => now(),
                        'billing_period_end' => $subscribeUser->expires_at,
                        'paid_at' => now(),
                    ]);

                    // 총 결제 금액 업데이트
                    $subscribeUser->increment('total_paid', $upgradePrice);
                }

                // 업그레이드 로그 기록
                subscribeSubscriptionLog::logUpgrade(
                    $subscribeUser->id,
                    $originalPlanName,
                    $newPlan->plan_name,
                    $upgradePrice
                );

                // 관리자 액션 로그
                subscribeSubscriptionLog::logAdminAction(
                    $subscribeUser->id,
                    '플랜 업그레이드',
                    $request->admin_notes ?: "'{$originalPlanName}'에서 '{$newPlan->plan_name}'으로 업그레이드",
                    auth()->id(),
                    auth()->user()->name ?? 'Unknown Admin'
                );
            });

            $message = $request->immediate_upgrade || $newPlan->immediate_upgrade
                ? '플랜이 즉시 업그레이드되었습니다.'
                : '다음 결제일에 플랜이 업그레이드됩니다.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'subscribe_user_id' => $subscribeUser->id,
                    'old_plan' => $currentPlan->plan_name,
                    'new_plan' => $newPlan->plan_name,
                    'upgrade_price' => $upgradePrice,
                    'payment_id' => $payment->id ?? null,
                    'immediate' => $request->immediate_upgrade || $newPlan->immediate_upgrade,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '플랜 업그레이드 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 플랜 다운그레이드
     */
    public function downgrade(Request $request, $subscribeUserId)
    {
        $request->validate([
            'new_plan_code' => 'required|exists:site_subscribe_plans,plan_code',
            'billing_cycle' => 'nullable|in:monthly,quarterly,yearly,lifetime',
            'immediate_downgrade' => 'boolean',
            'refund_amount' => 'nullable|numeric|min:0',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $subscribeUser = subscribeUser::findOrFail($subscribeUserId);
        $currentPlan = subscribePlan::where('plan_name', $subscribeUser->plan_name)
                                 ->where('subscribe_id', $subscribeUser->subscribe_id)
                                 ->first();

        $newPlan = subscribePlan::where('plan_code', $request->new_plan_code)
                             ->where('subscribe_id', $subscribeUser->subscribe_id)
                             ->where('is_active', true)
                             ->first();

        if (!$currentPlan || !$newPlan) {
            return response()->json([
                'success' => false,
                'message' => '플랜 정보를 찾을 수 없습니다.'
            ], 404);
        }

        // 다운그레이드 가능 여부 확인
        if (!$newPlan->isDowngradeAvailable($currentPlan->plan_code)) {
            return response()->json([
                'success' => false,
                'message' => '해당 플랜으로의 다운그레이드가 허용되지 않습니다.'
            ], 400);
        }

        $billingCycle = $request->billing_cycle ?: $subscribeUser->billing_cycle;

        try {
            \DB::transaction(function () use ($request, $subscribeUser, $currentPlan, $newPlan, $billingCycle, &$refundAmount) {

                $originalPlanName = $subscribeUser->plan_name;

                // 환불 금액 계산
                $refundAmount = $request->refund_amount;
                if (!$refundAmount && $request->immediate_downgrade) {
                    $remainingDays = now()->diffInDays($subscribeUser->expires_at);
                    $refundAmount = $newPlan->getDowngradeRefund($currentPlan, $billingCycle, $remainingDays);
                }

                // 즉시 다운그레이드 또는 다음 결제일에 다운그레이드
                if ($request->immediate_downgrade || $newPlan->immediate_downgrade) {
                    // 즉시 다운그레이드
                    $this->performImmediateDowngrade($subscribeUser, $newPlan, $billingCycle);
                } else {
                    // 다음 결제일에 다운그레이드 예약
                    $subscribeUser->update([
                        'admin_notes' => ($subscribeUser->admin_notes ?: '') . "\n다음 결제일에 '{$newPlan->plan_name}' 플랜으로 다운그레이드 예정",
                    ]);
                }

                // 환불 처리
                if ($refundAmount > 0) {
                    $subscribeUser->increment('refund_amount', $refundAmount);
                    $subscribeUser->update(['refunded_at' => now()]);

                    // 환불 로그 기록
                    subscribeSubscriptionLog::logRefund(
                        $subscribeUser->id,
                        $refundAmount,
                        '다운그레이드로 인한 환불'
                    );
                }

                // 다운그레이드 로그 기록
                subscribeSubscriptionLog::logDowngrade(
                    $subscribeUser->id,
                    $originalPlanName,
                    $newPlan->plan_name,
                    $refundAmount
                );

                // 관리자 액션 로그
                subscribeSubscriptionLog::logAdminAction(
                    $subscribeUser->id,
                    '플랜 다운그레이드',
                    $request->admin_notes ?: "'{$originalPlanName}'에서 '{$newPlan->plan_name}'으로 다운그레이드",
                    auth()->id(),
                    auth()->user()->name ?? 'Unknown Admin'
                );
            });

            $message = $request->immediate_downgrade || $newPlan->immediate_downgrade
                ? '플랜이 즉시 다운그레이드되었습니다.'
                : '다음 결제일에 플랜이 다운그레이드됩니다.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'subscribe_user_id' => $subscribeUser->id,
                    'old_plan' => $currentPlan->plan_name,
                    'new_plan' => $newPlan->plan_name,
                    'refund_amount' => $refundAmount,
                    'immediate' => $request->immediate_downgrade || $newPlan->immediate_downgrade,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '플랜 다운그레이드 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 업그레이드 비용 계산
     */
    private function calculateUpgradePrice(subscribePlan $currentPlan, subscribePlan $newPlan, subscribeUser $subscribeUser, string $billingCycle, bool $prorate): float
    {
        $currentPrice = $currentPlan->calculatePrice($billingCycle);
        $newPrice = $newPlan->calculatePrice($billingCycle);
        $priceDifference = $newPrice - $currentPrice;

        if (!$prorate || $priceDifference <= 0) {
            return max(0, $priceDifference);
        }

        // 비례 계산: 남은 기간에 대한 차액만 청구
        $remainingDays = now()->diffInDays($subscribeUser->expires_at);
        $totalDays = match ($billingCycle) {
            'monthly' => 30,
            'quarterly' => 90,
            'yearly' => 365,
            default => 30,
        };

        return ($priceDifference * $remainingDays) / $totalDays;
    }

    /**
     * 즉시 업그레이드 실행
     */
    private function performImmediateUpgrade(subscribeUser $subscribeUser, subscribePlan $newPlan, string $billingCycle): void
    {
        $subscribeUser->update([
            'plan_name' => $newPlan->plan_name,
            'plan_price' => $newPlan->calculatePrice($billingCycle),
            'plan_features' => $newPlan->features,
            'monthly_price' => $newPlan->monthly_price,
            'billing_cycle' => $billingCycle,
            'auto_upgrade' => false,
        ]);
    }

    /**
     * 즉시 다운그레이드 실행
     */
    private function performImmediateDowngrade(subscribeUser $subscribeUser, subscribePlan $newPlan, string $billingCycle): void
    {
        $subscribeUser->update([
            'plan_name' => $newPlan->plan_name,
            'plan_price' => $newPlan->calculatePrice($billingCycle),
            'plan_features' => $newPlan->features,
            'monthly_price' => $newPlan->monthly_price,
            'billing_cycle' => $billingCycle,
        ]);
    }
}
