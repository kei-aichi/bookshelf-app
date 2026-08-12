<?php

namespace Tests\Unit\Unit;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Policies\ReadingPlanPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 所有者本人は読書計画を更新できる
     */
    public function test_owner_can_update_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $policy = new ReadingPlanPolicy;

        $this->assertTrue(
            $policy->update($user, $readingPlan)
        );
    }

    /**
     * 所有者以外は読書計画を更新できない
     */
    public function test_non_owner_cannot_update_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);

        $policy = new ReadingPlanPolicy;

        $this->assertFalse(
            $policy->update($otherUser, $readingPlan)
        );
    }

    /**
     * 所有者本人は読書計画を削除できる
     */
    public function test_owner_can_delete_reading_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $policy = new ReadingPlanPolicy;

        $this->assertTrue(
            $policy->delete($user, $readingPlan)
        );
    }

    /**
     * 所有者以外は読書計画を削除できない
     */
    public function test_non_owner_cannot_delete_reading_plan(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);

        $policy = new ReadingPlanPolicy;

        $this->assertFalse(
            $policy->delete($otherUser, $readingPlan)
        );
    }
}
