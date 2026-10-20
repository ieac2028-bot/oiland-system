<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\Price;
use App\Models\PriceHistory;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NetworkFuelOfferingsSeeder extends Seeder
{
    private const OFFERED_SKUS = ['FUEL-DSL', 'FUEL-P95', 'FUEL-U91'];

    private const BASE_PRICES = [
        'FUEL-DSL' => 92.00,
        'FUEL-P95' => 83.00,
        'FUEL-U91' => 82.00,
    ];

    public function run(): void
    {
        CatalogItem::query()->whereNotIn('sku', self::OFFERED_SKUS)->update(['is_active' => false]);
        Review::query()->where('title', 'Solid car wash')->update([
            'title' => 'Helpful forecourt staff',
            'body' => 'Attendants handled the busy queue efficiently. The forecourt was tidy and the staff were helpful.',
        ]);

        $fuelItems = CatalogItem::query()
            ->whereIn('sku', self::OFFERED_SKUS)
            ->get(['id', 'sku']);
        $missingSkus = array_diff(self::OFFERED_SKUS, $fuelItems->pluck('sku')->all());

        if ($missingSkus !== []) {
            throw new \RuntimeException('Required fuel catalog items are missing: '.implode(', ', $missingSkus));
        }

        $now = now();
        $branches = Branch::query()->where('is_active', true)->get();
        foreach ($branches as $branch) {
            if (str_starts_with($branch->code, 'OIL-')) {
                $branch->update([
                    'description' => sprintf(
                        'Oiland %s serves %s, %s with Diesel, Premium, and Green.',
                        $branch->name,
                        $branch->city,
                        $branch->region,
                    ),
                ]);
            }

            DB::table('branch_catalog_item')->where('branch_id', $branch->id)->update([
                'is_available' => false,
                'updated_at' => $now,
            ]);

            foreach ($fuelItems as $fuelItem) {
                DB::table('branch_catalog_item')->updateOrInsert(
                    ['branch_id' => $branch->id, 'catalog_item_id' => $fuelItem->id],
                    [
                        'is_available' => true,
                        'notes' => $fuelItem->sku === 'FUEL-U91'
                            ? 'Known at this station as Green.'
                            : null,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ],
                );

                $this->publishStationPrice($branch, $fuelItem);
            }
        }

        Price::query()
            ->whereHas('catalogItem', fn ($query) => $query->whereNotIn('sku', self::OFFERED_SKUS))
            ->where('is_active', true)
            ->update(['is_active' => false, 'effective_to' => today()->subDay()]);

        $seedAdmin = User::query()->where('email', NetworkSeeder::SUPER_ADMIN_EMAIL)->first();
        if ($seedAdmin !== null) {
            Announcement::query()
                ->where('author_id', $seedAdmin->id)
                ->whereIn('slug', [
                    'revised-forecourt-hours-during-ramadan',
                    'new-ev-charging-bays-at-trece',
                    'full-service-express-lane',
                    'baguio-tyre-storage-program',
                    'loyalty-tier-benefits-refresh',
                ])
                ->update(['is_published' => false]);

            foreach ([
                [
                    'title' => 'Employee events and branch notices',
                    'body' => 'Head office updates about employee events, staff activities, participating branches, and event details will be posted here.',
                    'type' => 'event',
                ],
                [
                    'title' => 'Station staff hiring notices',
                    'body' => 'Head office will post station roles, hiring locations, and application details here when positions are available.',
                    'type' => 'job',
                ],
            ] as $notice) {
                $announcement = Announcement::query()->updateOrCreate(
                    ['slug' => Str::slug($notice['title'])],
                    [
                        ...$notice,
                        'scope' => 'global',
                        'is_featured' => true,
                        'publish_at' => now(),
                        'expires_at' => now()->addDays(30),
                        'is_published' => $notice['type'] !== 'job',
                        'author_id' => $seedAdmin->id,
                        'image_path' => null,
                    ],
                );
                $announcement->branches()->sync([]);
            }
        }
    }

    private function publishStationPrice(Branch $branch, CatalogItem $fuelItem): void
    {
        $basePrice = self::BASE_PRICES[$fuelItem->sku];
        $price = strtolower($branch->name) === 'abucay'
            ? $basePrice
            : $basePrice + (((abs(crc32($branch->code.'|'.$fuelItem->sku)) % 5) - 2) * 0.25);
        $price = round($price, 2);

        $activePrices = Price::query()
            ->where('branch_id', $branch->id)
            ->where('catalog_item_id', $fuelItem->id)
            ->where('is_active', true)
            ->orderByDesc('effective_from')
            ->get();
        $currentPrice = $activePrices->first(fn (Price $row): bool => $row->isCurrentlyEffective());

        if ($activePrices->count() === 1 && $currentPrice !== null && (float) $currentPrice->price === $price) {
            return;
        }

        foreach ($activePrices as $activePrice) {
            $activePrice->update([
                'is_active' => false,
                'effective_to' => today()->subDay(),
            ]);
        }

        $superAdmin = User::query()->where('email', NetworkSeeder::SUPER_ADMIN_EMAIL)->first();
        $newPrice = Price::query()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $fuelItem->id,
            'price' => $price,
            'currency' => config('app.currency'),
            'effective_from' => today(),
            'effective_to' => null,
            'is_active' => true,
            'approved_by' => $superAdmin?->id,
            'approved_at' => now(),
            'created_by' => $superAdmin?->id,
        ]);

        PriceHistory::query()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $fuelItem->id,
            'price_id' => $newPrice->id,
            'previous_price' => $currentPrice?->price,
            'price' => $price,
            'currency' => config('app.currency'),
            'effective_from' => today(),
            'changed_by' => $superAdmin?->id,
        ]);
    }
}
