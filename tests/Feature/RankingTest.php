<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 平均評価が高い書籍から順に表示される
     */
    public function test_ranking_orders_books_by_average_rating_descending(): void
    {
        $highRatingUser = User::factory()->create();
        $lowRatingUser = User::factory()->create();

        $highRatingBook = Book::factory()->create([
            'title' => '高評価の書籍',
        ]);

        $lowRatingBook = Book::factory()->create([
            'title' => '低評価の書籍',
        ]);

        Review::factory()->create([
            'user_id' => $highRatingUser->id,
            'book_id' => $highRatingBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $lowRatingUser->id,
            'book_id' => $lowRatingBook->id,
            'rating' => 3,
        ]);

        $response = $this->get(route('ranking.index'));

        $response
            ->assertOk()
            ->assertViewIs('ranking.index')
            ->assertSeeInOrder([
                '高評価の書籍',
                '低評価の書籍',
            ]);
    }

    /**
     * 平均評価が同じ場合はレビュー件数が多い書籍から順に表示される
     */
    public function test_ranking_orders_books_by_review_count_when_average_ratings_are_equal(): void
    {
        $manyReviewsBook = Book::factory()->create([
            'title' => 'レビュー件数が多い書籍',
        ]);

        $fewReviewsBook = Book::factory()->create([
            'title' => 'レビュー件数が少ない書籍',
        ]);

        $manyReviewUsers = User::factory()->count(4)->create();
        $fewReviewUsers = User::factory()->count(2)->create();

        // 平均評価4.0、レビュー4件
        foreach ([5, 4, 4, 3] as $index => $rating) {
            Review::factory()->create([
                'user_id' => $manyReviewUsers[$index]->id,
                'book_id' => $manyReviewsBook->id,
                'rating' => $rating,
            ]);
        }

        // 平均評価4.0、レビュー2件
        foreach ([5, 3] as $index => $rating) {
            Review::factory()->create([
                'user_id' => $fewReviewUsers[$index]->id,
                'book_id' => $fewReviewsBook->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->get(route('ranking.index'));

        $response
            ->assertOk()
            ->assertViewIs('ranking.index')
            ->assertSeeInOrder([
                'レビュー件数が多い書籍',
                'レビュー件数が少ない書籍',
            ]);
    }

    /**
     * 平均評価とレビュー件数が同じ場合は書籍IDの昇順で表示される
     */
    public function test_ranking_orders_books_by_id_when_average_and_review_count_are_equal(): void
    {
        $firstBook = Book::factory()->create([
            'title' => '先に登録された書籍',
        ]);

        $secondBook = Book::factory()->create([
            'title' => '後に登録された書籍',
        ]);

        $firstBookUser = User::factory()->create();
        $secondBookUser = User::factory()->create();

        Review::factory()->create([
            'user_id' => $firstBookUser->id,
            'book_id' => $firstBook->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'user_id' => $secondBookUser->id,
            'book_id' => $secondBook->id,
            'rating' => 4,
        ]);

        $response = $this->get(route('ranking.index'));

        $response
            ->assertOk()
            ->assertViewIs('ranking.index')
            ->assertSeeInOrder([
                '先に登録された書籍',
                '後に登録された書籍',
            ]);
    }

    /**
     * レビューが存在しない書籍はランキングに表示されない
     */
    public function test_ranking_does_not_display_books_without_reviews(): void
    {
        $reviewedBook = Book::factory()->create([
            'title' => 'レビューあり書籍',
        ]);

        Book::factory()->create([
            'title' => 'レビューなし書籍',
        ]);

        $user = User::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $reviewedBook->id,
            'rating' => 5,
        ]);

        $response = $this->get(route('ranking.index'));

        $response
            ->assertOk()
            ->assertSee('レビューあり書籍')
            ->assertDontSee('レビューなし書籍');
    }

    /**
     * レビューが存在しない場合は0件メッセージが表示される
     */
    public function test_ranking_page_displays_empty_message_when_no_reviews_exist(): void
    {
        $response = $this->get(route('ranking.index'));

        $response
            ->assertOk()
            ->assertViewIs('ranking.index')
            ->assertSee('まだレビューが投稿された書籍がありません。');
    }

    /**
     * ランキングは最大10冊まで表示される
     */
    public function test_ranking_displays_only_top_ten_books(): void
    {
        $books = Book::factory()->count(11)->create();

        foreach ($books as $index => $book) {
            Review::factory()->create([
                'user_id' => User::factory()->create()->id,
                'book_id' => $book->id,
                'rating' => 5,
                'comment' => "レビュー{$index}",
            ]);
        }

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $rankedBooks = $response->viewData('rankedBooks');

        $this->assertCount(10, $rankedBooks);
        $this->assertSame(
            $books->take(10)->pluck('id')->all(),
            $rankedBooks->pluck('id')->all()
        );
    }
}
