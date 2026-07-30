<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザーが書籍を登録できる
     */
    public function test_authenticated_user_can_create_book(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), [
                'title' => 'Laravel入門',
                'author' => '山田太郎',
                'isbn' => '9781234567897',
                'published_date' => '2026-07-01',
                'image_url' => 'https://example.com/laravel.jpg',
                'description' => 'Laravelの基礎を学べる書籍です。',
                'genres' => [$genre->id],
            ]);

        $book = Book::where('isbn', '9781234567897')->first();

        $this->assertNotNull($book);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '9781234567897',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    /**
     * バリデーションエラーの場合は書籍を登録できない
     */
    public function test_book_cannot_be_created_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('books.create'))
            ->post(route('books.store'), [
                'title' => '',
                'author' => '',
                'isbn' => '123',
                'published_at' => 'invalid-date',
                'image_url' => 'invalid-url',
                'description' => null,
                'genres' => [],
            ]);

        $response
            ->assertRedirect(route('books.create'))
            ->assertSessionHasErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'image_url',
                'genres',
            ]);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 書籍の所有者は編集画面を表示できる
     */
    public function test_book_owner_can_access_edit_page(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '編集前の書籍',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('books.edit', $book));

        $response
            ->assertOk()
            ->assertViewIs('books.edit')
            ->assertViewHas('book')
            ->assertSee('編集前の書籍');
    }

    /**
     * 書籍の所有者は書籍を更新できる
     */
    public function test_book_owner_can_update_book(): void
    {
        $user = User::factory()->create();

        $oldGenre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $newGenre = Genre::factory()->create([
            'name' => 'ビジネス',
        ]);

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn' => '9781111111111',
        ]);

        $book->genres()->attach($oldGenre->id);

        $response = $this
            ->actingAs($user)
            ->put(route('books.update', $book), [
                'title' => '更新後タイトル',
                'author' => '更新後著者',
                'isbn' => '9782222222222',
                'published_date' => '2026-07-15',
                'image_url' => 'https://example.com/updated.jpg',
                'description' => '更新後の説明文です。',
                'genres' => [$newGenre->id],
            ]);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '9782222222222',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);
    }

    /**
     * 他人が登録した書籍は更新できない
     */
    public function test_user_cannot_update_another_users_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '所有者の書籍',
            'isbn' => '9783333333333',
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->put(route('books.update', $book), [
                'title' => '不正に変更したタイトル',
                'author' => '不正な著者',
                'isbn' => '9784444444444',
                'published_date' => '2026-07-20',
                'image_url' => null,
                'description' => null,
                'genres' => [$genre->id],
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '所有者の書籍',
            'isbn' => '9783333333333',
        ]);
    }

    /**
     * 書籍の所有者は書籍を削除でき、
     * 関連するレビュー・レビューいいね・お気に入りも削除される
     */
    public function test_book_owner_can_delete_book_and_related_data(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $reviewUser = User::factory()->create();
        $reviewLikeUser = User::factory()->create();
        $favoriteUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
        ]);

        $reviewLike = ReviewLike::factory()->create([
            'user_id' => $reviewLikeUser->id,
            'review_id' => $review->id,
        ]);

        $favorite = Favorite::factory()->create([
            'user_id' => $favoriteUser->id,
            'book_id' => $book->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'id' => $reviewLike->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'id' => $favorite->id,
        ]);
    }

    /**
     * 他人が登録した書籍は削除できない
     */
    public function test_user_cannot_delete_another_users_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->delete(route('books.destroy', $book));

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    /**
     * ISBNを変更せずに書籍を更新できる
     */
    public function test_book_owner_can_update_book_without_changing_isbn(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn' => '9781234567897',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this
            ->actingAs($user)
            ->put(route('books.update', $book), [
                'title' => '更新後タイトル',
                'author' => '更新後著者',
                'isbn' => '9781234567897',
                'published_date' => '2026-07-15',
                'image_url' => null,
                'description' => 'ISBNは変更せずに更新しました。',
                'genres' => [$genre->id],
            ]);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '9781234567897',
        ]);
    }

    /**
     * 他ユーザーは書籍編集画面を表示できない
     */
    public function test_user_cannot_access_another_users_book_edit_page(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->for($owner)->create();

        $this->actingAs($otherUser)
            ->get(route('books.edit', $book))
            ->assertForbidden();
    }
}
