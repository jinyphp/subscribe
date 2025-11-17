<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribeUsers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribeUser;

class ShowController extends Controller
{
    public function __invoke(Request $request, $id)
    {
        $subscribeUser = subscribeUser::with(['subscribe'])->findOrFail($id);

        // 샤딩된 사용자 정보 조회
        $shardUser = $subscribeUser->getUserFromShard();

        // 결제 이력 (임시로 빈 컬렉션)
        $paymentHistory = collect(); // 실제로는 payments 관계를 사용

        // 구독 로그 (임시로 빈 컬렉션)
        $subscriptionLogs = collect(); // 실제로는 subscriptionLogs 관계를 사용

        // 통계 정보
        $stats = [
            'total_paid' => $subscribeUser->total_paid,
            'days_until_expiry' => $subscribeUser->days_until_expiry,
            'is_active' => $subscribeUser->is_active,
            'is_expired' => $subscribeUser->is_expired,
            'is_expiring_soon' => $subscribeUser->is_expiring_soon,
        ];

        // 관련 구독들 (같은 사용자의 다른 구독)
        $relatedSubscriptions = subscribeUser::where('user_uuid', $subscribeUser->user_uuid)
                                          ->where('id', '!=', $subscribeUser->id)
                                          ->with('subscribe')
                                          ->orderBy('created_at', 'desc')
                                          ->take(5)
                                          ->get();

        // 상태 변경 가능 여부
        $canActivate = in_array($subscribeUser->status, ['pending', 'suspended']);
        $canSuspend = $subscribeUser->status === 'active';
        $canCancel = in_array($subscribeUser->status, ['active', 'suspended', 'pending']);
        $canReactivate = in_array($subscribeUser->status, ['cancelled', 'expired']);

        return view('jiny-subscribe::admin.service_users.show', compact(
            'subscribeUser',
            'shardUser',
            'paymentHistory',
            'subscriptionLogs',
            'stats',
            'relatedSubscriptions',
            'canActivate',
            'canSuspend',
            'canCancel',
            'canReactivate'
        ));
    }
}
