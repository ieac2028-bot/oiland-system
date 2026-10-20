<?php

namespace Database\Factories;

use App\Enums\CatalogItemType;
use App\Enums\FuelType;
use App\Models\CatalogItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CatalogItem>
 */
class CatalogItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'type' => CatalogItemType::Product->value,
            'sku' => strtoupper(Str::random(6)),
            'fuel_type' => fake()->randomElement(FuelType::values()),
            'unit' => 'liter',
            'short_description' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'image_path' => null,
            'is_active' => true,
        ];
    }

    public function product(?FuelType $fuelType = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CatalogItemType::Product->value,
            'fuel_type' => $fuelType?->value ?? fake()->randomElement(FuelType::values()),
            'unit' => 'liter',
        ]);
    }

    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CatalogItemType::Service->value,
            'fuel_type' => null,
            'unit' => 'service',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
