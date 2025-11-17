<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribeSubscriptionLog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribeSubscriptionLog;

class ShowController extends Controller
{
    public function __invoke(Request $request, $id)
    {
        $log = subscribeSubscriptionLog::with([
            'subscribeUser.subscribe',
            'subscribe'
        ])->findOrFail($id);

        // 동일 사용자의 관련 로그들 (최근 10개)
        $relatedLogs = subscribeSubscriptionLog::where('user_uuid', $log->user_uuid)
                                            ->where('id', '!=', $log->id)
                                            ->orderBy('created_at', 'desc')
                                            ->limit(10)
                                            ->get();

        // 같은 액션의 최근 로그들 (최근 5개)
        $similarLogs = subscribeSubscriptionLog::where('action', $log->action)
                                            ->where('id', '!=', $log->id)
                                            ->orderBy('created_at', 'desc')
                                            ->limit(5)
                                            ->get();

        return view('jiny-subscribe::admin.service_subscription_log.show', compact(
            'log',
            'relatedLogs',
            'similarLogs'
        ));
    }
}
