<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainUser = User::first();

        $otherUsers = User::whereKeyNot($mainUser->id)->get();

        $books = Book::all();

        if (! $mainUser || $books->count() < 10) {
            return;
        }

        // 主要ユーザー：未着手
        ReadingPlan::factory()->create([
            'user_id' => $mainUser->id,
            'book_id' => $books[0]->id,
            'target_date' => now()->addDays(7),
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $mainUser->id,
            'book_id' => $books[1]->id,
            'target_date' => now()->addDays(14),
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $mainUser->id,
            'book_id' => $books[2]->id,
            'target_date' => now()->addDays(30),
        ]);

        // 主要ユーザー：読書中
        ReadingPlan::factory()->reading()->create([
            'user_id' => $mainUser->id,
            'book_id' => $books[3]->id,
            'target_date' => now(),
        ]);

        ReadingPlan::factory()->reading()->create([
            'user_id' => $mainUser->id,
            'book_id' => $books[4]->id,
            'target_date' => now()->addDays(3),
        ]);

        ReadingPlan::factory()->reading()->create([
            'user_id' => $mainUser->id,
            'book_id' => $books[5]->id,
            'target_date' => now()->subDays(3),
        ]);

        // 主要ユーザー：読了
        ReadingPlan::factory()->completed()->create([
            'user_id' => $mainUser->id,
            'book_id' => $books[6]->id,
            'target_date' => now()->subDays(10),
            'completed_at' => now()->subDays(12),
        ]);

        ReadingPlan::factory()->completed()->create([
            'user_id' => $mainUser->id,
            'book_id' => $books[7]->id,
            'target_date' => now()->subDays(5),
            'completed_at' => now()->subDays(3),
        ]);

        ReadingPlan::factory()->completed()->create([
            'user_id' => $mainUser->id,
            'book_id' => $books[8]->id,
            'target_date' => now()->addDays(2),
            'completed_at' => now(),
        ]);

        ReadingPlan::factory()->completed()->create([
            'user_id' => $mainUser->id,
            'book_id' => $books[9]->id,
            'target_date' => now(),
            'completed_at' => now(),
        ]);

        // 他ユーザー：ユーザーごとのデータ分離確認用
        foreach ($otherUsers as $index => $user) {
            $book = $books->get(($index + 10) % $books->count());

            ReadingPlan::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'target_date' => now()->addDays(7),
            ]);
        }
    }
}
