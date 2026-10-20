<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Branch;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'user_id' => User::factory()->customer(),
            'rating' => fake()->numberBetween(3, 5),
            'title' => ucfirst(fake()->words(3, true)),
            'body' => fake()->paragraph(),
            'status' => ReviewStatus::Published->value,
            'is_flagged' => false,
            'flagged_at' => null,
            'flagged_by' => null,
            'moderated_by' => null,
            'moderated_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReviewStatus::Pending->value,
        ]);
    }

    public function hidden(?User $moderator = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReviewStatus::Hidden->value,
            'moderated_by' => $moderator?->id ?? User::factory()->superAdmin(),
            'moderated_at' => now(),
        ]);
    }

    public function flagged(?User $flagger = null): static
    {
        return $this->state(fn (array $attributes) => [
            'is_flagged' => true,
            'flagged_at' => now(),
            'flagged_by' => $flagger?->id ?? User::factory()->branchStaff(),
        ]);
    }

    public function rating(int $rating): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $rating,
        ]);
    }
}
