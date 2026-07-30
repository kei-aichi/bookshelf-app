<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScreenAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストは書籍一覧へアクセスできる
     */
    public function test_guest_can_access_book_index(): void
    {
        Book::factory()->count(3)->create();

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertViewIs('books.index');
    }

    /**
     * ゲストは書籍詳細へアクセスできる
     */
    public function test_guest_can_access_book_detail(): void
    {
        $book = Book::factory()->create();

        $response = $this->get("/books/{$book->id}");

        $response
            ->assertOk()
            ->assertViewIs('books.show');
    }

    /**
     * 未認証ユーザーは認証必須画面へアクセスすると
     * ログイン画面へリダイレクトされる
     */
    public function test_guest_is_redirected_to_login_when_accessing_protected_pages(): void
    {
        $book = Book::factory()->create();

        $genre = Genre::factory()->create();

        $this->get('/books/create')
            ->assertRedirect('/login');

        $this->get('/favorites')
            ->assertRedirect('/login');

        $this->get('/genres')
            ->assertRedirect('/login');

        $this->get("/books/{$book->id}/edit")
            ->assertRedirect('/login');

        $this->get("/genres/{$genre->id}/edit")
            ->assertRedirect('/login');
    }
}
