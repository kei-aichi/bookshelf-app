<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BookIndexRequest;
use App\Http\Resources\BookDetailResource;
use App\Http\Resources\BookResource;
use App\Models\Book;

class BookController extends Controller
{
    /**
     * 書籍一覧を取得する
     */
    public function index(BookIndexRequest $request)
    {
        $validated = $request->validated();

        $perPage = $validated['per_page'] ?? 10;

        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($validated['keyword'] ?? null, function ($query, $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', "%{$keyword}%")
                        ->orWhere('author', 'like', "%{$keyword}%");
                });
            })
            ->when($validated['genre_id'] ?? null, function ($query, $genreId) {
                $query->whereHas('genres', function ($query) use ($genreId) {
                    $query->where('genres.id', $genreId);
                });
            })
            ->paginate($perPage);

        return BookResource::collection($books);
    }

    /**
     * 書籍詳細を取得する
     */
    public function show(Book $book)
    {
        $book->load([
            'genres',
            'reviews.user',
        ]);

        return new BookDetailResource($book);
    }
}
