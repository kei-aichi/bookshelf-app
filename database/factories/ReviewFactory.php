<?php

namespace Database\Factories;

use App\Models\Review;
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
        return [
            'rating' => fake()->numberBetween(3, 5),

            'comment' => fake()->randomElement([
                'とても読みやすく勉強になりました。',
                '内容が分かりやすく、最後まで楽しめました。',
                '何度も読み返したくなる一冊です。',
                '初心者にもおすすめできる内容でした。',
                '期待以上の内容で満足しています。',
                '考え方が変わるきっかけになりました。',
                '非常に参考になる一冊でした。',
                '文章が読みやすく理解しやすかったです。',
                'ぜひ多くの人に読んでほしい本です。',
                '購入して良かったと思える内容でした。',
            ]),
        ];
    }
}
