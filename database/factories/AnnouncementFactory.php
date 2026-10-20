<?php

namespace Database\Factories;

use App\Enums\AnnouncementScope;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = ucfirst(fake()->words(4, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'body' => fake()->paragraphs(2, true),
            'image_path' => null,
            'scope' => AnnouncementScope::Global->value,
            'publish_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'is_published' => true,
            'is_featured' => false,
            'author_id' => User::factory()->superAdmin(),
        ];
    }

    public function forBranches(): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => AnnouncementScope::Branches->value,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'publish_at' => now()->subMonths(2),
            'expires_at' => now()->subDay(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'publish_at' => now()->addWeek(),
            'expires_at' => now()->addMonth(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
