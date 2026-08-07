<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
use Illuminate\Support\Facades\Route;

// 公開ページ
Route::get('/', [BookController::class, 'index'])
    ->name('home');

Route::get('/ranking', [RankingController::class, 'index'])
    ->name('ranking.index');

// ログイン必須ページ
Route::middleware('auth')->group(function () {
    Route::resource('books', BookController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy']);

    // ISBN検索（Google Books API）
    Route::get('/books/isbn/{isbn}', [BookController::class, 'searchIsbn'])
        ->name('books.search-isbn');

    // レビュー投稿
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');

    // 編集・更新・削除
    Route::resource('reviews', ReviewController::class)
        ->only(['edit', 'update', 'destroy']);

    // レビューいいね追加・解除
    Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'toggle'])
        ->name('reviews.like');

    // お気に入り一覧画面
    Route::get('/favorites', [FavoriteController::class, 'index'])
        ->name('favorites.index');

    // お気に入り追加・解除
    Route::post('/books/{book}/favorite', [FavoriteController::class, 'toggle'])
        ->name('favorites.toggle');

    Route::resource('genres', GenreController::class);
});

//　書籍詳細画面(公開ページ)
Route::resource('books', BookController::class)
    ->only(['index', 'show']);

// 応用要件
Route::middleware('auth')->group(function () {
    // マイ読書レポート
    Route::get('/reports', [ReportController::class, 'index'])
        ->name('reports.index');

    // 読書計画一覧
    Route::get('/reading-plans', [ReadingPlanController::class, 'index'])
        ->name('reading-plans.index');

    // 読書計画を読書中に更新
    Route::post(
        '/reading-plans/{readingPlan}/start',
        [ReadingPlanController::class, 'start']
    )->name('reading-plans.start');

    // 読書計画を読了に更新
    Route::post(
        '/reading-plans/{readingPlan}/complete',
        [ReadingPlanController::class, 'complete']
    )->name('reading-plans.complete');

    // 読書計画作成画面
    Route::get('/reading-plans/create', [ReadingPlanController::class, 'create'])
        ->name('reading-plans.create');

    // 読書計画編集画面
    Route::get('/reading-plans/{readingPlan}/edit', [ReadingPlanController::class, 'edit'])
        ->name('reading-plans.edit');

    // 読書計画作成
    Route::post('/reading-plans', [ReadingPlanController::class, 'store'])
        ->name('reading-plans.store');

    // 読書計画更新
    Route::put(
        '/reading-plans/{readingPlan}',
        [ReadingPlanController::class, 'update']
    )->name('reading-plans.update');

    // 読書計画削除
    Route::delete(
        '/reading-plans/{readingPlan}',
        [ReadingPlanController::class, 'destroy']
    )->name('reading-plans.destroy');

    // 通知一覧
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->name('notifications.read');
});
