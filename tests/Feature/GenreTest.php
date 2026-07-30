<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ジャンル一覧にジャンル名と紐づく書籍数が表示される
     */
    public function test_genre_index_displays_genres_and_book_counts(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $books = Book::factory()->count(2)->create();

        $genre->books()->attach($books->pluck('id'));

        $response = $this
            ->actingAs($user)
            ->get(route('genres.index'));

        $response
            ->assertOk()
            ->assertViewIs('genres.index')
            ->assertSee('技術書')
            ->assertSee('2');
    }

    /**
     * ジャンル詳細には対象ジャンルの書籍だけが10件ずつ表示される
     */
    public function test_genre_detail_displays_only_related_books_with_pagination(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $otherGenre = Genre::factory()->create([
            'name' => 'ビジネス',
        ]);

        $relatedBooks = Book::factory()->count(11)->create();

        $otherBook = Book::factory()->create([
            'title' => '対象外の書籍',
        ]);

        $genre->books()->attach($relatedBooks->pluck('id'));
        $otherGenre->books()->attach($otherBook->id);

        $response = $this
            ->actingAs($user)
            ->get(route('genres.show', $genre));

        $response
            ->assertOk()
            ->assertViewIs('genres.show')
            ->assertViewHas('genre')
            ->assertViewHas('books')
            ->assertSee('小説')
            ->assertDontSee('対象外の書籍');

        $books = $response->viewData('books');

        $this->assertCount(10, $books);
        $this->assertSame(11, $books->total());
        $this->assertTrue($books->hasPages());
    }

    /**
     * ログインユーザーはジャンルを登録できる
     */
    public function test_authenticated_user_can_create_genre(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('genres.store'), [
                'name' => 'プログラミング',
            ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'name' => 'プログラミング',
        ]);
    }

    /**
     * ログインユーザーはジャンルを更新できる
     */
    public function test_authenticated_user_can_update_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '更新前ジャンル',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '更新後ジャンル',
            ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新後ジャンル',
        ]);

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
            'name' => '更新前ジャンル',
        ]);
    }

    /**
     * 未入力・31文字以上・重複したジャンル名は登録できない
     */
    public function test_genre_validation_rejects_invalid_names(): void
    {
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => '既存ジャンル',
        ]);

        // 未入力
        $emptyResponse = $this
            ->actingAs($user)
            ->from(route('genres.create'))
            ->post(route('genres.store'), [
                'name' => '',
            ]);

        $emptyResponse
            ->assertRedirect(route('genres.create'))
            ->assertSessionHasErrors('name');

        // 31文字以上
        $tooLongResponse = $this
            ->actingAs($user)
            ->from(route('genres.create'))
            ->post(route('genres.store'), [
                'name' => str_repeat('あ', 31),
            ]);

        $tooLongResponse
            ->assertRedirect(route('genres.create'))
            ->assertSessionHasErrors('name');

        // 重複
        $duplicateResponse = $this
            ->actingAs($user)
            ->from(route('genres.create'))
            ->post(route('genres.store'), [
                'name' => '既存ジャンル',
            ]);

        $duplicateResponse
            ->assertRedirect(route('genres.create'))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 1);
    }

    /**
     * 削除可能なジャンルは削除できる
     */
    public function test_authenticated_user_can_delete_deletable_genre(): void
    {
        $user = User::factory()->create();

        $deleteGenre = Genre::factory()->create([
            'name' => '削除対象ジャンル',
        ]);

        $remainingGenre = Genre::factory()->create([
            'name' => '残すジャンル',
        ]);

        $book = Book::factory()->create();

        // 書籍が別のジャンルも持っていれば、削除対象ジャンルを削除できる
        $book->genres()->attach([
            $deleteGenre->id,
            $remainingGenre->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('genres.destroy', $deleteGenre));

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseMissing('genres', [
            'id' => $deleteGenre->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $deleteGenre->id,
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $remainingGenre->id,
        ]);
    }

    /**
     * そのジャンルしか持たない書籍が存在する場合は削除できない
     */
    public function test_genre_cannot_be_deleted_when_book_has_only_that_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '削除制限ジャンル',
        ]);

        $book = Book::factory()->create([
            'title' => 'ジャンルが1つだけの書籍',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this
            ->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '削除制限ジャンル',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }
}
