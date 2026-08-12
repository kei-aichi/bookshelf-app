<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookIsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 13桁ISBNからGoogle Books APIの書籍情報を取得できる
     */
    public function test_book_information_can_be_fetched_by_isbn(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => '吾輩は猫である',
                            'authors' => ['夏目漱石'],
                            'publishedDate' => '2011-11-01',
                            'description' => 'テスト用の説明です。',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/cat.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $isbn = '9784167158057';

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.search-isbn', $isbn));

        $response
            ->assertOk()
            ->assertJson([
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'isbn' => $isbn,
                'published_date' => '2011-11-01',
                'description' => 'テスト用の説明です。',
                'image_url' => 'https://example.com/cat.jpg',
            ]);

        Http::assertSent(function ($request) use ($isbn) {
            return str_contains(
                $request->url(),
                'www.googleapis.com/books/v1/volumes'
            )
                && $request['q'] === "isbn:{$isbn}";
        });
    }

    /**
     * ISBNが13桁未満の場合は422を返す
     */
    public function test_isbn_search_returns_422_when_isbn_is_too_short(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.search-isbn', '123456789012'));

        $response
            ->assertUnprocessable()
            ->assertJson([
                'error' => 'ISBNは13桁の半角数字で入力してください。',
            ]);
    }

    /**
     * ISBNが13桁を超える場合は422を返す
     */
    public function test_isbn_search_returns_422_when_isbn_is_too_long(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.search-isbn', '12345678901234'));

        $response
            ->assertUnprocessable()
            ->assertJson([
                'error' => 'ISBNは13桁の半角数字で入力してください。',
            ]);
    }

    /**
     * ISBNに半角数字以外が含まれる場合は422を返す
     */
    public function test_isbn_search_returns_422_when_isbn_contains_non_numeric_characters(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.search-isbn', '978416715805A'));

        $response
            ->assertUnprocessable()
            ->assertJson([
                'error' => 'ISBNは13桁の半角数字で入力してください。',
            ]);
    }

    /**
     * Google Books APIで該当書籍が見つからない場合は404を返す
     */
    public function test_isbn_search_returns_404_when_book_is_not_found(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
            ], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.search-isbn', '9784167158057'));

        $response
            ->assertNotFound()
            ->assertJson([
                'error' => '該当する書籍が見つかりませんでした。',
            ]);
    }

    /**
     * Google Books APIがエラーを返した場合は502を返す
     */
    public function test_isbn_search_returns_502_when_google_books_api_fails(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'error' => [
                    'message' => 'Too Many Requests',
                ],
            ], 429),
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.search-isbn', '9784167158057'));

        $response
            ->assertStatus(502)
            ->assertJson([
                'error' => '書籍情報の取得に失敗しました。',
                'status' => 429,
            ]);
    }

    /**
     * Google Books APIとの通信で例外が発生した場合は500を返す
     */
    public function test_isbn_search_returns_500_when_connection_error_occurs(): void
    {
        $user = User::factory()->create();

        Http::fake(function () {
            throw new \RuntimeException('Connection failed');
        });

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.search-isbn', '9784167158057'));

        $response
            ->assertInternalServerError()
            ->assertJson([
                'error' => '通信エラーが発生しました。',
            ]);
    }

    /**
     * Google Books APIが不正なJSON構造を返した場合は502を返す
     */
    public function test_isbn_search_returns_502_when_google_books_api_returns_invalid_json_structure(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'unexpected' => 'invalid-data',
                    ],
                ],
            ], 200),
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('books.search-isbn', '9784167158057'));

        $response
            ->assertStatus(502)
            ->assertJson([
                'error' => '書籍情報の形式が不正です。',
            ]);
    }
}
