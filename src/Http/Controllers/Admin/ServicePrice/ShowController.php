<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribePrice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\Sitesubscribe;
use Jiny\Subscribe\Models\subscribePrice;

class ShowController extends Controller
{
    public function __invoke(Request $request, $subscribeId, $priceId)
    {
        $subscribe = Sitesubscribe::findOrFail($subscribeId);

        $price = subscribePrice::where('subscribe_id', $subscribeId)
                   ->findOrFail($priceId);

        return view('jiny-subscribe::admin.service_price.show', compact('subscribe', 'price'));
    }
}
