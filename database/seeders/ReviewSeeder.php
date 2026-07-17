<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::orderBy('id')->get();
        $books = Book::orderBy('id')->get();

        $reviewAssignments = [
            [0, 1, 2], // 1冊目
            [1, 2, 3], // 2冊目
            [2, 3, 4], // 3冊目
            [3, 4, 0], // 4冊目
            [4, 0, 1], // 5冊目
            [0, 2, 4], // 6冊目
            [1, 3, 0], // 7冊目
            [2, 4, 1], // 8冊目
            [3, 0, 2], // 9冊目
            [4, 1, 3], // 10冊目
            [0, 2], // 11冊目
        ];

        foreach ($reviewAssignments as $bookIndex => $userIndexes) {
            foreach ($userIndexes as $userIndex) {
                Review::factory()->create([
                    'user_id' => $users[$userIndex]->id,
                    'book_id' => $books[$bookIndex]->id,
                ]);
            }
        }
    }
}
