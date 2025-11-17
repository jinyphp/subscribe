<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\PlanPrice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribePlan;
use Jiny\Subscribe\Models\subscribePlanPrice;

class DestroyController extends Controller
{
    public function __invoke(Request $request, $planId, $priceId)
    {
        $plan = subscribePlan::findOrFail($planId);

        $price = subscribePlanPrice::where('subscribe_plan_id', $planId)
                   ->findOrFail($priceId);

        // 다른 가격 옵션이 있는지 확인
        $otherPrices = subscribePlanPrice::where('subscribe_plan_id', $planId)
                         ->where('id', '!=', $priceId)
                         ->count();

        if ($otherPrices === 0) {
            return redirect()
                ->route('admin.subscribe.plan.price.index', $planId)
                ->with('error', '최소 하나의 가격 옵션은 유지되어야 합니다.');
        }

        // 삭제 실행 (Soft Delete)
        $price->delete();

        return redirect()
            ->route('admin.subscribe.plan.price.index', $planId)
            ->with('success', '플랜 가격 옵션이 성공적으로 삭제되었습니다.');
    }
}
