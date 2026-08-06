<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingPlan>
 */
class ReadingPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'status' => ReadingPlan::STATUS_NOT_STARTED,
            'target_date' => now()->addDays(fake()->numberBetween(1, 30)),
            'completed_at' => null,
        ];
    }

    public function reading(): static
    {
        return $this->state(fn () => [
            'status' => ReadingPlan::STATUS_READING,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => ReadingPlan::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }
}
