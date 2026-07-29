<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use App\Policies\ReviewPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 投稿者本人はレビューを更新できる
     */
    public function test_owner_can_update_review(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $policy = new ReviewPolicy;

        $this->assertTrue(
            $policy->update($user, $review)
        );
    }

    /**
     * 投稿者以外はレビューを更新できない
     */
    public function test_non_owner_cannot_update_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);

        $policy = new ReviewPolicy;

        $this->assertFalse(
            $policy->update($otherUser, $review)
        );
    }

    /**
     * 投稿者本人はレビューを削除できる
     */
    public function test_owner_can_delete_review(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $policy = new ReviewPolicy;

        $this->assertTrue(
            $policy->delete($user, $review)
        );
    }

    /**
     * 投稿者以外はレビューを削除できない
     */
    public function test_non_owner_cannot_delete_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);

        $policy = new ReviewPolicy;

        $this->assertFalse(
            $policy->delete($otherUser, $review)
        );
    }
}
