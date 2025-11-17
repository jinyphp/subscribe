<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribeDetail;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\Sitesubscribe;
use Jiny\Subscribe\Models\subscribePlanDetail;

class DestroyController extends Controller
{
    public function __invoke(Request $request, $subscribeId, $detailId)
    {
        $subscribe = Sitesubscribe::findOrFail($subscribeId);

        $detail = subscribePlanDetail::where('subscribe_id', $subscribeId)
                     ->findOrFail($detailId);

        $detail->delete();

        return redirect()
            ->route('admin.site.subscribes.detail.index', $subscribeId)
            ->with('success', '구독 상세 정보가 성공적으로 삭제되었습니다.');
    }
}
