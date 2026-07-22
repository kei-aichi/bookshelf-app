<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * ジャンル一覧を表示する
     */
    public function index()
    {
        $genres = Genre::withCount('books')
            ->orderBy('name')
            ->get();

        return view('genres.index', compact('genres'));
    }

    /**
     * ジャンル登録画面を表示する
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * ジャンルを登録する
     */
    public function store(StoreGenreRequest $request): RedirectResponse
    {
        Genre::create($request->validated());

        return redirect()
            ->route('genres.index')
            ->with('success', '新しいジャンルが登録されました');
    }

    /**
     * ジャンル詳細を表示する
     */
    public function show(Genre $genre): View
    {
        $books = $genre->books()
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    /**
     * ジャンル編集画面を表示する
     */
    public function edit(Genre $genre): View
    {
        return view('genres.edit', compact('genre'));
    }

    /**
     * ジャンルを更新する
     */
    public function update(UpdateGenreRequest $request, Genre $genre): RedirectResponse
    {
        $genre->update($request->validated());

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンル名が更新されました');
    }

    /**
     * ジャンルを削除する
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        $hasBooksWithOnlyThisGenre = $genre->books()
            ->withCount('genres')
            ->get()
            ->contains(fn ($book) => $book->genres_count === 1);

        if ($hasBooksWithOnlyThisGenre) {
            return redirect()
                ->route('genres.index')
                ->with(
                    'error',
                    'このジャンルのみが設定されている書籍があるため削除できません'
                );
        }

        $genre->delete();

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを削除しました');
    }
}
