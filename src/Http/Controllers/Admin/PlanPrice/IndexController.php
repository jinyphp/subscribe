<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\PlanPrice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribePlan;
use Jiny\Subscribe\Models\subscribePlanPrice;

class IndexController extends Controller
{
    public function __invoke(Request $request, $planId)
    {
        $plan = subscribePlan::with('subscribe')->findOrFail($planId);

        // 가격 옵션 목록 조회 with 필터링
        $query = subscribePlanPrice::where('subscribe_plan_id', $planId)
                    ->orderBy('pos')
                    ->orderBy('price');

        // 결제 주기별 필터링
        if ($request->filled('billing_period')) {
            $query->where('billing_period', $request->billing_period);
        }

        // 통화별 필터링
        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        // 상태별 필터링
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('enable', true);
            } elseif ($request->status === 'inactive') {
                $query->where('enable', false);
            }
        }

        // 특별 옵션별 필터링
        if ($request->filled('special')) {
            if ($request->special === 'popular') {
                $query->where('is_popular', true);
            } elseif ($request->special === 'recommended') {
                $query->where('is_recommended', true);
            } elseif ($request->special === 'trial') {
                $query->where('trial_days', '>', 0);
            } elseif ($request->special === 'discount') {
                $query->whereNotNull('sale_price');
            }
        }

        // 가격 범위 필터링
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        // 검색
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $prices = $query->paginate(20)->withQueryString();

        // 통계 정보
        $stats = [
            'total' => subscribePlanPrice::where('subscribe_plan_id', $planId)->count(),
            'active' => subscribePlanPrice::where('subscribe_plan_id', $planId)->where('enable', true)->count(),
            'popular' => subscribePlanPrice::where('subscribe_plan_id', $planId)->where('is_popular', true)->count(),
            'with_trial' => subscribePlanPrice::where('subscribe_plan_id', $planId)->where('trial_days', '>', 0)->count(),
        ];

        // 필터 옵션들
        $billingPeriods = [
            'monthly' => '월간',
            'quarterly' => '분기',
            'yearly' => '연간',
            'once' => '일회성',
        ];

        $currencies = subscribePlanPrice::where('subscribe_plan_id', $planId)
                        ->distinct()
                        ->pluck('currency')
                        ->filter()
                        ->sort()
                        ->values();

        return view('jiny-subscribe::admin.plan_price.index', compact(
            'plan',
            'prices',
            'stats',
            'billingPeriods',
            'currencies'
        ));
    }
}
