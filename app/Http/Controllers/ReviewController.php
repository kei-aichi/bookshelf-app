<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * レビューを登録する
     */
    public function store(StoreReviewRequest $request, Book $book): RedirectResponse
    {
        $alreadyReviewed = Review::query()
            ->where('user_id', $request->user()->id)
            ->where('book_id', $book->id)
            ->exists();

        if ($alreadyReviewed) {
            return redirect()
                ->route('books.show', $book)
                ->with('error', 'この書籍にはすでにレビューを投稿しています');
        }

        Review::create([
            'user_id' => $request->user()->id,
            'book_id' => $book->id,
            'rating' => $request->validated('rating'),
            'comment' => $request->validated('comment'),
        ]);

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューが投稿されました');
    }

    /**
     * レビュー編集画面を表示する
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /**
     * レビューを更新する
     */
    public function update(
        UpdateReviewRequest $request,
        Review $review
    ): RedirectResponse {
        $this->authorize('update', $review);

        $review->update($request->validated());

        return redirect()
            ->route('books.show', $review->book)
            ->with('success', 'レビューが更新されました');
    }

    /**
     * レビューを削除する
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $book = $review->book;

        $review->delete();

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューが削除されました');
    }
}
