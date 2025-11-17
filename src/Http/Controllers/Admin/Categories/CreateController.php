<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\Categories;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * subscribe Categories 생성 컨트롤러
 *
 * 진입 경로:
 * Route::get('/admin/subscribe/categories/create') → CreateController::__invoke()
 */
class CreateController extends Controller
{
    protected $config;

    public function __construct()
    {
        $this->loadConfig();
    }

    protected function loadConfig()
    {
        $this->config = [
            'table' => 'subscribe_categories',
            'view' => 'jiny-subscribe::admin.categories.create',
            'title' => 'subscribe Category 생성',
            'subtitle' => '새로운 구독 카테고리를 생성합니다.',
        ];
    }

    public function __invoke(Request $request)
    {
        $parentCategories = $this->getParentCategories();

        return view($this->config['view'], [
            'parentCategories' => $parentCategories,
            'config' => $this->config,
        ]);
    }

    protected function getParentCategories()
    {
        return DB::table('subscribe_categories')
            ->whereNull('parent_id')
            ->where('enable', true)
            ->orderBy('pos')
            ->orderBy('title')
            ->get();
    }
}
