<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BookIndexRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\BookCrudResource;
use App\Http\Resources\BookDetailResource;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    /**
     * 書籍一覧を取得する
     */
    public function index(BookIndexRequest $request): AnonymousResourceCollection
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
    public function show(Book $book): BookDetailResource
    {
        $book->load([
            'genres',
            'reviews.user',
        ]);

        return new BookDetailResource($book);
    }

    /**
     * 書籍を登録する
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $userId = 1;
        $user = User::find($userId);

        if (! $user) {
            return response()->json([
                'message' => '指定された登録者は存在しません。',
                'errors' => [
                    'user_id' => [
                        '指定された登録者は存在しません。',
                    ],
                ],
            ], 422);
        }

        $book = $user->books()->create([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $book->genres()->sync($validated['genre_ids']);

        $book->load('genres');

        return response()->json([
            'message' => '書籍を登録しました。',
            'data' => new BookCrudResource($book),
        ], 201);
    }

    /**
     * 書籍を更新する
     */
    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $validated = $request->validated();

        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $book->genres()->sync($validated['genre_ids']);

        $book->load('genres');

        return response()->json([
            'message' => '書籍を更新しました。',
            'data' => new BookCrudResource($book),
        ], 200);
    }

    /**
     * 書籍を削除する
     */
    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json(null, 204);
    }
}
