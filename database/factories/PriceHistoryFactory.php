<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\PriceHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceHistory>
 */
class PriceHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 1.5, 6.5);

        return [
            'branch_id' => Branch::factory(),
            'catalog_item_id' => CatalogItem::factory()->product(),
            'price_id' => null,
            'price_change_request_id' => null,
            'previous_price' => round($price - fake()->randomFloat(2, 0.1, 0.4), 2),
            'price' => $price,
            'currency' => 'PHP',
            'effective_from' => today()->subDays(fake()->numberBetween(0, 60)),
            'changed_by' => User::factory()->superAdmin(),
        ];
    }
}
