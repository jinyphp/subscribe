<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\Categories;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * subscribe Categories 수정 컨트롤러
 *
 * 진입 경로:
 * Route::get('/admin/subscribe/categories/{id}/edit') → EditController::__invoke()
 */
class EditController extends Controller
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
            'view' => 'jiny-subscribe::admin.categories.edit',
            'title' => 'subscribe Category 수정',
            'subtitle' => '구독 카테고리를 수정합니다.',
        ];
    }

    public function __invoke(Request $request, $id)
    {
        $category = DB::table($this->config['table'])
            ->where('id', $id)
            ->first();

        if (!$category) {
            return redirect()
                ->route('admin.subscribe.categories.index')
                ->with('error', '카테고리를 찾을 수 없습니다.');
        }

        $parentCategories = $this->getParentCategories($id);

        return view($this->config['view'], [
            'category' => $category,
            'parentCategories' => $parentCategories,
            'config' => $this->config,
        ]);
    }

    protected function getParentCategories($excludeId = null)
    {
        $query = DB::table('subscribe_categories')
            ->whereNull('parent_id')
            ->where('enable', true);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->orderBy('pos')
            ->orderBy('title')
            ->get();
    }
}
