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

        foreach ($books as $book) {

            $reviewUsers = $users
                ->shuffle()
                ->take(fake()->numberBetween(2, 4));

            foreach ($reviewUsers as $user) {

                Review::factory()->create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                ]);

            }
        }
    }
}
