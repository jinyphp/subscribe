<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\Process;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribeUser;
use Jiny\Subscribe\Models\subscribePayment;
use Jiny\Subscribe\Models\subscribeSubscriptionLog;

class RefundController extends Controller
{
    /**
     * 환불 처리
     */
    public function processRefund(Request $request, $subscribeUserId)
    {
        $request->validate([
            'refund_type' => 'required|in:full,partial,payment_specific',
            'refund_amount' => 'required_if:refund_type,partial|numeric|min:0',
            'payment_id' => 'required_if:refund_type,payment_specific|exists:site_subscribe_payments,id',
            'refund_reason' => 'required|string|max:500',
            'refund_method' => 'nullable|string|max:50',
            'transaction_id' => 'nullable|string|max:255',
            'cancel_subscription' => 'boolean',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $subscribeUser = subscribeUser::with('payments')->findOrFail($subscribeUserId);

        // 환불 가능 금액 확인
        $maxRefundableAmount = $subscribeUser->total_paid - $subscribeUser->refund_amount;
        if ($maxRefundableAmount <= 0) {
            return response()->json([
                'success' => false,
                'message' => '환불 가능한 금액이 없습니다.'
            ], 400);
        }

        try {
            \DB::transaction(function () use ($request, $subscribeUser, $maxRefundableAmount, &$refundAmount, &$targetPayment) {

                // 환불 금액 계산
                $refundAmount = $this->calculateRefundAmount($request, $subscribeUser, $maxRefundableAmount);

                if ($refundAmount > $maxRefundableAmount) {
                    throw new \Exception('환불 금액이 환불 가능한 금액을 초과합니다.');
                }

                // 특정 결제에 대한 환불인 경우
                if ($request->refund_type === 'payment_specific') {
                    $targetPayment = subscribePayment::findOrFail($request->payment_id);

                    if ($targetPayment->subscribe_user_id !== $subscribeUser->id) {
                        throw new \Exception('해당 결제는 이 구독 사용자의 것이 아닙니다.');
                    }

                    // 결제 환불 처리
                    $targetPayment->processRefund(
                        $refundAmount,
                        $request->refund_reason,
                        $request->transaction_id
                    );
                }

                // 구독 사용자 환불 정보 업데이트
                $subscribeUser->increment('refund_amount', $refundAmount);
                $subscribeUser->update(['refunded_at' => now()]);

                // 구독 취소 (옵션)
                if ($request->cancel_subscription) {
                    $subscribeUser->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancel_reason' => '환불로 인한 구독 취소',
                        'auto_renewal' => false,
                    ]);
                }

                // 환불 로그 기록
                subscribeSubscriptionLog::logRefund(
                    $subscribeUser->id,
                    $refundAmount,
                    $request->refund_reason
                );

                // 관리자 액션 로그
                subscribeSubscriptionLog::logAdminAction(
                    $subscribeUser->id,
                    '환불 처리',
                    $request->admin_notes ?: "환불 금액: {$refundAmount}원, 사유: {$request->refund_reason}",
                    auth()->id(),
                    auth()->user()->name ?? 'Unknown Admin'
                );
            });

            return response()->json([
                'success' => true,
                'message' => '환불이 성공적으로 처리되었습니다.',
                'data' => [
                    'subscribe_user_id' => $subscribeUser->id,
                    'refund_amount' => $refundAmount,
                    'total_refunded' => $subscribeUser->refund_amount,
                    'payment_id' => $targetPayment->id ?? null,
                    'subscription_cancelled' => $request->cancel_subscription,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '환불 처리 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 환불 내역 조회
     */
    public function getRefundHistory(Request $request, $subscribeUserId)
    {
        $subscribeUser = subscribeUser::findOrFail($subscribeUserId);

        // 환불된 결제 내역
        $refundedPayments = $subscribeUser->payments()
                                      ->refunded()
                                      ->orderBy('refunded_at', 'desc')
                                      ->get();

        // 환불 관련 로그
        $refundLogs = $subscribeUser->subscriptionLogs()
                                 ->whereIn('action', ['refund', 'admin_action'])
                                 ->where(function($query) {
                                     $query->where('action', 'refund')
                                           ->orWhere('action_title', 'like', '%환불%');
                                 })
                                 ->orderBy('created_at', 'desc')
                                 ->get();

        // 환불 통계
        $refundStats = [
            'total_refunded' => $subscribeUser->refund_amount,
            'total_paid' => $subscribeUser->total_paid,
            'refund_ratio' => $subscribeUser->total_paid > 0
                ? round(($subscribeUser->refund_amount / $subscribeUser->total_paid) * 100, 2)
                : 0,
            'refund_count' => $refundedPayments->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'subscribe_user' => $subscribeUser,
                'refunded_payments' => $refundedPayments,
                'refund_logs' => $refundLogs,
                'refund_stats' => $refundStats,
            ]
        ]);
    }

    /**
     * 환불 취소 (환불 철회)
     */
    public function cancelRefund(Request $request, $subscribeUserId, $paymentId)
    {
        $request->validate([
            'cancel_reason' => 'required|string|max:500',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $subscribeUser = subscribeUser::findOrFail($subscribeUserId);
        $payment = subscribePayment::where('id', $paymentId)
                                ->where('subscribe_user_id', $subscribeUserId)
                                ->refunded()
                                ->firstOrFail();

        try {
            \DB::transaction(function () use ($request, $subscribeUser, $payment) {
                $refundAmount = $payment->refunded_amount;

                // 결제 상태를 다시 완료로 변경
                $payment->update([
                    'status' => 'completed',
                    'refunded_amount' => 0,
                    'refunded_at' => null,
                    'refund_reason' => null,
                    'refund_transaction_id' => null,
                ]);

                // 구독 사용자 환불 금액 차감
                $subscribeUser->decrement('refund_amount', $refundAmount);

                // 환불 취소 로그 기록
                subscribeSubscriptionLog::logAdminAction(
                    $subscribeUser->id,
                    '환불 취소',
                    $request->admin_notes ?: "환불 취소: {$refundAmount}원, 사유: {$request->cancel_reason}",
                    auth()->id(),
                    auth()->user()->name ?? 'Unknown Admin'
                );
            });

            return response()->json([
                'success' => true,
                'message' => '환불이 성공적으로 취소되었습니다.',
                'data' => [
                    'subscribe_user_id' => $subscribeUser->id,
                    'payment_id' => $payment->id,
                    'cancelled_refund_amount' => $payment->refunded_amount,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '환불 취소 중 오류가 발생했습니다: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 환불 가능 금액 계산
     */
    public function getRefundableAmount(Request $request, $subscribeUserId)
    {
        $subscribeUser = subscribeUser::with('payments')->findOrFail($subscribeUserId);

        $refundablePayments = $subscribeUser->payments()
                                        ->completed()
                                        ->where('refunded_amount', '<', \DB::raw('final_amount'))
                                        ->get()
                                        ->map(function ($payment) {
                                            return [
                                                'payment_id' => $payment->id,
                                                'order_id' => $payment->order_id,
                                                'amount' => $payment->final_amount,
                                                'refunded_amount' => $payment->refunded_amount,
                                                'refundable_amount' => $payment->refundable_amount,
                                                'payment_date' => $payment->paid_at,
                                                'payment_type' => $payment->payment_type,
                                            ];
                                        });

        $totalRefundable = $subscribeUser->total_paid - $subscribeUser->refund_amount;

        return response()->json([
            'success' => true,
            'data' => [
                'total_paid' => $subscribeUser->total_paid,
                'total_refunded' => $subscribeUser->refund_amount,
                'total_refundable' => $totalRefundable,
                'refundable_payments' => $refundablePayments,
            ]
        ]);
    }

    /**
     * 환불 금액 계산
     */
    private function calculateRefundAmount(Request $request, subscribeUser $subscribeUser, float $maxRefundableAmount): float
    {
        return match ($request->refund_type) {
            'full' => $maxRefundableAmount,
            'partial' => min($request->refund_amount, $maxRefundableAmount),
            'payment_specific' => $this->calculatePaymentSpecificRefund($request, $subscribeUser),
        };
    }

    /**
     * 특정 결제에 대한 환불 금액 계산
     */
    private function calculatePaymentSpecificRefund(Request $request, subscribeUser $subscribeUser): float
    {
        $payment = subscribePayment::findOrFail($request->payment_id);

        if ($request->refund_amount) {
            return min($request->refund_amount, $payment->refundable_amount);
        }

        return $payment->refundable_amount;
    }
}
