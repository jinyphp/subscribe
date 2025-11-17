<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribePayments;

use App\Http\Controllers\Controller;
use Jiny\Subscribe\Models\subscribePayment;

class ShowController extends Controller
{
    public function __invoke(subscribePayment $payment)
    {
        // 관련 데이터 로드
        $payment->load(['subscribeUser', 'subscribe']);

        // 동일 사용자의 최근 결제 내역 (최대 10개)
        $relatedPayments = subscribePayment::where('user_uuid', $payment->user_uuid)
                                        ->where('id', '!=', $payment->id)
                                        ->with(['subscribe'])
                                        ->orderBy('created_at', 'desc')
                                        ->limit(10)
                                        ->get();

        // 동일 구독의 최근 결제 내역 (최대 10개)
        $subscribePayments = subscribePayment::where('subscribe_id', $payment->subscribe_id)
                                        ->where('id', '!=', $payment->id)
                                        ->with(['subscribeUser'])
                                        ->orderBy('created_at', 'desc')
                                        ->limit(10)
                                        ->get();

        // 환불 가능 여부 체크
        $canRefund = $payment->status === 'completed' && $payment->refundable_amount > 0;

        // 재시도 가능 여부 체크
        $canRetry = $payment->status === 'failed' && $payment->retry_count < 3;

        return view('jiny-subscribe::admin.service_payments.show', compact(
            'payment',
            'relatedPayments',
            'subscribePayments',
            'canRefund',
            'canRetry'
        ));
    }
}
