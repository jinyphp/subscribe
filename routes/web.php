<?php

use Illuminate\Support\Facades\Route;

/**
 * Subscribe (구독) 사용자 페이지 라우트
 *
 * @description
 * 사용자가 접근할 수 있는 구독 기능을 제공합니다.
 */
Route::middleware('web')->prefix('subscribes')->name('subscribes.')->group(function () {
    // 구독 목록 페이지
    Route::get('/', \Jiny\Subscribe\Http\Controllers\Site\Services\IndexController::class)
        ->name('index');

    // 구독 검색
    Route::get('/search', \Jiny\Subscribe\Http\Controllers\Site\Services\SearchController::class)
        ->name('search');

    // 구독 카테고리별 목록
    Route::get('/category/{slug}', \Jiny\Subscribe\Http\Controllers\Site\Services\CategoryController::class)
        ->name('category');

    // 구독 상세 페이지
    Route::get('/{slug}', \Jiny\Subscribe\Http\Controllers\Site\Services\ShowController::class)
        ->name('show')
        ->where('slug', '[a-zA-Z0-9\-_]+');
});
