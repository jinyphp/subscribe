<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribePrice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\Sitesubscribe;
use Jiny\Subscribe\Models\subscribePrice;

class IndexController extends Controller
{
    public function __invoke(Request $request, $subscribeId)
    {
        $subscribe = Sitesubscribe::findOrFail($subscribeId);

        $prices = subscribePrice::where('subscribe_id', $subscribeId)
                    ->when($request->search, function ($query, $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%')
                              ->orWhere('code', 'like', '%' . $search . '%')
                              ->orWhere('description', 'like', '%' . $search . '%');
                        });
                    })
                    ->when($request->status !== null, function ($query) use ($request) {
                        $query->where('enable', $request->status === 'active');
                    })
                    ->when($request->type, function ($query, $type) {
                        switch ($type) {
                            case 'popular':
                                $query->where('is_popular', true);
                                break;
                            case 'recommended':
                                $query->where('is_recommended', true);
                                break;
                        }
                    })
                    ->orderBy('pos', 'asc')
                    ->orderBy('price', 'asc')
                    ->paginate(15);

        // 통계 계산
        $stats = [
            'total' => subscribePrice::where('subscribe_id', $subscribeId)->count(),
            'active' => subscribePrice::where('subscribe_id', $subscribeId)->where('enable', true)->count(),
            'popular' => subscribePrice::where('subscribe_id', $subscribeId)->where('is_popular', true)->count(),
            'with_trial' => subscribePrice::where('subscribe_id', $subscribeId)->where('has_trial', true)->count(),
            'with_discount' => subscribePrice::where('subscribe_id', $subscribeId)->whereNotNull('sale_price')->count(),
        ];

        return view('jiny-subscribe::admin.service_price.index', compact(
            'subscribe',
            'prices',
            'stats'
        ));
    }
}
