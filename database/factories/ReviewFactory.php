<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $comments = [
            1 => '期待していた内容とは異なりました。',
            2 => '参考になる部分もありました。',
            3 => '全体的に満足できる内容でした。',
            4 => 'とても参考になりました。',
            5 => '非常に満足できる内容でした。',
        ];

        $rating = fake()->numberBetween(1, 5);

        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'rating' => $rating,
            'comment' => $comments[$rating],
        ];
    }
}
