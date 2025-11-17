<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribeUsers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jiny\Subscribe\Models\subscribeUser;
use Jiny\Subscribe\Models\subscribe;

class UpdateController extends Controller
{
    public function __invoke(Request $request, $id)
    {
        $subscribeUser = subscribeUser::findOrFail($id);

        $validated = $request->validate([
            'user_uuid' => 'required|string|max:255',
            'user_shard' => 'required|string|max:255',
            'user_id' => 'required|integer',
            'user_email' => 'required|email|max:255',
            'user_name' => 'required|string|max:255',
            'subscribe_id' => 'required|exists:subscribes,id',
            'status' => 'required|in:pending,active,suspended,cancelled,expired',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly,lifetime',
            'started_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'next_billing_at' => 'nullable|date',
            'plan_name' => 'nullable|string|max:255',
            'plan_price' => 'nullable|numeric|min:0',
            'plan_features' => 'nullable|array',
            'monthly_price' => 'nullable|numeric|min:0',
            'total_paid' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:255',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'auto_renewal' => 'boolean',
            'auto_upgrade' => 'boolean',
            'cancel_reason' => 'nullable|string',
            'refund_amount' => 'nullable|numeric|min:0',
            'refunded_at' => 'nullable|date',
            'admin_notes' => 'nullable|string',
        ]);

        try {
            // 구독 정보 업데이트
            if ($validated['subscribe_id'] !== $subscribeUser->subscribe_id) {
                $subscribe = subscribe::findOrFail($validated['subscribe_id']);
                $validated['subscribe_title'] = $subscribe->title;
            }

            // 상태 변경 로그
            $statusChanged = $subscribeUser->status !== $validated['status'];
            $oldStatus = $subscribeUser->status;

            // 취소 처리
            if ($validated['status'] === 'cancelled' && !$subscribeUser->cancelled_at) {
                $validated['cancelled_at'] = now();
                $validated['auto_renewal'] = false;
            }

            // 활성화 처리
            if ($validated['status'] === 'active' && in_array($oldStatus, ['pending', 'suspended'])) {
                if (!$validated['started_at']) {
                    $validated['started_at'] = now();
                }
            }

            // 업데이트 실행
            $subscribeUser->update($validated);

            // 사용자 캐시 정보 업데이트
            $subscribeUser->updateUserCache();

            // 상태 변경 로그 기록
            if ($statusChanged) {
                $subscribeUser->subscriptionLogs()->create([
                    'user_uuid' => $subscribeUser->user_uuid,
                    'subscribe_id' => $subscribeUser->subscribe_id,
                    'action' => 'status_change',
                    'action_title' => '상태 변경',
                    'action_description' => "상태가 '{$oldStatus}'에서 '{$validated['status']}'로 변경되었습니다.",
                    'status_before' => $oldStatus,
                    'status_after' => $validated['status'],
                    'processed_by' => 'admin',
                ]);
            }

            return redirect()
                ->route('admin.subscribe.users.show', $subscribeUser->id)
                ->with('success', '구독 구독자 정보가 성공적으로 업데이트되었습니다.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', '구독자 정보 업데이트 중 오류가 발생했습니다: ' . $e->getMessage());
        }
    }
}
