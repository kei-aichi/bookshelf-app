<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザーはレビューを投稿できる
     */
    public function test_authenticated_user_can_create_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), [
                'book_id' => $book->id,
                'rating' => 5,
                'comment' => 'とても参考になる書籍でした。',
            ]);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても参考になる書籍でした。',
        ]);
    }

    /**
     * バリデーションエラーの場合はレビューを投稿できない
     */
    public function test_review_cannot_be_created_with_invalid_data(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.store', $book), [
                'book_id' => $book->id,
                'rating' => 6,
                'comment' => str_repeat('あ', 1001),
            ]);

        $response
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHasErrors([
                'rating',
                'comment',
            ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    /**
     * レビュー投稿者は編集画面を表示できる
     */
    public function test_review_owner_can_access_edit_page(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '編集前のコメントです。',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reviews.edit', $review));

        $response
            ->assertOk()
            ->assertViewIs('reviews.edit')
            ->assertViewHas('review')
            ->assertSee('編集前のコメントです。');
    }

    /**
     * レビュー投稿者はレビューを更新できる
     */
    public function test_review_owner_can_update_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '更新前のコメントです。',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('reviews.update', $review), [
                'rating' => 5,
                'comment' => '更新後のコメントです。',
            ]);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '更新後のコメントです。',
        ]);
    }

    /**
     * レビュー投稿者は削除でき、他人は更新・削除できない
     */
    public function test_review_authorization_is_applied(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $reviewForUnauthorizedTest = Review::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '所有者のレビューです。',
        ]);

        $updateResponse = $this
            ->actingAs($otherUser)
            ->put(route('reviews.update', $reviewForUnauthorizedTest), [
                'rating' => 1,
                'comment' => '不正に更新したコメントです。',
            ]);

        $updateResponse->assertForbidden();

        $deleteResponse = $this
            ->actingAs($otherUser)
            ->delete(route('reviews.destroy', $reviewForUnauthorizedTest));

        $deleteResponse->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $reviewForUnauthorizedTest->id,
            'rating' => 4,
            'comment' => '所有者のレビューです。',
        ]);

        $reviewForDeleteTest = Review::factory()->create([
            'user_id' => $owner->id,
            'book_id' => Book::factory()->create()->id,
            'rating' => 3,
            'comment' => '削除対象のレビューです。',
        ]);

        $deleteResponse = $this
            ->actingAs($owner)
            ->delete(route('reviews.destroy', $reviewForDeleteTest));

        $deleteResponse->assertRedirect(
            route('books.show', $reviewForDeleteTest->book_id)
        );

        $this->assertDatabaseMissing('reviews', [
            'id' => $reviewForDeleteTest->id,
        ]);
    }

    /**
     * 同じユーザーは同じ書籍に2件目のレビューを投稿できない
     */
    public function test_user_cannot_create_duplicate_review_for_same_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '1件目のレビューです。',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('reviews.store', $book), [
                'book_id' => $book->id,
                'rating' => 5,
                'comment' => '2件目のレビューです。',
            ]);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseCount('reviews', 1);

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'comment' => '2件目のレビューです。',
        ]);
    }

    /**
     * レビューを削除すると関連するレビューいいねも削除される
     */
    public function test_deleting_review_cascades_review_likes(): void
    {
        $reviewOwner = User::factory()->create();
        $likeUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $reviewOwner->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '削除対象のレビューです。',
        ]);

        $reviewLike = $review->reviewLikes()->create([
            'user_id' => $likeUser->id,
        ]);

        $response = $this
            ->actingAs($reviewOwner)
            ->delete(route('reviews.destroy', $review));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'id' => $reviewLike->id,
        ]);
    }
}
