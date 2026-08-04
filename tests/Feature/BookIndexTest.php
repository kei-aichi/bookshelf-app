<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍一覧に書籍情報が表示され、10件でページネーションされる
     */
    public function test_book_index_displays_books_with_pagination(): void
    {
        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $reviewUser = User::factory()->create();

        $book = Book::factory()->create([
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell',
            'image_url' => 'https://example.com/readable-code.jpg',
        ]);

        $book->genres()->attach($genre->id);

        Review::factory()->create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '読みやすい本でした。',
        ]);

        Book::factory()
            ->count(10)
            ->create();

        $response = $this->get(route('books.index'));

        $response
            ->assertOk()
            ->assertViewIs('books.index')
            ->assertViewHas('books')
            ->assertSee('リーダブルコード')
            ->assertSee('Dustin Boswell')
            ->assertSee('技術書')
            ->assertSee('5')
            ->assertSee('https://example.com/readable-code.jpg', false);

        $books = $response->viewData('books');

        $this->assertCount(10, $books);
        $this->assertSame(11, $books->total());
        $this->assertTrue($books->hasPages());
    }

    /**
     * 書籍が0件の場合は0件メッセージが表示される
     */
    public function test_book_index_displays_empty_message_when_no_books_exist(): void
    {
        $response = $this->get(route('books.index'));

        $response
            ->assertOk()
            ->assertViewIs('books.index')
            ->assertSee('書籍が見つかりませんでした。');
    }
}
