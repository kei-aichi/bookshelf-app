<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザーは書籍をお気に入りに追加できる
     */
    public function test_authenticated_user_can_add_book_to_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    /**
     * お気に入り一覧にはログインユーザーのお気に入りだけが表示される
     */
    public function test_favorite_index_displays_only_authenticated_users_favorites(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $favoriteBook = Book::factory()->create([
            'title' => '自分のお気に入り書籍',
        ]);

        $otherUsersFavoriteBook = Book::factory()->create([
            'title' => '他人のお気に入り書籍',
        ]);

        Favorite::factory()->create([
            'user_id' => $user->id,
            'book_id' => $favoriteBook->id,
        ]);

        Favorite::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $otherUsersFavoriteBook->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        $response
            ->assertOk()
            ->assertViewIs('favorites.index')
            ->assertSee('自分のお気に入り書籍')
            ->assertDontSee('他人のお気に入り書籍');
    }

    /**
     * お気に入り一覧は10件ごとにページネーションされる
     */
    public function test_favorite_index_is_paginated_by_ten(): void
    {
        $user = User::factory()->create();

        $books = Book::factory()->count(11)->create();

        foreach ($books as $book) {
            Favorite::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();

        $paginatedBooks = $response->viewData('books');

        $this->assertCount(10, $paginatedBooks);
        $this->assertSame(11, $paginatedBooks->total());
        $this->assertTrue($paginatedBooks->hasPages());
    }

    /**
     * ログインユーザーはお気に入りを解除できる
     */
    public function test_authenticated_user_can_remove_book_from_favorites(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => 'お気に入り解除対象',
        ]);

        Favorite::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    /**
     * お気に入りが0件の場合はメッセージが表示される
     */
    public function test_favorite_index_displays_empty_message_when_no_favorites_exist(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        $response
            ->assertOk()
            ->assertViewIs('favorites.index')
            ->assertSee('お気に入りに登録された書籍はありません。');
    }

    /**
     * ゲストユーザーはお気に入り操作ができず、ログイン画面へリダイレクトされる
     */
    public function test_guest_cannot_toggle_favorite(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('favorites', 0);
    }
}
