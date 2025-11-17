<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribePrice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\Sitesubscribe;
use Jiny\Subscribe\Models\subscribePrice;

class DestroyController extends Controller
{
    public function __invoke(Request $request, $subscribeId, $priceId)
    {
        $subscribe = Sitesubscribe::findOrFail($subscribeId);

        $price = subscribePrice::where('subscribe_id', $subscribeId)
                   ->findOrFail($priceId);

        // 다른 가격 옵션이 있는지 확인
        $otherPrices = subscribePrice::where('subscribe_id', $subscribeId)
                         ->where('id', '!=', $priceId)
                         ->count();

        if ($otherPrices === 0) {
            return redirect()
                ->route('admin.site.subscribes.price.index', $subscribeId)
                ->with('error', '최소 하나의 가격 옵션은 유지되어야 합니다.');
        }

        // 기본 옵션 삭제 시 다른 옵션을 기본으로 설정
        if ($price->is_default && $otherPrices > 0) {
            $nextPrice = subscribePrice::where('subscribe_id', $subscribeId)
                          ->where('id', '!=', $priceId)
                          ->where('enable', true)
                          ->orderBy('sort_order')
                          ->first();

            if ($nextPrice) {
                $nextPrice->update(['is_default' => true]);
            }
        }

        // 삭제 실행 (Soft Delete)
        $price->delete();

        return redirect()
            ->route('admin.site.subscribes.price.index', $subscribeId)
            ->with('success', '구독 가격이 성공적으로 삭제되었습니다.');
    }
}
