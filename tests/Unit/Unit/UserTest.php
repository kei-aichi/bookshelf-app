<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ユーザーは複数の書籍を登録できる
     */
    public function test_user_has_many_books(): void
    {
        $user = User::factory()->create();

        Book::factory()
            ->count(2)
            ->for($user)
            ->create();

        $user->refresh();

        $this->assertCount(2, $user->books);

        $this->assertContainsOnlyInstancesOf(
            Book::class,
            $user->books
        );
    }

    /**
     * ユーザーは複数のレビューを投稿できる
     */
    public function test_user_has_many_reviews(): void
    {
        $user = User::factory()->create();

        $books = Book::factory()
            ->count(2)
            ->create();

        foreach ($books as $book) {
            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]);
        }

        $user->refresh();

        $this->assertCount(2, $user->reviews);

        $this->assertContainsOnlyInstancesOf(
            Review::class,
            $user->reviews
        );
    }

    /**
     * ユーザーは複数の書籍をお気に入り登録できる
     */
    public function test_user_has_many_favorites(): void
    {
        $user = User::factory()->create();

        $books = Book::factory()
            ->count(2)
            ->create();

        foreach ($books as $book) {
            Favorite::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]);
        }

        $user->refresh();

        $this->assertCount(2, $user->favorites);

        $this->assertContainsOnlyInstancesOf(
            Favorite::class,
            $user->favorites
        );
    }

    /**
     * ユーザーは複数のレビューにいいねできる
     */
    public function test_user_has_many_review_likes(): void
    {
        $user = User::factory()->create();

        $reviewUsers = User::factory()
            ->count(2)
            ->create();

        $books = Book::factory()
            ->count(2)
            ->create();

        foreach ($books as $index => $book) {
            $review = Review::factory()->create([
                'user_id' => $reviewUsers[$index]->id,
                'book_id' => $book->id,
            ]);

            ReviewLike::create([
                'user_id' => $user->id,
                'review_id' => $review->id,
            ]);
        }

        $user->refresh();

        $this->assertCount(2, $user->reviewLikes);

        $this->assertContainsOnlyInstancesOf(
            ReviewLike::class,
            $user->reviewLikes
        );
    }
}
