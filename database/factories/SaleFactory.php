<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\SaleSource;
use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(1, 5, 400);
        $unitPrice = fake()->randomFloat(2, 1.8, 6.2);

        return [
            'branch_id' => Branch::factory(),
            'catalog_item_id' => CatalogItem::factory()->product(),
            'item_name' => fake()->randomElement(['Green', 'Diesel', 'Premium']),
            'sale_date' => today()->subDays(fake()->numberBetween(0, 45)),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => round($quantity * $unitPrice, 2),
            'currency' => 'PHP',
            'payment_method' => fake()->randomElement(PaymentMethod::values()),
            'source' => SaleSource::Manual->value,
            'notes' => null,
            'recorded_by' => User::factory()->branchStaff(),
        ];
    }

    public function onDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'sale_date' => $date,
        ]);
    }

    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (array $attributes) => [
            'branch_id' => $branch->id,
        ]);
    }

    public function imported(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => SaleSource::Import->value,
        ]);
    }
}
