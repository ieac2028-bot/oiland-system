<?php

namespace App\Services;

use App\Models\PriceHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the 7 and 30 day price trend series shown on a branch page.
 */
class PriceTrendService
{
    /**
     * The daily closing price for one catalog item at one branch.
     *
     * The most recent history entry per day wins, and days without a change
     * carry the previous close forward so the line is continuous.
     *
     * @return Collection<int, array{date: string, label: string, price: float, changed: bool}>
     */
    public function dailySeries(int $branchId, int $catalogItemId, int $days = 30): Collection
    {
        $since = today()->subDays($days - 1);

        $entries = PriceHistory::query()
            ->where('branch_id', $branchId)
            ->where('catalog_item_id', $catalogItemId)
            ->where('created_at', '>=', $since->copy()->startOfDay())
            ->orderBy('effective_from')
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (PriceHistory $history) => $history->effective_from->toDateString());

        // Seed the series with the price that was live before the window.
        $carry = (float) (PriceHistory::query()
            ->where('branch_id', $branchId)
            ->where('catalog_item_id', $catalogItemId)
            ->where('created_at', '<', $since->copy()->startOfDay())
            ->orderByDesc('created_at')
            ->value('price') ?? 0);

        $series = collect();
        $cursor = $since->copy();

        while ($cursor->lte(today())) {
            $key = $cursor->toDateString();
            $dayEntries = $entries->get($key);
            $changed = $dayEntries !== null && $dayEntries->isNotEmpty();

            if ($changed) {
                $carry = (float) $dayEntries->last()->price;
            }

            $series->push([
                'date' => $key,
                'label' => $cursor->format('d M'),
                'price' => round($carry, 2),
                'changed' => $changed,
            ]);

            $cursor->addDay();
        }

        return $series;
    }

    /**
     * Summary statistics for the window, used for the trend header badges.
     *
     * @return array{lowest: float, highest: float, average: float, change: float, change_percentage: float|null, direction: string}
     */
    public function summary(int $branchId, int $catalogItemId, int $days = 30): array
    {
        $series = $this->dailySeries($branchId, $catalogItemId, $days);
        $prices = $series->pluck('price')->filter(fn (float $price): bool => $price > 0)->values();

        if ($prices->isEmpty()) {
            return [
                'lowest' => 0.0,
                'highest' => 0.0,
                'average' => 0.0,
                'change' => 0.0,
                'change_percentage' => null,
                'direction' => 'unchanged',
            ];
        }

        $first = (float) $prices->first();
        $last = (float) $prices->last();
        $change = round($last - $first, 2);

        return [
            'lowest' => (float) $prices->min(),
            'highest' => (float) $prices->max(),
            'average' => round((float) $prices->avg(), 2),
            'change' => $change,
            'change_percentage' => $first > 0 ? round(($change / $first) * 100, 2) : null,
            'direction' => $change > 0 ? 'increase' : ($change < 0 ? 'decrease' : 'unchanged'),
        ];
    }

    /**
     * The trend for every priced item at a branch, keyed by catalog item id.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function forBranch(int $branchId, int $days = 30): Collection
    {
        $catalogItemIds = PriceHistory::query()
            ->where('branch_id', $branchId)
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->distinct()
            ->pluck('catalog_item_id');

        return $catalogItemIds->mapWithKeys(function (int $catalogItemId) use ($branchId, $days): array {
            $series = $this->dailySeries($branchId, $catalogItemId, $days);
            $first = $series->first();

            return [$catalogItemId => [
                'series' => $series,
                'labels' => $series->pluck('label')->all(),
                'values' => $series->pluck('price')->all(),
                'summary' => $this->summary($branchId, $catalogItemId, $days),
                'from' => is_array($first) ? $first['date'] : Carbon::now()->subDays($days - 1)->toDateString(),
                'to' => today()->toDateString(),
            ]];
        });
    }
}
