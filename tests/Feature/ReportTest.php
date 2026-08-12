<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証ユーザーはマイ読書レポートにアクセスできない
     */
    public function test_guest_cannot_access_my_reading_report(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 自分の総レビュー数・ユニーク読了冊数・平均評価が正しく表示される
     */
    public function test_report_displays_correct_summary_for_logged_in_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();
        $book3 = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $book3->id,
            'rating' => 1,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        // 同じ書籍の読了計画をもう1件作成
        // distinct(book_id) が効いて2冊のままになることを確認
        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $book3->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $stats = $response->viewData('stats');

        $this->assertSame(2, $stats['summary']['total_reviews']);
        $this->assertSame(2, $stats['summary']['books_read']);
        $this->assertEquals(4.0, $stats['summary']['average_rating']);
    }

    /**
     * レビューが0件の場合は平均評価に「-」が表示される
     */
    public function test_report_displays_dash_when_there_are_no_reviews(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $stats = $response->viewData('stats');

        $this->assertNull(
            $stats['summary']['average_rating']
        );

        $response->assertSee('-');
    }

    /**
     * 1〜5星の評価件数が正しく集計される
     */
    public function test_report_displays_correct_rating_distribution(): void
    {
        $user = User::factory()->create();

        $books = Book::factory()
            ->count(6)
            ->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $books[0]->id,
            'rating' => 1,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $books[1]->id,
            'rating' => 2,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $books[2]->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $books[3]->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $books[4]->id,
            'rating' => 5,
        ]);

        // 他ユーザーのレビューは集計対象外
        $otherUser = User::factory()->create();

        Review::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $books[5]->id,
            'rating' => 4,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $stats = $response->viewData('stats');

        $this->assertSame(
            [1, 1, 1, 0, 2],
            $stats['rating_distribution']->all()
        );
    }

    /**
     * 高評価書籍TOP5が評価順・同評価時はレビュー日時の新しい順で表示される
     */
    public function test_report_displays_top_five_high_rated_books_in_correct_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $books = Book::factory()
            ->count(8)
            ->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $books[0]->id,
            'rating' => 5,
            'created_at' => now()->subDays(2),
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $books[1]->id,
            'rating' => 5,
            'created_at' => now(),
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $books[2]->id,
            'rating' => 5,
            'created_at' => now()->subDay(),
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $books[3]->id,
            'rating' => 4,
            'created_at' => now(),
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $books[4]->id,
            'rating' => 4,
            'created_at' => now()->subDay(),
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $books[5]->id,
            'rating' => 4,
            'created_at' => now()->subDays(2),
        ]);

        // 評価3なので対象外
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $books[6]->id,
            'rating' => 3,
        ]);

        // 他ユーザーなので対象外
        Review::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $books[7]->id,
            'rating' => 5,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $stats = $response->viewData('stats');

        $topRatedBooks = $stats['top_rated_books'];

        $this->assertCount(5, $topRatedBooks);

        $this->assertSame([
            $books[1]->id,
            $books[2]->id,
            $books[0]->id,
            $books[3]->id,
            $books[4]->id,
        ], collect($topRatedBooks)->pluck('id')->all());

        $this->assertNotContains(
            $books[6]->id,
            collect($topRatedBooks)->pluck('id')->all()
        );

        $this->assertNotContains(
            $books[7]->id,
            collect($topRatedBooks)->pluck('id')->all()
        );
    }

    /**
     * ジャンル別TOP5が平均評価順・同評価時はレビュー件数順で表示される
     */
    public function test_report_displays_top_five_genres_in_correct_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $genre1 = Genre::factory()->create(['name' => 'ジャンル1']);
        $genre2 = Genre::factory()->create(['name' => 'ジャンル2']);
        $genre3 = Genre::factory()->create(['name' => 'ジャンル3']);
        $genre4 = Genre::factory()->create(['name' => 'ジャンル4']);
        $genre5 = Genre::factory()->create(['name' => 'ジャンル5']);
        $genre6 = Genre::factory()->create(['name' => 'ジャンル6']);

        // ジャンル1：平均5.0 / 1件
        $book1 = Book::factory()->create();
        $book1->genres()->attach($genre1->id);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 5,
        ]);

        // ジャンル2：平均4.0 / 3件
        foreach ([4, 4, 4] as $rating) {
            $book = Book::factory()->create();
            $book->genres()->attach($genre2->id);

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $rating,
            ]);
        }

        // ジャンル3：平均4.0 / 2件
        foreach ([4, 4] as $rating) {
            $book = Book::factory()->create();
            $book->genres()->attach($genre3->id);

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $rating,
            ]);
        }

        // ジャンル4：平均3.0 / 1件
        $book4 = Book::factory()->create();
        $book4->genres()->attach($genre4->id);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book4->id,
            'rating' => 3,
        ]);

        // ジャンル5：平均2.0 / 1件
        $book5 = Book::factory()->create();
        $book5->genres()->attach($genre5->id);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book5->id,
            'rating' => 2,
        ]);

        // ジャンル6：平均1.0 / 1件 → TOP5から外れる
        $book6 = Book::factory()->create();
        $book6->genres()->attach($genre6->id);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book6->id,
            'rating' => 1,
        ]);

        // 他ユーザーの高評価レビューは集計対象外
        $otherBook = Book::factory()->create();
        $otherBook->genres()->attach($genre6->id);

        Review::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
            'rating' => 5,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $stats = $response->viewData('stats');

        $genreRatings = $stats['genre_ratings'];

        $this->assertCount(5, $genreRatings);

        $this->assertSame([
            $genre1->id,
            $genre2->id,
            $genre3->id,
            $genre4->id,
            $genre5->id,
        ], collect($genreRatings)->pluck('id')->all());

        $genre2Result = collect($genreRatings)
            ->firstWhere('id', $genre2->id);

        $this->assertSame(3, $genre2Result['count']);
        $this->assertEquals(4.0, $genre2Result['average_rating']);

        $genre3Result = collect($genreRatings)
            ->firstWhere('id', $genre3->id);

        $this->assertSame(2, $genre3Result['count']);
        $this->assertEquals(4.0, $genre3Result['average_rating']);

        $this->assertNotContains(
            $genre6->id,
            collect($genreRatings)->pluck('id')->all()
        );
    }
}
