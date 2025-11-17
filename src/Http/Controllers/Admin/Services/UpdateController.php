<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\subscribes;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * subscribes 업데이트 컨트롤러
 */
class UpdateController extends Controller
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

        $validated = $request->validate([
            'title' => 'required|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:subscribe_categories,id',
            'price' => 'nullable|numeric|min:0',
            'duration' => 'nullable|string|max:100',
            'image' => 'nullable|string|max:500',
            'images' => 'nullable|string',
            'features' => 'nullable|json',
            'process' => 'nullable|json',
            'requirements' => 'nullable|json',
            'deliverables' => 'nullable|json',
            'tags' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'enable' => 'boolean',
            'featured' => 'boolean',
        ]);

        // slug 업데이트 (제목이 변경된 경우)
        if ($validated['title'] !== $subscribe->title) {
            $validated['slug'] = Str::slug($validated['title']);

            // 중복 slug 처리 (자기 자신 제외)
            $originalSlug = $validated['slug'];
            $count = 1;
            while (DB::table($this->config['table'])
                    ->where('slug', $validated['slug'])
                    ->where('id', '!=', $id)
                    ->whereNull('deleted_at')
                    ->exists()) {
                $validated['slug'] = $originalSlug . '-' . $count;
                $count++;
            }
        }

        $validated['updated_at'] = now();

        DB::table($this->config['table'])
            ->where('id', $id)
            ->update($validated);

        return redirect()
            ->route($this->config['redirect_route'])
            ->with('success', 'subscribe가 성공적으로 업데이트되었습니다.');
    }
}
