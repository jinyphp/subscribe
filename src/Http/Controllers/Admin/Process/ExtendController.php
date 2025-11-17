<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\Process;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribeUser;
use Jiny\Subscribe\Models\subscribePayment;
use Jiny\Subscribe\Models\subscribeSubscriptionLog;
use Carbon\Carbon;

class ExtendController extends Controller
{
    /**
     * 구독 기간 연장
     */
    public function extend(Request $request, $subscribeUserId)
    {
        $request->validate([
            'extend_type' => 'required|in:days,billing_cycle,custom',
            'extend_days' => 'required_if:extend_type,days|integer|min:1|max:3650',
            'extend_cycles' => 'required_if:extend_type,billing_cycle|integer|min:1|max:12',
            'custom_expires_at' => 'required_if:extend_type,custom|date|after:now',
            'charge_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'extend_reason' => 'nullable|string|max:500',
            'admin_notes' => 'nullable|string|max:1000',
            'create_payment' => 'boolean',
        ]);

        $subscribeUser = subscribeUser::findOrFail($subscribeUserId);

        try {
            \DB::transaction(function () use ($request, $subscribeUser, &$payment) {
                $originalExpiresAt = $subscribeUser->expires_at;

                // 새로운 만료일 계산
                $newExpiresAt = $this->calculateNewExpiresAt($request, $subscribeUser);

                // 다음 결제일 계산
                $newNextBillingAt = null;
                if ($subscribeUser->billing_cycle !== 'lifetime' && $subscribeUser->auto_renewal) {
                    $newNextBillingAt = $newExpiresAt;
                }

                // 구독 사용자 정보 업데이트
                $subscribeUser->update([
                    'expires_at' => $newExpiresAt,
                    'next_billing_at' => $newNextBillingAt,
                    'status' => 'active', // 만료된 구독도 연장하면 활성화
                ]);

                // 결제 레코드 생성 (옵션)
                if ($request->create_payment && $request->charge_amount > 0) {
                    $payment = subscribePayment::create([
                        'subscribe_user_id' => $subscribeUser->id,
                        'user_uuid' => $subscribeUser->user_uuid,
                        'subscribe_id' => $subscribeUser->subscribe_id,
                        'order_id' => 'EXT-' . $subscribeUser->id . '-' . time(),
                        'amount' => $request->charge_amount,
                        'tax_amount' => 0,
                        'discount_amount' => 0,
                        'final_amount' => $request->charge_amount,
                        'currency' => 'KRW',
                        'payment_method' => $request->payment_method ?: 'manual',
                        'payment_provider' => 'manual',
                        'status' => 'completed',
                        'payment_type' => 'extension',
                        'billing_cycle' => $subscribeUser->billing_cycle,
                        'billing_period_start' => $originalExpiresAt,
                        'billing_period_end' => $newExpiresAt,
                        'paid_at' => now(),
                    ]);

                    // 결제 완료 시 총 결제 금액 업데이트
                    $subscribeUser->increment('total_paid', $request->charge_amount);
                }

                // 연장 로그 기록
                $extensionDays = $originalExpiresAt->diffInDays($newExpiresAt);
                subscribeSubscriptionLog::logAdminAction(
                    $subscribeUser->id,
                    '구독 기간 연장',
                    $request->extend_reason ?: "구독이 {$extensionDays}일 연장되었습니다.",
                    auth()->id(),
                    auth()->user()->name ?? 'Unknown Admin'
                );

                // 갱신 로그 (결제가 있는 경우)
                if ($payment) {
                    subscribeSubscriptionLog::logRenew(
                        $subscribeUser->id,
                        $request->charge_amount,
                        $newExpiresAt
                    );
                }
            });

            $extensionDays = $subscribeUser->expires_at->diffInDays(Carbon::parse($subscribeUser->getOriginal('expires_at')));

            return response()->json([
                'success' => true,
                'message' => "구독이 {$extensionDays}일 연장되었습니다.",
                'data' => [
                    'subscribe_user_id' => $subscribeUser->id,
                    'old_expires_at' => $subscribeUser->getOriginal('expires_at'),
                    'new_expires_at' => $subscribeUser->expires_at,
                    'extension_days' => $extensionDays,
                    'payment_id' => $payment->id ?? null,
                    'charge_amount' => $request->charge_amount ?? 0,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '구독 연장 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 구독 갱신 (자동/수동)
     */
    public function renew(Request $request, $subscribeUserId)
    {
        $request->validate([
            'payment_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'transaction_id' => 'nullable|string|max:255',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $subscribeUser = subscribeUser::findOrFail($subscribeUserId);

        try {
            \DB::transaction(function () use ($request, $subscribeUser, &$payment) {
                $originalExpiresAt = $subscribeUser->expires_at;

                // 새로운 만료일 계산 (현재 만료일 기준으로 결제 주기만큼 연장)
                $newExpiresAt = match ($subscribeUser->billing_cycle) {
                    'monthly' => $originalExpiresAt->copy()->addMonth(),
                    'quarterly' => $originalExpiresAt->copy()->addMonths(3),
                    'yearly' => $originalExpiresAt->copy()->addYear(),
                    default => $originalExpiresAt->copy()->addMonth(),
                };

                // 다음 결제일 설정
                $newNextBillingAt = $subscribeUser->billing_cycle !== 'lifetime' && $subscribeUser->auto_renewal
                    ? $newExpiresAt : null;

                // 구독 사용자 정보 업데이트
                $subscribeUser->update([
                    'expires_at' => $newExpiresAt,
                    'next_billing_at' => $newNextBillingAt,
                    'status' => 'active',
                    'payment_status' => 'completed',
                ]);

                // 결제 레코드 생성
                $payment = subscribePayment::create([
                    'subscribe_user_id' => $subscribeUser->id,
                    'user_uuid' => $subscribeUser->user_uuid,
                    'subscribe_id' => $subscribeUser->subscribe_id,
                    'transaction_id' => $request->transaction_id,
                    'order_id' => 'REN-' . $subscribeUser->id . '-' . time(),
                    'amount' => $request->payment_amount,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'final_amount' => $request->payment_amount,
                    'currency' => 'KRW',
                    'payment_method' => $request->payment_method,
                    'payment_provider' => 'manual',
                    'status' => 'completed',
                    'payment_type' => 'renewal',
                    'billing_cycle' => $subscribeUser->billing_cycle,
                    'billing_period_start' => $originalExpiresAt,
                    'billing_period_end' => $newExpiresAt,
                    'paid_at' => now(),
                ]);

                // 총 결제 금액 업데이트
                $subscribeUser->increment('total_paid', $request->payment_amount);

                // 갱신 로그 기록
                subscribeSubscriptionLog::logRenew(
                    $subscribeUser->id,
                    $request->payment_amount,
                    $newExpiresAt
                );

                // 관리자 액션 로그
                subscribeSubscriptionLog::logAdminAction(
                    $subscribeUser->id,
                    '구독 갱신',
                    $request->admin_notes ?: '관리자가 구독을 갱신했습니다.',
                    auth()->id(),
                    auth()->user()->name ?? 'Unknown Admin'
                );
            });

            return response()->json([
                'success' => true,
                'message' => '구독이 성공적으로 갱신되었습니다.',
                'data' => [
                    'subscribe_user_id' => $subscribeUser->id,
                    'old_expires_at' => $subscribeUser->getOriginal('expires_at'),
                    'new_expires_at' => $subscribeUser->expires_at,
                    'payment_id' => $payment->id,
                    'payment_amount' => $request->payment_amount,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '구독 갱신 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 새로운 만료일 계산
     */
    private function calculateNewExpiresAt(Request $request, subscribeUser $subscribeUser): Carbon
    {
        return match ($request->extend_type) {
            'days' => $subscribeUser->expires_at->copy()->addDays($request->extend_days),
            'billing_cycle' => $this->extendByCycles($subscribeUser, $request->extend_cycles),
            'custom' => Carbon::parse($request->custom_expires_at),
        };
    }

    /**
     * 결제 주기 기준으로 연장
     */
    private function extendByCycles(subscribeUser $subscribeUser, int $cycles): Carbon
    {
        $currentExpires = $subscribeUser->expires_at->copy();

        return match ($subscribeUser->billing_cycle) {
            'monthly' => $currentExpires->addMonths($cycles),
            'quarterly' => $currentExpires->addMonths($cycles * 3),
            'yearly' => $currentExpires->addYears($cycles),
            default => $currentExpires->addMonths($cycles),
        };
    }
}
