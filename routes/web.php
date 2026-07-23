<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
use Illuminate\Support\Facades\Route;

// 公開ページ
Route::get('/', [BookController::class, 'index'])
    ->name('home');

Route::get('/ranking', function () {
    return view('ranking.index');
})->name('ranking.index');

// ログイン必須ページ
Route::middleware('auth')->group(function () {
    Route::resource('books', BookController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy']);

    Route::post('/books/{book}/favorites', function () {
        return back()->with('success', 'お気に入り機能は今後実装予定です。');
    })->name('favorites.toggle');

    // レビュー投稿
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');

    // 編集・更新・削除
    Route::resource('reviews', ReviewController::class)
        ->only(['edit', 'update', 'destroy']);

    // レビューいいね追加・解除
    Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'toggle'])
        ->name('reviews.like');

    Route::get('/favorites', function () {
        return 'お気に入り一覧は今後実装予定です';
    })->name('favorites.index');

    Route::resource('genres', GenreController::class);
});

//　書籍詳細画面(公開ページ)
Route::resource('books', BookController::class)
    ->only(['index', 'show']);
