<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribeDetail;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\Sitesubscribe;
use Jiny\Subscribe\Models\subscribePlanDetail;

class CreateController extends Controller
{
    public function __invoke(Request $request, $subscribeId)
    {
        $subscribe = Sitesubscribe::findOrFail($subscribeId);

        // 다음 정렬 순서 계산
        $nextPos = subscribePlanDetail::where('subscribe_id', $subscribeId)->max('pos') + 1;

        // 필터 옵션들
        $detailTypes = subscribePlanDetail::getDetailTypes();
        $valueTypes = subscribePlanDetail::getValueTypes();
        $categories = subscribePlanDetail::getCategories();

        // 기존 그룹명 목록 (자동완성용)
        $existingGroups = subscribePlanDetail::where('subscribe_id', $subscribeId)
                            ->whereNotNull('group_name')
                            ->distinct()
                            ->pluck('group_name')
                            ->filter()
                            ->sort()
                            ->values();

        return view('jiny-subscribe::admin.service_detail.create', compact(
            'subscribe',
            'nextPos',
            'detailTypes',
            'valueTypes',
            'categories',
            'existingGroups'
        ));
    }
}
