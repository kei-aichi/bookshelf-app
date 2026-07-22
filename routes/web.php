<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\GenreController;
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

    Route::post('/books/{book}/reviews', function () {
        return back()->with('success', 'レビュー機能は今後実装予定です。');
    })->name('reviews.store');

    Route::get('/favorites', function () {
        return 'お気に入り一覧は今後実装予定です';
    })->name('favorites.index');

    Route::resource('genres', GenreController::class);
});

Route::resource('books', BookController::class)
    ->only(['index', 'show']);
