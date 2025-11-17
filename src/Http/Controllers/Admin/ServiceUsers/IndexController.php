<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribeUsers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribeUser;
use Jiny\Subscribe\Models\subscribe;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        // 검색 및 필터 파라미터
        $search = $request->get('search');
        $status = $request->get('status');
        $subscribe_id = $request->get('subscribe_id');
        $billing_cycle = $request->get('billing_cycle');
        $payment_status = $request->get('payment_status');

        // 기본 쿼리
        $query = subscribeUser::with(['subscribe'])
                            ->orderBy('created_at', 'desc');

        // 검색 조건
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('user_email', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('user_uuid', 'like', "%{$search}%")
                  ->orWhere('plan_name', 'like', "%{$search}%");
            });
        }

        // 상태 필터
        if ($status) {
            if ($status === 'expiring_soon') {
                $query->expiringSoon();
            } elseif ($status === 'expired') {
                $query->expired();
            } else {
                $query->where('status', $status);
            }
        }

        // 구독 필터
        if ($subscribe_id) {
            $query->where('subscribe_id', $subscribe_id);
        }

        // 결제 주기 필터
        if ($billing_cycle) {
            $query->where('billing_cycle', $billing_cycle);
        }

        // 결제 상태 필터
        if ($payment_status) {
            $query->where('payment_status', $payment_status);
        }

        // 페이지네이션
        $subscribeUsers = $query->paginate(20);

        // 통계 데이터
        $stats = $this->getStatistics();

        // 필터용 데이터
        $subscribes = subscribe::where('enable', true)->orderBy('title')->get();
        $statusOptions = [
            'active' => '활성',
            'suspended' => '일시정지',
            'cancelled' => '취소',
            'expired' => '만료',
            'pending' => '대기',
            'expiring_soon' => '곧 만료'
        ];
        $billingCycleOptions = [
            'monthly' => '월간',
            'quarterly' => '분기',
            'yearly' => '연간',
            'lifetime' => '평생'
        ];
        $paymentStatusOptions = [
            'pending' => '결제 대기',
            'paid' => '결제 완료',
            'failed' => '결제 실패',
            'refunded' => '환불 완료'
        ];

        return view('jiny-subscribe::admin.service_users.index', compact(
            'subscribeUsers',
            'stats',
            'subscribes',
            'statusOptions',
            'billingCycleOptions',
            'paymentStatusOptions'
        ));
    }

    private function getStatistics()
    {
        $total = subscribeUser::count();
        $active = subscribeUser::where('status', 'active')->count();
        $expired = subscribeUser::expired()->count();
        $expiringSoon = subscribeUser::expiringSoon()->count();
        $thisMonth = subscribeUser::whereMonth('created_at', now()->month)
                               ->whereYear('created_at', now()->year)
                               ->count();

        $totalRevenue = subscribeUser::where('status', 'active')
                                  ->sum('monthly_price');

        $pendingPayments = subscribeUser::where('payment_status', 'pending')
                                     ->where('status', 'active')
                                     ->count();

        return [
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'expiring_soon' => $expiringSoon,
            'this_month' => $thisMonth,
            'total_revenue' => $totalRevenue,
            'pending_payments' => $pendingPayments,
            'active_rate' => $total > 0 ? round(($active / $total) * 100, 1) : 0,
        ];
    }
}
