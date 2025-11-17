<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribes;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * subscribes 수정 폼 컨트롤러
 */
class EditController extends Controller
{
    protected $config;

    public function __construct()
    {
        $this->config = [
            'table' => 'subscribes',
            'view' => 'jiny-subscribe::admin.services.edit',
            'title' => 'subscribe 수정',
            'subtitle' => '구독 정보를 수정합니다.',
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
                ->route('admin.site.subscribes.index')
                ->with('error', 'subscribe를 찾을 수 없습니다.');
        }

        // 활성화된 구독 카테고리 목록 조회
        $categories = DB::table('subscribe_categories')
            ->whereNull('deleted_at')
            ->where('enable', true)
            ->orderBy('pos')
            ->orderBy('title')
            ->get();

        // 카테고리를 배열로 변환하여 display_title 추가
        $categoriesArray = [];
        foreach ($categories as $category) {
            $category->display_title = $category->title;
            $categoriesArray[] = $category;
        }

        return view($this->config['view'], [
            'subscribe' => $subscribe,
            'categories' => $categoriesArray,
            'config' => $this->config,
        ]);
    }

    /**
     * 카테고리를 계층 구조로 정리
     */
    private function buildCategoryHierarchy($categories, $parentId = null, $level = 0)
    {
        $result = [];

        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $category->level = $level;
                $category->display_title = str_repeat('└ ', $level) . $category->title;

                $result[] = $category;

                // 하위 카테고리 재귀 호출
                $children = $this->buildCategoryHierarchy($categories, $category->id, $level + 1);
                $result = array_merge($result, $children);
            }
        }

        return $result;
    }
}
