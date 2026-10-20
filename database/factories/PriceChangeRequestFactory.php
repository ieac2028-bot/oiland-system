<?php

namespace Database\Factories;

use App\Enums\PriceChangeStatus;
use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\PriceChangeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceChangeRequest>
 */
class PriceChangeRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $old = fake()->randomFloat(2, 1.5, 6.5);

        return [
            'branch_id' => Branch::factory(),
            'catalog_item_id' => CatalogItem::factory()->product(),
            'old_price' => $old,
            'new_price' => round($old + fake()->randomFloat(2, -0.4, 0.6), 2),
            'note' => fake()->sentence(),
            'status' => PriceChangeStatus::Pending->value,
            'requested_by' => User::factory()->branchAdmin(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ];
    }

    public function approved(?User $reviewer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PriceChangeStatus::Approved->value,
            'reviewed_by' => $reviewer?->id ?? User::factory()->superAdmin(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(?User $reviewer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PriceChangeStatus::Rejected->value,
            'reviewed_by' => $reviewer?->id ?? User::factory()->superAdmin(),
            'reviewed_at' => now(),
            'review_note' => fake()->sentence(),
        ]);
    }
}
