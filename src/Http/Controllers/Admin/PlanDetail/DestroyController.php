<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\PlanDetail;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribePlan;
use Jiny\Subscribe\Models\subscribePlanDetail;

class DestroyController extends Controller
{
    public function __invoke(Request $request, $planId, $detailId)
    {
        $plan = subscribePlan::findOrFail($planId);

        $detail = subscribePlanDetail::where('subscribe_plan_id', $planId)
                    ->findOrFail($detailId);

        // 삭제 실행 (Soft Delete)
        $detail->delete();

        return redirect()
            ->route('admin.subscribe.plan.detail.index', $planId)
            ->with('success', '플랜 상세 정보가 성공적으로 삭제되었습니다.');
    }
}
