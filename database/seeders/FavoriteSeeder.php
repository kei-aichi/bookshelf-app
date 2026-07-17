<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::orderBy('id')->get();
        $books = Book::orderBy('id')->get();

        $favorites = [
            [0, 1, 2],       // 1人目
            [2, 3, 4, 5],    // 2人目
            [6, 7, 8],       // 3人目
            [9, 10, 1, 2],   // 4人目
            [3, 4, 5, 6, 7], // 5人目
        ];

        foreach ($users as $userIndex => $user) {
            $bookIds = [];

            foreach ($favorites[$userIndex] as $bookIndex) {
                $bookIds[] = $books[$bookIndex]->id;
            }

            $user->favoriteBooks()->syncWithoutDetaching($bookIds);
        }
    }
}
