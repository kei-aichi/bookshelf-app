<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookIndexRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧を表示する。
     */
    public function index(BookIndexRequest $request): View
    {
        $validated = $request->validated();

        $keyword = $validated['keyword'] ?? null;
        $genreId = $validated['genre'] ?? null;
        $sort = $validated['sort'] ?? 'newest';

        $books = Book::query()
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->when($keyword, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query
                        ->where('title', 'like', "%{$keyword}%")
                        ->orWhere('author', 'like', "%{$keyword}%");
                });
            })
            ->when($genreId, function ($query, int $genreId) {
                $query->whereHas('genres', function ($query) use ($genreId) {
                    $query->where('genres.id', $genreId);
                });
            })
            ->when($sort === 'newest', function ($query) {
                $query->latest();
            })
            ->when($sort === 'oldest', function ($query) {
                $query->oldest();
            })
            ->when($sort === 'title', function ($query) {
                $query->orderBy('title');
            })
            ->when($sort === 'rating', function ($query) {
                $query
                    ->orderByRaw('reviews_avg_rating IS NULL')
                    ->orderByDesc('reviews_avg_rating')
                    ->orderByDesc('id');
            })
            ->paginate(10)
            ->withQueryString();

        $genres = Genre::orderBy('name')->get();

        return view('books.index', compact(
            'books',
            'genres'
        ));
    }

    /**
     * 書籍詳細を表示する。
     */
    public function show(Book $book): View
    {
        $book->load([
            'genres',
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        return view('books.show', compact('book'));
    }

    /**
     * 書籍登録画面を表示する。
     */
    public function create(): View
    {
        $genres = Genre::orderBy('name')->get();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍を登録する。
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $book = DB::transaction(function () use ($validated, $request) {
            $book = Book::create([
                'user_id' => $request->user()->id,
                'title' => $validated['title'],
                'author' => $validated['author'],
                'isbn' => $validated['isbn'],
                'published_date' => $validated['published_date'],
                'description' => $validated['description'] ?? null,
                'image_url' => $validated['image_url'] ?? null,
            ]);

            $book->genres()->sync($validated['genres']);

            return $book;
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '新しい書籍が登録されました。');
    }

    /**
     * 書籍編集画面を表示する。
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $book->load('genres');

        $genres = Genre::orderBy('name')->get();

        return view('books.edit', compact(
            'book',
            'genres'
        ));
    }

    /**
     * 書籍を更新する。
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $book) {
            $book->update([
                'title' => $validated['title'],
                'author' => $validated['author'],
                'isbn' => $validated['isbn'],
                'published_date' => $validated['published_date'],
                'description' => $validated['description'] ?? null,
                'image_url' => $validated['image_url'] ?? null,
            ]);

            $book->genres()->sync($validated['genres']);
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍情報を更新しました。');
    }

    /**
     * 書籍を削除する。
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍が削除されました。');
    }

    /**
     * ISBNからGoogle Books APIで書籍情報を取得する。
     */
    public function searchIsbn(string $isbn): JsonResponse
    {
        if (! preg_match('/^\d{13}$/', $isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁の半角数字で入力してください。',
            ], 422);
        }

        try {
            $response = Http::timeout(10)->get(
                'https://www.googleapis.com/books/v1/volumes',
                [
                    'q' => "isbn:{$isbn}",
                    'key' => config('services.google_books.key'),
                ]
            );

            if ($response->failed()) {
                return response()->json([
                    'error' => '書籍情報の取得に失敗しました。',
                    'status' => $response->status(),
                    'details' => $response->json(),
                ], 502);
            }

            $items = $response->json('items');

            if (empty($items)) {
                return response()->json([
                    'error' => '該当する書籍が見つかりませんでした。',
                ], 404);
            }

            $volumeInfo = $items[0]['volumeInfo'] ?? [];

            return response()->json([
                'title' => $volumeInfo['title'] ?? null,
                'author' => collect($volumeInfo['authors'] ?? [])->join('、'),
                'isbn' => $isbn,
                'published_date' => $volumeInfo['publishedDate'] ?? null,
                'description' => $volumeInfo['description'] ?? null,
                'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => '通信エラーが発生しました。',
            ], 500);
        }
    }
}
