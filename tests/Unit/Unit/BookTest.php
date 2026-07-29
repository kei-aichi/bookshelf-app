<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍は登録ユーザーに所属する
     */
    public function test_book_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()
            ->for($user)
            ->create();

        $this->assertTrue(
            $book->user->is($user)
        );

        $this->assertInstanceOf(
            User::class,
            $book->user
        );
    }

    /**
     * 書籍は複数のジャンルを持つことができる
     */
    public function test_book_belongs_to_many_genres(): void
    {
        $book = Book::factory()->create();

        $genres = Genre::factory()
            ->count(2)
            ->create();

        $book->genres()->attach($genres->pluck('id'));

        $book->refresh();

        $this->assertCount(
            2,
            $book->genres
        );

        $this->assertTrue(
            $book->genres->contains($genres[0])
        );

        $this->assertTrue(
            $book->genres->contains($genres[1])
        );
    }

    /**
     * 書籍は複数のレビューを持つことができる
     */
    public function test_book_has_many_reviews(): void
    {
        $book = Book::factory()->create();

        $users = User::factory()
            ->count(2)
            ->create();

        foreach ($users as $user) {
            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]);
        }

        $book->refresh();

        $this->assertCount(
            2,
            $book->reviews
        );

        $this->assertContainsOnlyInstancesOf(
            Review::class,
            $book->reviews
        );
    }

    /**
     * 書籍は複数のお気に入り情報を持つことができる
     */
    public function test_book_has_many_favorites(): void
    {
        $book = Book::factory()->create();

        $users = User::factory()
            ->count(2)
            ->create();

        foreach ($users as $user) {
            Favorite::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]);
        }

        $book->refresh();

        $this->assertCount(
            2,
            $book->favorites
        );

        $this->assertContainsOnlyInstancesOf(
            Favorite::class,
            $book->favorites
        );
    }
}
