<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍詳細画面に書籍情報・ジャンル・レビューが表示される
     */
    public function test_book_detail_displays_book_and_review_information(): void
    {
        $bookOwner = User::factory()->create([
            'name' => '書籍登録ユーザー',
        ]);

        $reviewUser = User::factory()->create([
            'name' => 'レビューユーザー',
        ]);

        $book = Book::factory()
            ->for($bookOwner)
            ->create([
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '9780132350884',
                'published_date' => '2008-08-01',
                'image_url' => 'https://example.com/clean-code.jpg',
                'description' => '良いコードを書くための原則を解説した書籍です。',
            ]);

        $technicalGenre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $programmingGenre = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        $book->genres()->attach([
            $technicalGenre->id,
            $programmingGenre->id,
        ]);

        Review::factory()->create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても参考になりました。',
        ]);

        $response = $this->get(
            route('books.show', $book)
        );

        $response
            ->assertOk()
            ->assertViewIs('books.show')
            ->assertViewHas('book')
            ->assertSee('Clean Code')
            ->assertSee('Robert C. Martin')
            ->assertSee('9780132350884')
            ->assertSee('2008-08-01')
            ->assertSee('良いコードを書くための原則を解説した書籍です。')
            ->assertSee('技術書')
            ->assertSee('プログラミング')
            ->assertSee('レビューユーザー')
            ->assertSee('とても参考になりました。')
            ->assertSee('https://example.com/clean-code.jpg', false);
    }

    /**
     * 画像URLが登録されていない書籍では代替表示される
     */
    public function test_book_detail_can_be_displayed_without_image(): void
    {
        $book = Book::factory()->create([
            'title' => '坊っちゃん',
            'author' => '夏目漱石',
            'image_url' => null,
        ]);

        $response = $this->get(
            route('books.show', $book)
        );

        $response
            ->assertOk()
            ->assertViewIs('books.show')
            ->assertSee('坊っちゃん')
            ->assertSee('夏目漱石')
            ->assertSee('画像なし');
    }

    /**
     * 書籍詳細のレビューは新しい順に表示される
     */
    public function test_book_reviews_are_displayed_newest_first(): void
    {
        $book = Book::factory()->create();

        $oldUser = User::factory()->create();
        $newUser = User::factory()->create();

        Review::factory()->create([
            'user_id' => $oldUser->id,
            'book_id' => $book->id,
            'comment' => '古いレビュー',
            'created_at' => now()->subDay(),
        ]);

        Review::factory()->create([
            'user_id' => $newUser->id,
            'book_id' => $book->id,
            'comment' => '新しいレビュー',
            'created_at' => now(),
        ]);

        $response = $this->get(route('books.show', $book));

        $response
            ->assertOk()
            ->assertViewIs('books.show')
            ->assertSeeInOrder([
                '新しいレビュー',
                '古いレビュー',
            ]);
    }
}
