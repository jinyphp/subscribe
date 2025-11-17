<?php

namespace Jiny\Subscribe\Http\Controllers\Admin\Dashboard;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * subscribe Dashboard 컨트롤러
 *
 * 진입 경로:
 * Route::get('/admin/subscribe/') → IndexController::__invoke()
 */
class IndexController extends Controller
{
    protected $config;

    public function __construct()
    {
        $this->loadConfig();
    }

    protected function loadConfig()
    {
        $this->config = [
            'title' => 'subscribe Dashboard',
            'subtitle' => '구독 관리 대시보드입니다.',
            'view' => 'jiny-subscribe::admin.dashboard.index',
        ];
    }

    public function __invoke(Request $request)
    {
        // 전체 통계 수집
        $stats = $this->getOverallStatistics();

        // 최근 구독 목록
        $recentsubscribes = $this->getRecentsubscribes();

        // 카테고리별 통계
        $categoryStats = $this->getCategoryStatistics();

        // 월별 구독 등록 통계 (최근 6개월)
        $monthlyStats = $this->getMonthlyStatistics();

        return view($this->config['view'], [
            'stats' => $stats,
            'recentsubscribes' => $recentsubscribes,
            'categoryStats' => $categoryStats,
            'monthlyStats' => $monthlyStats,
            'config' => $this->config,
        ]);
    }

    protected function getOverallStatistics()
    {
        $total = DB::table('subscribes')->whereNull('deleted_at')->count();
        $published = DB::table('subscribes')->where('enable', true)->whereNull('deleted_at')->count();
        $draft = DB::table('subscribes')->where('enable', false)->whereNull('deleted_at')->count();
        $featured = DB::table('subscribes')->where('featured', true)->whereNull('deleted_at')->count();

        // 카테고리 수
        $categories = DB::table('subscribe_categories')->where('enable', true)->count();

        // 이번 달 신규 등록
        $thisMonth = DB::table('subscribes')
            ->whereNull('deleted_at')
            ->whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->count();

        return [
            'total' => $total,
            'published' => $published,
            'draft' => $draft,
            'featured' => $featured,
            'categories' => $categories,
            'this_month' => $thisMonth,
            'published_rate' => $total > 0 ? round(($published / $total) * 100, 1) : 0,
        ];
    }

    protected function getRecentsubscribes($limit = 5)
    {
        return DB::table('subscribes')
            ->leftJoin('subscribe_categories', 'subscribes.category_id', '=', 'subscribe_categories.id')
            ->select(
                'subscribes.*',
                'subscribe_categories.title as category_name'
            )
            ->whereNull('subscribes.deleted_at')
            ->orderBy('subscribes.created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    protected function getCategoryStatistics()
    {
        return DB::table('subscribe_categories')
            ->leftJoin('subscribes', function($join) {
                $join->on('subscribe_categories.id', '=', 'subscribes.category_id')
                     ->whereNull('subscribes.deleted_at');
            })
            ->select(
                'subscribe_categories.id',
                'subscribe_categories.title',
                DB::raw('COUNT(subscribes.id) as subscribe_count'),
                DB::raw('SUM(CASE WHEN subscribes.enable = 1 THEN 1 ELSE 0 END) as published_count')
            )
            ->where('subscribe_categories.enable', true)
            ->groupBy('subscribe_categories.id', 'subscribe_categories.title')
            ->orderBy('subscribe_count', 'desc')
            ->get();
    }

    protected function getMonthlyStatistics($months = 6)
    {
        $stats = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $year = $date->year;
            $month = $date->month;

            $count = DB::table('subscribes')
                ->whereNull('deleted_at')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count();

            $stats[] = [
                'year' => $year,
                'month' => $month,
                'month_name' => $date->format('Y-m'),
                'count' => $count,
            ];
        }

        return $stats;
    }
}
