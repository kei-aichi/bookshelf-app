<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RankingController;
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

Route::middleware('auth')->group(function () {
    Route::view('/reports', 'reports.index')
        ->name('reports.index');

    Route::view('/reading-plans', 'reading-plans.index')
        ->name('reading-plans.index');

    Route::view('/notifications', 'notifications.index')
        ->name('notifications.index');
});
