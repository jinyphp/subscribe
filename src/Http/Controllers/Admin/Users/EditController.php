<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribeUser;
use Jiny\Subscribe\Models\Sitesubscribe;
use Jiny\Subscribe\Models\subscribePlan;

class EditController extends Controller
{
    public function __invoke(Request $request, $id)
    {
        $subscribeUser = subscribeUser::with(['subscribe', 'payments', 'subscriptionLogs'])
                                 ->findOrFail($id);

        // 활성화된 구독 목록
        $subscribes = Sitesubscribe::where('is_active', true)
                              ->orderBy('name')
                              ->get();

        // 활성화된 플랜 목록
        $plans = subscribePlan::with('subscribe')
                           ->where('is_active', true)
                           ->orderBy('sort_order')
                           ->orderBy('monthly_price')
                           ->get()
                           ->groupBy('subscribe_id');

        // 상태 옵션
        $statusOptions = [
            'active' => 'Active',
            'pending' => 'Pending',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            'suspended' => 'Suspended'
        ];

        // 결제 주기 옵션
        $billingCycles = [
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
            'lifetime' => 'Lifetime'
        ];

        // 결제 방법 옵션
        $paymentMethods = [
            'card' => 'Credit Card',
            'bank_transfer' => 'Bank Transfer',
            'paypal' => 'PayPal',
            'stripe' => 'Stripe',
            'manual' => 'Manual'
        ];

        // 사용자 샤드 목록 (예시)
        $userShards = [];
        for ($i = 1; $i <= 10; $i++) {
            $shardName = 'users_' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $userShards[$shardName] = $shardName;
        }

        // 결제 내역
        $payments = $subscribeUser->payments()
                               ->orderBy('created_at', 'desc')
                               ->limit(10)
                               ->get();

        // 구독 로그
        $subscriptionLogs = $subscribeUser->subscriptionLogs()
                                       ->orderBy('created_at', 'desc')
                                       ->limit(20)
                                       ->get();

        // 통계 정보
        $stats = [
            'total_payments' => $subscribeUser->payments()->count(),
            'total_paid' => $subscribeUser->total_paid,
            'successful_payments' => $subscribeUser->payments()->completed()->count(),
            'failed_payments' => $subscribeUser->payments()->failed()->count(),
            'refunded_amount' => $subscribeUser->refund_amount,
            'days_until_expiry' => $subscribeUser->days_until_expiry,
        ];

        // 현재 플랜 정보
        $currentPlan = subscribePlan::where('plan_name', $subscribeUser->plan_name)
                                 ->where('subscribe_id', $subscribeUser->subscribe_id)
                                 ->first();

        // 업그레이드/다운그레이드 가능한 플랜들
        $availableUpgrades = [];
        $availableDowngrades = [];

        if ($currentPlan) {
            if ($currentPlan->upgrade_paths) {
                $availableUpgrades = subscribePlan::whereIn('plan_code', $currentPlan->upgrade_paths)
                                               ->where('subscribe_id', $subscribeUser->subscribe_id)
                                               ->where('is_active', true)
                                               ->get();
            }

            if ($currentPlan->downgrade_paths) {
                $availableDowngrades = subscribePlan::whereIn('plan_code', $currentPlan->downgrade_paths)
                                                 ->where('subscribe_id', $subscribeUser->subscribe_id)
                                                 ->where('is_active', true)
                                                 ->get();
            }
        }

        return view('jiny-subscribe::admin.users.edit', compact(
            'subscribeUser',
            'subscribes',
            'plans',
            'statusOptions',
            'billingCycles',
            'paymentMethods',
            'userShards',
            'payments',
            'subscriptionLogs',
            'stats',
            'currentPlan',
            'availableUpgrades',
            'availableDowngrades'
        ));
    }
}
