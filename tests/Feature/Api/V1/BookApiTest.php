<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Genre $genre;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * 書籍登録APIが固定でuser_id=1を使用するため、
         * テストでもIDが1のユーザーを用意する
         */
        $this->user = User::factory()->create([
            'id' => 1,
        ]);

        $this->genre = Genre::factory()->create([
            'name' => 'PHP',
        ]);
    }

    /**
     * 書籍一覧取得時に200とページネーション情報が返る
     */
    public function test_books_index_returns_200_with_pagination(): void
    {
        $books = Book::factory()
            ->count(11)
            ->for($this->user)
            ->create();

        foreach ($books as $book) {
            $book->genres()->attach($this->genre->id);
        }

        $response = $this->getJson('/api/v1/books');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ])
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10);

        $this->assertCount(10, $response->json('data'));
    }

    /**
     * 書籍一覧の各データが仕様どおりの構造で返る
     */
    public function test_books_index_returns_expected_book_structure(): void
    {
        $book = Book::factory()
            ->for($this->user)
            ->create([
                'title' => 'Laravel入門',
                'author' => '山田太郎',
                'isbn' => '9781234567890',
                'image_url' => 'https://example.com/laravel.jpg',
            ]);

        $book->genres()->attach($this->genre->id);

        $reviewer1 = User::factory()->create();
        $reviewer2 = User::factory()->create();

        Review::factory()->create([
            'user_id' => $reviewer1->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '分かりやすい内容でした。',
        ]);

        Review::factory()->create([
            'user_id' => $reviewer2->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても参考になりました。',
        ]);

        $response = $this->getJson('/api/v1/books');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'author',
                        'isbn',
                        'image_url',
                        'average_rating',
                        'review_count',
                        'genres' => [
                            '*' => [
                                'id',
                                'name',
                            ],
                        ],
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonPath('data.0.id', $book->id)
            ->assertJsonPath('data.0.title', 'Laravel入門')
            ->assertJsonPath('data.0.author', '山田太郎')
            ->assertJsonPath('data.0.isbn', '9781234567890')
            ->assertJsonPath(
                'data.0.image_url',
                'https://example.com/laravel.jpg'
            )
            ->assertJsonPath('data.0.average_rating', 4.5)
            ->assertJsonPath('data.0.review_count', 2)
            ->assertJsonPath('data.0.genres.0.id', $this->genre->id)
            ->assertJsonPath('data.0.genres.0.name', 'PHP');
    }

    /**
     * keywordで書籍タイトルの部分一致検索ができる
     */
    public function test_books_can_be_searched_by_title_keyword(): void
    {
        $matchedBook = Book::factory()
            ->for($this->user)
            ->create([
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell',
            ]);

        $notMatchedBook = Book::factory()
            ->for($this->user)
            ->create([
                'title' => '坊っちゃん',
                'author' => '夏目漱石',
            ]);

        $response = $this->getJson(
            '/api/v1/books?keyword=リーダブル'
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchedBook->id)
            ->assertJsonPath('data.0.title', 'リーダブルコード')
            ->assertJsonMissing([
                'id' => $notMatchedBook->id,
                'title' => '坊っちゃん',
            ]);
    }

    /**
     * keywordで著者名の部分一致検索ができる
     */
    public function test_books_can_be_searched_by_author_keyword(): void
    {
        $matchedBook = Book::factory()
            ->for($this->user)
            ->create([
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
            ]);

        $notMatchedBook = Book::factory()
            ->for($this->user)
            ->create([
                'title' => 'ノルウェイの森',
                'author' => '村上春樹',
            ]);

        $response = $this->getJson(
            '/api/v1/books?keyword=Robert'
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchedBook->id)
            ->assertJsonPath(
                'data.0.author',
                'Robert C. Martin'
            )
            ->assertJsonMissing([
                'id' => $notMatchedBook->id,
                'author' => '村上春樹',
            ]);
    }

    /**
     * genre_idで対象ジャンルの書籍を絞り込める
     */
    public function test_books_can_be_filtered_by_genre_id(): void
    {
        $technicalGenre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $novelGenre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $matchedBook = Book::factory()
            ->for($this->user)
            ->create([
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell',
            ]);

        $matchedBook->genres()->attach($technicalGenre->id);

        $notMatchedBook = Book::factory()
            ->for($this->user)
            ->create([
                'title' => '坊っちゃん',
                'author' => '夏目漱石',
            ]);

        $notMatchedBook->genres()->attach($novelGenre->id);

        $response = $this->getJson(
            "/api/v1/books?genre_id={$technicalGenre->id}"
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchedBook->id)
            ->assertJsonPath(
                'data.0.title',
                'リーダブルコード'
            )
            ->assertJsonPath(
                'data.0.genres.0.id',
                $technicalGenre->id
            )
            ->assertJsonPath(
                'data.0.genres.0.name',
                '技術書'
            )
            ->assertJsonMissing([
                'id' => $notMatchedBook->id,
                'title' => '坊っちゃん',
            ]);
    }

    /**
     * 存在しないgenre_idを指定した場合は422が返る
     */
    public function test_books_index_returns_422_when_genre_id_does_not_exist(): void
    {
        $response = $this->getJson(
            '/api/v1/books?genre_id=999999'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'genre_id',
            ])
            ->assertJsonPath(
                'errors.genre_id.0',
                '指定されたジャンルは存在しません。'
            );
    }

    /**
     * pageに0を指定した場合は422が返る
     */
    public function test_books_index_returns_422_when_page_is_less_than_one(): void
    {
        $response = $this->getJson(
            '/api/v1/books?page=0'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'page',
            ])
            ->assertJsonPath(
                'errors.page.0',
                'ページ番号は1以上で指定してください。'
            );
    }

    /**
     * per_pageに101を指定した場合は422が返る
     */
    public function test_books_index_returns_422_when_per_page_exceeds_maximum(): void
    {
        $response = $this->getJson(
            '/api/v1/books?per_page=101'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'per_page',
            ])
            ->assertJsonPath(
                'errors.per_page.0',
                '1ページあたりの件数は100件以下で指定してください。'
            );
    }

    /**
     * per_pageに整数以外を指定した場合は422が返る
     */
    public function test_books_index_returns_422_when_per_page_is_not_integer(): void
    {
        $response = $this->getJson(
            '/api/v1/books?per_page=abc'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'per_page',
            ])
            ->assertJsonPath(
                'errors.per_page.0',
                '1ページあたりの件数は整数で指定してください。'
            );
    }

    /**
     * 書籍詳細を取得できる
     */
    public function test_book_detail_can_be_retrieved(): void
    {
        $book = Book::factory()
            ->for($this->user)
            ->create([
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '9780132350884',
                'published_date' => '2008-08-01',
                'description' => '良いコードを書くための原則を解説した書籍です。',
                'image_url' => 'https://example.com/clean-code.jpg',
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
            'user_id' => $this->user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても参考になりました。',
        ]);

        $reviewUser = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        Review::factory()->create([
            'user_id' => $reviewUser->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '読みやすい内容でした。',
        ]);

        $response = $this->getJson(
            "/api/v1/books/{$book->id}"
        );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'author',
                    'isbn',
                    'published_date',
                    'image_url',
                    'description',
                    'genres' => [
                        '*' => [
                            'id',
                            'name',
                        ],
                    ],
                    'reviews' => [
                        '*' => [
                            'id',
                            'user_name',
                            'rating',
                            'comment',
                            'created_at',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', 'Clean Code')
            ->assertJsonPath('data.author', 'Robert C. Martin')
            ->assertJsonPath('data.isbn', '9780132350884')
            ->assertJsonPath('data.published_date', '2008-08-01')
            ->assertJsonPath(
                'data.description',
                '良いコードを書くための原則を解説した書籍です。'
            )
            ->assertJsonPath(
                'data.image_url',
                'https://example.com/clean-code.jpg'
            )
            ->assertJsonCount(2, 'data.genres')
            ->assertJsonCount(2, 'data.reviews')
            ->assertJsonFragment([
                'name' => '技術書',
            ])
            ->assertJsonFragment([
                'name' => 'プログラミング',
            ])
            ->assertJsonFragment([
                'user_name' => $this->user->name,
                'rating' => 5,
                'comment' => 'とても参考になりました。',
            ])
            ->assertJsonFragment([
                'user_name' => 'テストユーザー',
                'rating' => 4,
                'comment' => '読みやすい内容でした。',
            ]);
    }

    /**
     * 存在しない書籍IDを指定した場合は404が返る
     */
    public function test_book_detail_returns_404_when_book_does_not_exist(): void
    {
        $response = $this->getJson(
            '/api/v1/books/999999'
        );

        $response
            ->assertNotFound()
            ->assertJsonStructure([
                'message',
            ]);
    }

    /**
     * 書籍を登録できる
     */
    public function test_book_can_be_created(): void
    {
        $technicalGenre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $programmingGenre = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        $requestData = [
            'title' => '達人プログラマー',
            'author' => 'David Thomas',
            'isbn' => '9784274226298',
            'published_date' => '2020-11-21',
            'image_url' => 'https://example.com/pragmatic-programmer.jpg',
            'description' => 'プログラマーとして成長するための考え方を解説した書籍です。',
            'genre_ids' => [
                $technicalGenre->id,
                $programmingGenre->id,
            ],
        ];

        $response = $this->postJson(
            '/api/v1/books',
            $requestData
        );

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'author',
                    'isbn',
                    'published_date',
                    'image_url',
                    'description',
                    'genres' => [
                        '*' => [
                            'id',
                            'name',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.title', '達人プログラマー')
            ->assertJsonPath('data.author', 'David Thomas')
            ->assertJsonPath('data.isbn', '9784274226298')
            ->assertJsonPath('data.published_date', '2020-11-21')
            ->assertJsonPath(
                'data.image_url',
                'https://example.com/pragmatic-programmer.jpg'
            )
            ->assertJsonPath(
                'data.description',
                'プログラマーとして成長するための考え方を解説した書籍です。'
            )
            ->assertJsonCount(2, 'data.genres')
            ->assertJsonFragment([
                'id' => $technicalGenre->id,
                'name' => '技術書',
            ])
            ->assertJsonFragment([
                'id' => $programmingGenre->id,
                'name' => 'プログラミング',
            ]);

        $this->assertDatabaseHas('books', [
            'user_id' => $this->user->id,
            'title' => '達人プログラマー',
            'author' => 'David Thomas',
            'isbn' => '9784274226298',
            'published_date' => '2020-11-21',
            'image_url' => 'https://example.com/pragmatic-programmer.jpg',
            'description' => 'プログラマーとして成長するための考え方を解説した書籍です。',
        ]);

        $createdBook = Book::where('isbn', '9784274226298')->firstOrFail();

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $createdBook->id,
            'genre_id' => $technicalGenre->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $createdBook->id,
            'genre_id' => $programmingGenre->id,
        ]);
    }

    /**
     * 必須項目が未入力の場合は422が返る
     */
    public function test_book_creation_returns_422_when_required_fields_are_missing(): void
    {
        $bookCountBeforeRequest = Book::count();

        $response = $this->postJson(
            '/api/v1/books',
            []
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'genre_ids',
            ]);

        $this->assertDatabaseCount(
            'books',
            $bookCountBeforeRequest
        );
    }

    /**
     * 書籍を更新できる
     */
    public function test_book_can_be_updated(): void
    {
        $book = Book::factory()
            ->for($this->user)
            ->create([
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell',
                'isbn' => '9784873115658',
                'published_date' => '2012-06-23',
                'image_url' => 'https://example.com/readable-code.jpg',
                'description' => '変更前の説明です。',
            ]);

        $oldGenre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $newGenre = Genre::factory()->create([
            'name' => 'プログラミング',
        ]);

        $book->genres()->attach($oldGenre->id);

        $requestData = [
            'title' => 'リーダブルコード 改訂版',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_date' => '2026-01-01',
            'image_url' => 'https://example.com/readable-code-new.jpg',
            'description' => '変更後の説明です。',
            'genre_ids' => [
                $newGenre->id,
            ],
        ];

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $requestData
        );

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'author',
                    'isbn',
                    'published_date',
                    'image_url',
                    'description',
                    'genres' => [
                        '*' => [
                            'id',
                            'name',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.title', 'リーダブルコード 改訂版')
            ->assertJsonPath('data.author', 'Dustin Boswell')
            ->assertJsonPath('data.isbn', '9784873115658')
            ->assertJsonPath('data.published_date', '2026-01-01')
            ->assertJsonPath(
                'data.image_url',
                'https://example.com/readable-code-new.jpg'
            )
            ->assertJsonPath(
                'data.description',
                '変更後の説明です。'
            )
            ->assertJsonCount(1, 'data.genres')
            ->assertJsonFragment([
                'id' => $newGenre->id,
                'name' => 'プログラミング',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'リーダブルコード 改訂版',
            'author' => 'Dustin Boswell',
            'isbn' => '9784873115658',
            'published_date' => '2026-01-01',
            'image_url' => 'https://example.com/readable-code-new.jpg',
            'description' => '変更後の説明です。',
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
     * 他の書籍と重複するISBNを指定した場合は422が返る
     */
    public function test_book_update_returns_422_when_isbn_is_already_used(): void
    {
        $book = Book::factory()
            ->for($this->user)
            ->create([
                'isbn' => '9784873115658',
            ]);

        $otherBook = Book::factory()
            ->for($this->user)
            ->create([
                'isbn' => '9780132350884',
            ]);

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $requestData = [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => $otherBook->isbn,
            'published_date' => '2026-01-01',
            'image_url' => null,
            'description' => '更新後の説明です。',
            'genre_ids' => [
                $genre->id,
            ],
        ];

        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $requestData
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'isbn',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => '9784873115658',
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'isbn' => '9780132350884',
        ]);
    }

    /**
     * 存在しない書籍IDを更新しようとした場合は404が返る
     */
    public function test_book_update_returns_404_when_book_does_not_exist(): void
    {
        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $requestData = [
            'title' => '存在しない書籍',
            'author' => 'テスト著者',
            'isbn' => '9784873115658',
            'published_date' => '2026-01-01',
            'image_url' => null,
            'description' => 'テスト説明です。',
            'genre_ids' => [
                $genre->id,
            ],
        ];

        $response = $this->putJson(
            '/api/v1/books/999999',
            $requestData
        );

        $response->assertNotFound();
    }

    /**
     * 書籍を削除できる
     */
    public function test_book_can_be_deleted(): void
    {
        $book = Book::factory()
            ->for($this->user)
            ->create([
                'title' => '削除対象の書籍',
                'author' => 'テスト著者',
                'isbn' => '9784873119991',
            ]);

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->deleteJson(
            "/api/v1/books/{$book->id}"
        );

        $response
            ->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    /**
     * 存在しない書籍IDを削除しようとした場合は404が返る
     */
    public function test_book_delete_returns_404_when_book_does_not_exist(): void
    {
        $response = $this->deleteJson(
            '/api/v1/books/999999'
        );

        $response
            ->assertNotFound();
    }
}
