<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribes;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * subscribes 삭제 컨트롤러
 */
class DestroyController extends Controller
{
    protected $config;

    public function __construct()
    {
        $this->config = [
            'table' => 'subscribes',
            'redirect_route' => 'admin.site.subscribes.index',
        ];
    }

    public function __invoke(Request $request, $id)
    {
        $subscribe = DB::table($this->config['table'])
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (!$subscribe) {
            return redirect()
                ->route($this->config['redirect_route'])
                ->with('error', 'subscribe를 찾을 수 없습니다.');
        }

        // Soft delete
        DB::table($this->config['table'])
            ->where('id', $id)
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route($this->config['redirect_route'])
            ->with('success', 'subscribe가 성공적으로 삭제되었습니다.');
    }
}
