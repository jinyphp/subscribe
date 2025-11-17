<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\Plan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribePlan;
use Jiny\Subscribe\Models\Sitesubscribe;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        // 구독 필터링을 위한 구독 목록
        $subscribes = Sitesubscribe::select('id', 'title')->orderBy('title')->get();

        // 플랜 목록 조회 with 필터링
        $query = subscribePlan::with('subscribe')
                    ->orderBy('sort_order')
                    ->orderBy('monthly_price');

        // 구독별 필터링
        if ($request->filled('subscribe_id')) {
            $query->where('subscribe_id', $request->subscribe_id);
        }

        // 플랜 타입별 필터링
        if ($request->filled('plan_type')) {
            $query->where('plan_type', $request->plan_type);
        }

        // 상태별 필터링
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // 검색
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('plan_name', 'like', "%{$search}%")
                  ->orWhere('plan_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $plans = $query->paginate(15)->withQueryString();

        // 통계 정보
        $stats = [
            'total' => subscribePlan::count(),
            'active' => subscribePlan::where('is_active', true)->count(),
            'featured' => subscribePlan::where('is_featured', true)->count(),
            'with_trial' => subscribePlan::where('allow_trial', true)->where('trial_period_days', '>', 0)->count(),
        ];

        return view('jiny-subscribe::admin.plan.index', compact('plans', 'subscribes', 'stats'));
    }
}
