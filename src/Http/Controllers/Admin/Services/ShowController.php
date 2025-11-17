<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribes;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * subscribes 상세보기 컨트롤러
 */
class ShowController extends Controller
{
    protected $config;

    public function __construct()
    {
        $this->config = [
            'table' => 'subscribes',
            'view' => 'jiny-subscribe::admin.services.show',
            'title' => 'subscribe 상세보기',
        ];
    }

    public function __invoke(Request $request, $id)
    {
        // Eloquent 모델 사용으로 변경
        $subscribe = \Jiny\Subscribe\Models\Sitesubscribe::findOrFail($id);

        return view($this->config['view'], [
            'subscribe' => $subscribe,
            'config' => $this->config,
        ]);
    }
}
