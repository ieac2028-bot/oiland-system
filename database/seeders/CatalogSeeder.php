<?php

namespace Database\Seeders;

use App\Enums\CatalogItemType;
use App\Enums\FuelType;
use App\Models\CatalogItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    /**
     * Reference prices in PHP per litre used to seed branch price boards.
     *
     * @var array<string, float>
     */
    public const FUEL_PRICES = [
        'unleaded' => 82.00,
        'premium' => 83.00,
        'diesel' => 92.00,
    ];

    /**
     * Fuel grades offered across the network.
     *
     * @var array<int, array{name: string, sku: string, fuel_type: FuelType|null, unit: string, short_description: string}>
     */
    private const PRODUCTS = [
        ['name' => 'Green', 'sku' => 'FUEL-U91', 'fuel_type' => FuelType::Unleaded, 'unit' => 'liter', 'short_description' => 'Green gasoline for everyday driving.'],
        ['name' => 'Premium', 'sku' => 'FUEL-P95', 'fuel_type' => FuelType::Premium, 'unit' => 'liter', 'short_description' => 'Premium gasoline for everyday driving.'],
        ['name' => 'Diesel', 'sku' => 'FUEL-DSL', 'fuel_type' => FuelType::Diesel, 'unit' => 'liter', 'short_description' => 'Low-sulphur diesel suitable for commercial fleets.'],
    ];

    public function run(): void
    {
        $offeredSkus = array_column(self::PRODUCTS, 'sku');

        CatalogItem::query()->whereNotIn('sku', $offeredSkus)->update(['is_active' => false]);

        foreach (self::PRODUCTS as $definition) {
            $this->upsertItem(
                $definition['name'],
                $definition['sku'],
                CatalogItemType::Product,
                $definition['fuel_type'],
                $definition['unit'],
                $definition['short_description'],
            );
        }
    }

    private function upsertItem(
        string $name,
        string $sku,
        CatalogItemType $type,
        ?FuelType $fuelType,
        string $unit,
        string $shortDescription,
    ): void {
        CatalogItem::query()->updateOrCreate(
            ['sku' => $sku],
            [
                'name' => $name,
                'slug' => Str::slug($name),
                'type' => $type->value,
                'fuel_type' => $fuelType?->value,
                'unit' => $unit,
                'short_description' => $shortDescription,
                'description' => $shortDescription.' Available at participating stations, subject to forecourt capacity.',
                'image_path' => null,
                'is_active' => true,
            ],
        );
    }
}
