<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribeSubscriptionLog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribeSubscriptionLog;
use Jiny\Subscribe\Models\subscribeUser;
use Jiny\Subscribe\Models\subscribe;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = subscribeSubscriptionLog::with(['subscribeUser', 'subscribe'])
                                       ->orderBy('created_at', 'desc');

        // 필터링
        if ($request->filled('user_uuid')) {
            $query->where('user_uuid', $request->user_uuid);
        }

        if ($request->filled('subscribe_id')) {
            $query->where('subscribe_id', $request->subscribe_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('result')) {
            $query->where('result', $request->result);
        }

        if ($request->filled('processed_by')) {
            $query->where('processed_by', $request->processed_by);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // 검색 (사용자 이름, 이메일, 액션 제목으로 검색)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('action_title', 'like', "%{$search}%")
                  ->orWhere('action_description', 'like', "%{$search}%")
                  ->orWhereHas('subscribeUser', function($subQuery) use ($search) {
                      $subQuery->where('user_name', 'like', "%{$search}%")
                               ->orWhere('user_email', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        // 통계 데이터
        $stats = [
            'total' => subscribeSubscriptionLog::count(),
            'today' => subscribeSubscriptionLog::whereDate('created_at', today())->count(),
            'this_week' => subscribeSubscriptionLog::where('created_at', '>=', now()->startOfWeek())->count(),
            'this_month' => subscribeSubscriptionLog::where('created_at', '>=', now()->startOfMonth())->count(),
            'successful' => subscribeSubscriptionLog::where('result', 'success')->count(),
            'failed' => subscribeSubscriptionLog::where('result', 'failed')->count(),
        ];

        // 액션 별 통계
        $actionStats = subscribeSubscriptionLog::selectRaw('action, COUNT(*) as count')
                                            ->groupBy('action')
                                            ->orderBy('count', 'desc')
                                            ->limit(10)
                                            ->get();

        // 필터 옵션 데이터
        $subscribes = subscribe::where('enable', true)->orderBy('title')->get(['id', 'title']);
        $actions = [
            'subscribe' => '구독 신청',
            'activate' => '구독 활성화',
            'suspend' => '구독 일시정지',
            'resume' => '구독 재개',
            'cancel' => '구독 취소',
            'expire' => '구독 만료',
            'renew' => '구독 갱신',
            'upgrade' => '등급 업그레이드',
            'downgrade' => '등급 다운그레이드',
            'extend' => '구독 연장',
            'refund' => '환불',
            'payment_success' => '결제 성공',
            'payment_failed' => '결제 실패',
            'admin_action' => '관리자 조치'
        ];

        return view('jiny-subscribe::admin.service_subscription_log.index', compact(
            'logs',
            'stats',
            'actionStats',
            'subscribes',
            'actions'
        ));
    }
}
