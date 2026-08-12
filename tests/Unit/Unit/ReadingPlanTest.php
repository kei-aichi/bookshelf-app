<?php

namespace Tests\Unit\Unit;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 読書計画はユーザーに属する。
     */
    public function test_reading_plan_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue($readingPlan->user->is($user));
    }

    /**
     * 読書計画は書籍に属する。
     */
    public function test_reading_plan_belongs_to_book(): void
    {
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'book_id' => $book->id,
        ]);

        $this->assertTrue($readingPlan->book->is($book));
    }
}
