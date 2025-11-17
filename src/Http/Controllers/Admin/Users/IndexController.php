<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribeUser;
use Jiny\Subscribe\Models\Sitesubscribe;
use Jiny\Subscribe\Models\subscribePlan;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        // 필터링을 위한 기본 데이터
        $subscribes = Sitesubscribe::select('id', 'name')->orderBy('name')->get();
        $plans = subscribePlan::select('plan_name')->distinct()->orderBy('plan_name')->get();

        // 구독 사용자 목록 조회 with 필터링
        $query = subscribeUser::with(['subscribe'])
                    ->orderBy('created_at', 'desc');

        // 구독별 필터링
        if ($request->filled('subscribe_id')) {
            $query->where('subscribe_id', $request->subscribe_id);
        }

        // 플랜별 필터링
        if ($request->filled('plan_name')) {
            $query->where('plan_name', $request->plan_name);
        }

        // 상태별 필터링
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'expired') {
                $query->expired();
            } elseif ($request->status === 'expiring_soon') {
                $query->expiringSoon(7);
            } else {
                $query->where('status', $request->status);
            }
        }

        // 결제 상태별 필터링
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // 결제 주기별 필터링
        if ($request->filled('billing_cycle')) {
            $query->where('billing_cycle', $request->billing_cycle);
        }

        // 자동 갱신 필터링
        if ($request->filled('auto_renewal')) {
            $query->where('auto_renewal', $request->auto_renewal === 'true');
        }

        // 검색
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('user_uuid', 'like', "%{$search}%")
                  ->orWhere('user_email', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('plan_name', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20)->withQueryString();

        // 통계 정보
        $stats = [
            'total' => subscribeUser::count(),
            'active' => subscribeUser::active()->count(),
            'expired' => subscribeUser::expired()->count(),
            'expiring_soon' => subscribeUser::expiringSoon(7)->count(),
            'total_revenue' => subscribeUser::sum('total_paid'),
            'monthly_revenue' => subscribeUser::where('created_at', '>=', now()->startOfMonth())->sum('total_paid'),
        ];

        // 최근 활동
        $recentActivities = subscribeUser::with('subscribe')
                                     ->orderBy('updated_at', 'desc')
                                     ->limit(10)
                                     ->get();

        return view('jiny-subscribe::admin.users.index', compact(
            'users',
            'subscribes',
            'plans',
            'stats',
            'recentActivities'
        ));
    }
}
