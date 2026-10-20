<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Realistic operating hours for a fuel station network.
     *
     * @return array<string, array{open: string, close: string}>
     */
    public static function openingHours(): array
    {
        return [
            'monday' => ['open' => '06:00', 'close' => '23:00'],
            'tuesday' => ['open' => '06:00', 'close' => '23:00'],
            'wednesday' => ['open' => '06:00', 'close' => '23:00'],
            'thursday' => ['open' => '06:00', 'close' => '23:00'],
            'friday' => ['open' => '06:00', 'close' => '23:00'],
            'saturday' => ['open' => '07:00', 'close' => '23:00'],
            'sunday' => ['open' => '07:00', 'close' => '22:00'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Station';
        $city = fake()->randomElement(['Dubai', 'Abu Dhabi', 'Sharjah', 'Al Ain', 'Ajman', 'Ras Al Khaimah']);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'code' => 'BR-'.fake()->unique()->numberBetween(1000, 9999),
            'address_line1' => fake()->streetAddress(),
            'address_line2' => fake()->optional(0.5)->buildingNumber(),
            'city' => $city,
            'region' => $city,
            'postal_code' => fake()->numerify('#####'),
            'country' => 'United Arab Emirates',
            'phone' => fake()->numerify('+9714#######'),
            'email' => fake()->unique()->companyEmail(),
            'latitude' => fake()->latitude(22.5, 26.5),
            'longitude' => fake()->longitude(51.5, 56.5),
            'is_24_hours' => false,
            'opening_hours' => self::openingHours(),
            'photos' => [],
            'description' => fake()->paragraph(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function open24Hours(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_24_hours' => true,
        ]);
    }

    public function inCity(string $city, ?string $region = null): static
    {
        return $this->state(fn (array $attributes) => [
            'city' => $city,
            'region' => $region ?? $city,
        ]);
    }
}
