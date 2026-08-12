<?php

namespace Database\Factories;

use App\Enums\ReadingPlanStatus;
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
            'status' => ReadingPlanStatus::NotStarted,
            'target_date' => now()->addDays(fake()->numberBetween(1, 30)),
            'completed_at' => null,
        ];
    }

    public function reading(): static
    {
        return $this->state(fn () => [
            'status' => ReadingPlanStatus::Reading,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);
    }
}
