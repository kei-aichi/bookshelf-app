<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\User;
use App\Policies\BookPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍の登録者本人は更新できる
     */
    public function test_owner_can_update_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()
            ->for($user)
            ->create();

        $policy = new BookPolicy;

        $this->assertTrue(
            $policy->update($user, $book)
        );
    }

    /**
     * 書籍の登録者以外は更新できない
     */
    public function test_non_owner_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()
            ->for($owner)
            ->create();

        $policy = new BookPolicy;

        $this->assertFalse(
            $policy->update($otherUser, $book)
        );
    }

    /**
     * 書籍の登録者本人は削除できる
     */
    public function test_owner_can_delete_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()
            ->for($user)
            ->create();

        $policy = new BookPolicy;

        $this->assertTrue(
            $policy->delete($user, $book)
        );
    }

    /**
     * 書籍の登録者以外は削除できない
     */
    public function test_non_owner_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()
            ->for($owner)
            ->create();

        $policy = new BookPolicy;

        $this->assertFalse(
            $policy->delete($otherUser, $book)
        );
    }
}
