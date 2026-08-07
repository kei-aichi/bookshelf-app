<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * マイ読書レポート画面を表示する。
     */
    public function index(): View
    {
        $userId = Auth::id();

        $reviews = Review::query()
            ->where('user_id', $userId)
            ->with([
                'book:id,title,author',
                'book.genres:id,name',
            ])
            ->get();

        $totalReviews = $reviews->count();

        $booksRead = ReadingPlan::query()
            ->where('user_id', $userId)
            ->where('status', ReadingPlanStatus::Completed->value)
            ->distinct()
            ->count('book_id');

        $averageRating = $reviews->avg('rating');

        $ratingDistribution = collect(range(1, 5))
            ->map(
                fn (int $rating): int => $reviews
                    ->where('rating', $rating)
                    ->count()
            )
            ->values();

        $topRatedBooks = $reviews
            ->where('rating', '>=', 4)
            ->sortByDesc([
                ['rating', 'desc'],
                ['created_at', 'desc'],
            ])
            ->take(5)
            ->map(fn (Review $review): array => [
                'id' => $review->book->id,
                'title' => $review->book->title,
                'author' => $review->book->author,
                'rating' => $review->rating,
            ])
            ->values()
            ->all();

        $genreRatings = $reviews
            ->flatMap(function (Review $review) {
                return $review->book->genres->map(
                    fn ($genre): array => [
                        'id' => $genre->id,
                        'name' => $genre->name,
                        'rating' => $review->rating,
                    ]
                );
            })
            ->groupBy('id')
            ->map(function ($genreReviews) {
                return [
                    'id' => $genreReviews->first()['id'],
                    'name' => $genreReviews->first()['name'],
                    'count' => $genreReviews->count(),
                    'average_rating' => $genreReviews->avg('rating'),
                ];
            })
            ->sort(function (array $a, array $b): int {
                $averageComparison = $b['average_rating'] <=> $a['average_rating'];

                if ($averageComparison !== 0) {
                    return $averageComparison;
                }

                return $b['count'] <=> $a['count'];
            })
            ->take(5)
            ->values()
            ->all();

        $stats = [
            'summary' => [
                'total_reviews' => $totalReviews,
                'books_read' => $booksRead,
                'average_rating' => $averageRating,
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];

        return view('reports.index', compact('stats'));
    }
}
