<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\Price;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Price>
 */
class PriceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'catalog_item_id' => CatalogItem::factory()->product(),
            'price' => fake()->randomFloat(2, 1.5, 6.5),
            'currency' => 'PHP',
            'effective_from' => today()->subDays(fake()->numberBetween(0, 30)),
            'effective_to' => null,
            'is_active' => true,
            'approved_by' => User::factory()->superAdmin(),
            'approved_at' => now(),
            'created_by' => null,
        ];
    }

    public function amount(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
        ]);
    }

    public function retired(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'effective_to' => today(),
        ]);
    }
}
