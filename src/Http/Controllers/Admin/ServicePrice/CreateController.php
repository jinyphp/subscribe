<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribePrice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\Sitesubscribe;

class CreateController extends Controller
{
    public function __invoke(Request $request, $subscribeId)
    {
        $subscribe = Sitesubscribe::findOrFail($subscribeId);

        return view('jiny-subscribe::admin.service_price.create', compact('subscribe'));
    }
}
