<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribeDetail;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\Sitesubscribe;
use Jiny\Subscribe\Models\subscribePlanDetail;

class EditController extends Controller
{
    public function __invoke(Request $request, $subscribeId, $detailId)
    {
        $subscribe = Sitesubscribe::findOrFail($subscribeId);

        $detail = subscribePlanDetail::where('subscribe_id', $subscribeId)
                     ->findOrFail($detailId);

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

        return view('jiny-subscribe::admin.service_detail.edit', compact(
            'subscribe',
            'detail',
            'detailTypes',
            'valueTypes',
            'categories',
            'existingGroups'
        ));
    }
}
