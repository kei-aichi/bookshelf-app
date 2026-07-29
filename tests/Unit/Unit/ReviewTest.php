<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * レビューは複数のいいねを持つことができる
     */
    public function test_review_has_many_review_likes(): void
    {
        $reviewUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
        ]);

        $likeUsers = User::factory()
            ->count(2)
            ->create();

        foreach ($likeUsers as $user) {
            ReviewLike::create([
                'user_id' => $user->id,
                'review_id' => $review->id,
            ]);
        }

        $review->refresh();

        $this->assertCount(2, $review->reviewLikes);

        $this->assertContainsOnlyInstancesOf(
            ReviewLike::class,
            $review->reviewLikes
        );
    }
}
