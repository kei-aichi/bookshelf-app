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

    /**
     * タイトルのキーワードで書籍を検索できる
     */
    public function test_books_can_be_searched_by_title(): void
    {
        Book::factory()->create([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
        ]);

        Book::factory()->create([
            'title' => '嫌われる勇気',
            'author' => '岸見一郎',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => '猫',
        ]));

        $response
            ->assertOk()
            ->assertViewIs('books.index')
            ->assertSee('吾輩は猫である')
            ->assertDontSee('嫌われる勇気');
    }

    /**
     * 著者のキーワードで書籍を検索できる
     */
    public function test_books_can_be_searched_by_author(): void
    {
        Book::factory()->create([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
        ]);

        Book::factory()->create([
            'title' => '嫌われる勇気',
            'author' => '岸見一郎',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => '夏目',
        ]));

        $response
            ->assertOk()
            ->assertViewIs('books.index')
            ->assertSee('吾輩は猫である')
            ->assertDontSee('嫌われる勇気');
    }

    /**
     * ジャンルで書籍を絞り込める
     */
    public function test_books_can_be_filtered_by_genre(): void
    {
        $targetGenre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $otherGenre = Genre::factory()->create([
            'name' => 'ビジネス',
        ]);

        $targetBook = Book::factory()->create([
            'title' => '吾輩は猫である',
        ]);

        $otherBook = Book::factory()->create([
            'title' => '嫌われる勇気',
        ]);

        $targetBook->genres()->attach($targetGenre->id);
        $otherBook->genres()->attach($otherGenre->id);

        $response = $this->get(route('books.index', [
            'genre' => $targetGenre->id,
        ]));

        $response
            ->assertOk()
            ->assertViewIs('books.index')
            ->assertSee('吾輩は猫である')
            ->assertDontSee('嫌われる勇気');
    }

    /**
     * キーワードとジャンルを組み合わせて検索できる
     */
    public function test_books_can_be_filtered_with_keyword_and_genre(): void
    {
        $targetGenre = Genre::factory()->create(['name' => '小説']);
        $otherGenre = Genre::factory()->create(['name' => 'ビジネス']);

        $targetBook = Book::factory()->create([
            'title' => '猫の物語',
            'author' => '夏目太郎',
        ]);

        $otherGenreBook = Book::factory()->create([
            'title' => '猫の仕事術',
            'author' => '夏目次郎',
        ]);

        $nonKeywordBook = Book::factory()->create([
            'title' => '人を動かす',
            'author' => 'テスト著者',
        ]);

        $targetBook->genres()->attach($targetGenre->id);
        $otherGenreBook->genres()->attach($otherGenre->id);
        $nonKeywordBook->genres()->attach($targetGenre->id);

        $response = $this->get(route('books.index', [
            'keyword' => '猫',
            'genre' => $targetGenre->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('猫の物語')
            ->assertDontSee('猫の仕事術')
            ->assertDontSee('人を動かす');
    }

    /**
     * キーワードと並び順を組み合わせて検索できる
     */
    public function test_books_can_be_filtered_with_keyword_and_sort(): void
    {
        Book::factory()->create([
            'title' => '古い猫の本',
            'author' => '著者A',
            'created_at' => now()->subDay(),
        ]);

        Book::factory()->create([
            'title' => '新しい猫の本',
            'author' => '著者B',
            'created_at' => now(),
        ]);

        Book::factory()->create([
            'title' => '犬の本',
            'author' => '著者C',
            'created_at' => now()->addDay(),
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => '猫',
            'sort' => 'newest',
        ]));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '新しい猫の本',
                '古い猫の本',
            ])
            ->assertDontSee('犬の本');
    }

    /**
     * ジャンルと並び順を組み合わせて検索できる
     */
    public function test_books_can_be_filtered_with_genre_and_sort(): void
    {
        $targetGenre = Genre::factory()->create(['name' => '小説']);
        $otherGenre = Genre::factory()->create(['name' => 'ビジネス']);

        $olderBook = Book::factory()->create([
            'title' => '古い小説',
            'created_at' => now()->subDay(),
        ]);

        $newerBook = Book::factory()->create([
            'title' => '新しい小説',
            'created_at' => now(),
        ]);

        $otherBook = Book::factory()->create([
            'title' => 'ビジネス書',
            'created_at' => now()->addDay(),
        ]);

        $olderBook->genres()->attach($targetGenre->id);
        $newerBook->genres()->attach($targetGenre->id);
        $otherBook->genres()->attach($otherGenre->id);

        $response = $this->get(route('books.index', [
            'genre' => $targetGenre->id,
            'sort' => 'newest',
        ]));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '新しい小説',
                '古い小説',
            ])
            ->assertDontSee('ビジネス書');
    }

    /**
     * キーワード・ジャンル・並び順を組み合わせて検索できる
     */
    public function test_books_can_be_filtered_with_keyword_genre_and_sort(): void
    {
        $targetGenre = Genre::factory()->create(['name' => '小説']);
        $otherGenre = Genre::factory()->create(['name' => 'ビジネス']);

        $olderTargetBook = Book::factory()->create([
            'title' => '古い猫の小説',
            'author' => '夏目太郎',
            'created_at' => now()->subDay(),
        ]);

        $newerTargetBook = Book::factory()->create([
            'title' => '新しい猫の小説',
            'author' => '夏目次郎',
            'created_at' => now(),
        ]);

        $otherGenreBook = Book::factory()->create([
            'title' => '猫の仕事術',
            'author' => '夏目三郎',
            'created_at' => now()->addDay(),
        ]);

        $nonKeywordBook = Book::factory()->create([
            'title' => '人を動かす',
            'author' => 'テスト著者',
        ]);

        $olderTargetBook->genres()->attach($targetGenre->id);
        $newerTargetBook->genres()->attach($targetGenre->id);
        $otherGenreBook->genres()->attach($otherGenre->id);
        $nonKeywordBook->genres()->attach($targetGenre->id);

        $response = $this->get(route('books.index', [
            'keyword' => '猫',
            'genre' => $targetGenre->id,
            'sort' => 'newest',
        ]));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '新しい猫の小説',
                '古い猫の小説',
            ])
            ->assertDontSee('猫の仕事術')
            ->assertDontSee('人を動かす');
    }

    /**
     * 書籍を新しい順に並び替えられる
     */
    public function test_books_can_be_sorted_by_newest(): void
    {
        Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => now()->subDay(),
        ]);

        Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => now(),
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'newest',
        ]));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '新しい書籍',
                '古い書籍',
            ]);
    }

    /**
     * 書籍を古い順に並び替えられる
     */
    public function test_books_can_be_sorted_by_oldest(): void
    {
        Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => now()->subDay(),
        ]);

        Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => now(),
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'oldest',
        ]));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '古い書籍',
                '新しい書籍',
            ]);
    }

    /**
     * 書籍をタイトル順に並び替えられる
     */
    public function test_books_can_be_sorted_by_title(): void
    {
        Book::factory()->create([
            'title' => 'CCC',
        ]);

        Book::factory()->create([
            'title' => 'AAA',
        ]);

        Book::factory()->create([
            'title' => 'BBB',
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'title',
        ]));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'AAA',
                'BBB',
                'CCC',
            ]);
    }

    /**
     * 書籍を平均評価の高い順に並び替え、レビューなしは最後に表示する
     */
    public function test_books_can_be_sorted_by_rating(): void
    {
        $highRatedBook = Book::factory()->create([
            'title' => '高評価書籍',
        ]);

        $lowRatedBook = Book::factory()->create([
            'title' => '低評価書籍',
        ]);

        $noReviewBook = Book::factory()->create([
            'title' => 'レビューなし書籍',
        ]);

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'rating',
        ]));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '高評価書籍',
                '低評価書籍',
                'レビューなし書籍',
            ]);
    }

    /**
     * ページネーション後も検索条件が保持される
     */
    public function test_search_conditions_are_preserved_in_pagination(): void
    {
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $books = Book::factory()
            ->count(11)
            ->create([
                'author' => 'テスト著者',
            ]);

        foreach ($books as $book) {
            $book->genres()->attach($genre->id);
        }

        $response = $this->get(route('books.index', [
            'keyword' => 'テスト著者',
            'genre' => $genre->id,
            'sort' => 'oldest',
        ]));

        $response->assertOk();

        $books = $response->viewData('books');

        $this->assertTrue($books->hasPages());

        $nextPageUrl = $books->nextPageUrl();

        $this->assertStringContainsString(
            'keyword='.urlencode('テスト著者'),
            $nextPageUrl
        );

        $this->assertStringContainsString(
            'genre='.$genre->id,
            $nextPageUrl
        );

        $this->assertStringContainsString(
            'sort=oldest',
            $nextPageUrl
        );
    }

    /**
     * 検索条件を解除すると全書籍が表示される
     */
    public function test_books_are_all_displayed_when_search_conditions_are_reset(): void
    {
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $matchingBook = Book::factory()->create([
            'title' => '猫の物語',
        ]);

        $otherBook = Book::factory()->create([
            'title' => '人を動かす',
        ]);

        $matchingBook->genres()->attach($genre->id);

        // 検索条件あり
        $filteredResponse = $this->get(route('books.index', [
            'keyword' => '猫',
            'genre' => $genre->id,
        ]));

        $filteredResponse
            ->assertOk()
            ->assertSee('猫の物語')
            ->assertDontSee('人を動かす');

        // 検索条件をリセット
        $resetResponse = $this->get(route('books.index'));

        $resetResponse
            ->assertOk()
            ->assertSee('猫の物語')
            ->assertSee('人を動かす');
    }
}
