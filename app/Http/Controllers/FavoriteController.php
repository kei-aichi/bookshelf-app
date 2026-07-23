<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * お気に入り一覧画面を表示する
     */
    public function index(Request $request): View
    {
        $books = $request->user()
            ->favoriteBooks()
            ->with('genres')
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * お気に入りを追加・解除する
     */
    public function toggle(Request $request, Book $book): RedirectResponse
    {
        $result = $request->user()
            ->favoriteBooks()
            ->toggle($book->id);

        $message = count($result['attached']) > 0
            ? 'お気に入りに追加しました'
            : 'お気に入りが解除されました';

        return redirect()
            ->route('books.show', $book)
            ->with('success', $message);
    }
}
