<?php

use App\Http\Controllers\BookController;
use App\Models\Book;
use Illuminate\Support\Facades\Route;

// 公開ページ
Route::get('/', [BookController::class, 'index'])
    ->name('home');

Route::get('/books', [BookController::class, 'index'])
    ->name('books.index');

Route::get('/ranking', function () {
    return view('ranking.index');
})->name('ranking.index');

// ログイン必須ページ
Route::middleware('auth')->group(function () {
    Route::get('/books/create', function () {
        return view('books.create');
    })->name('books.create');

    Route::get('/books/{book}/edit', function () {
        return view('books.edit');
    })->name('books.edit');

    Route::get('/favorites', function () {
        return 'お気に入り一覧は今後実装予定です';
    })->name('favorites.index');

    Route::get('/genres', function () {
        return view('genres.index');
    })->name('genres.index');
});

Route::get('/books/{book}', function (Book $book) {
    return '書籍詳細画面：'.$book->title;
})->name('books.show');
