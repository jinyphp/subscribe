<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\PlanDetail;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribePlan;
use Jiny\Subscribe\Models\subscribePlanDetail;

class ShowController extends Controller
{
    public function __invoke(Request $request, $planId, $detailId)
    {
        $plan = subscribePlan::with('subscribe')->findOrFail($planId);

        $detail = subscribePlanDetail::where('subscribe_plan_id', $planId)
                    ->findOrFail($detailId);

        // 같은 그룹의 다른 상세 정보들
        $relatedDetails = [];
        if ($detail->group_name) {
            $relatedDetails = subscribePlanDetail::where('subscribe_plan_id', $planId)
                                ->where('group_name', $detail->group_name)
                                ->where('id', '!=', $detailId)
                                ->enabled()
                                ->ordered()
                                ->get();
        }

        // 같은 카테고리의 다른 상세 정보들
        $categoryDetails = [];
        if ($detail->category) {
            $categoryDetails = subscribePlanDetail::where('subscribe_plan_id', $planId)
                                ->where('category', $detail->category)
                                ->where('id', '!=', $detailId)
                                ->enabled()
                                ->ordered()
                                ->limit(5)
                                ->get();
        }

        // 이전/다음 상세 정보
        $prevDetail = subscribePlanDetail::where('subscribe_plan_id', $planId)
                        ->where('pos', '<', $detail->pos)
                        ->orderBy('pos', 'desc')
                        ->first();

        $nextDetail = subscribePlanDetail::where('subscribe_plan_id', $planId)
                        ->where('pos', '>', $detail->pos)
                        ->orderBy('pos', 'asc')
                        ->first();

        return view('jiny-subscribe::admin.plan_detail.show', compact(
            'plan',
            'detail',
            'relatedDetails',
            'categoryDetails',
            'prevDetail',
            'nextDetail'
        ));
    }
}
