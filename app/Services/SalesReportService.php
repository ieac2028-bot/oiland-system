<?php

namespace App\Services;

use App\Enums\FuelType;
use App\Enums\PaymentMethod;
use App\Enums\SaleSource;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Aggregates sales for the dashboards. Every method accepts the branch scope
 * resolved for the requesting user, which is how role isolation is enforced
 * even in aggregate queries.
 */
class SalesReportService
{
    /**
     * Headline numbers for a period.
     *
     * @return array{total_revenue: float, total_quantity: float, transaction_count: int, average_ticket: float, average_price: float}
     */
    public function summary(?int $branchId, Carbon $from, Carbon $to): array
    {
        $base = Sale::query()
            ->forBranchScope($branchId)
            ->betweenDates($from->toDateString(), $to->toDateString());

        $totals = (clone $base)
            ->selectRaw('COALESCE(SUM(total_amount), 0) as revenue, COALESCE(SUM(quantity), 0) as quantity, COUNT(*) as transactions')
            ->first();

        $revenue = (float) ($totals->revenue ?? 0);
        $transactions = (int) ($totals->transactions ?? 0);

        return [
            'total_revenue' => round($revenue, 2),
            'total_quantity' => round((float) ($totals->quantity ?? 0), 3),
            'transaction_count' => $transactions,
            'average_ticket' => $transactions > 0 ? round($revenue / $transactions, 2) : 0.0,
            'average_price' => (float) ($totals->quantity ?? 0) > 0
                ? round($revenue / (float) $totals->quantity, 3)
                : 0.0,
        ];
    }

    /**
     * Revenue per day across the window, gap filled with zeros so the chart is
     * continuous.
     *
     * @return Collection<int, array{date: string, label: string, revenue: float, quantity: float}>
     */
    public function dailySeries(?int $branchId, Carbon $from, Carbon $to): Collection
    {
        $rows = Sale::query()
            ->forBranchScope($branchId)
            ->betweenDates($from->toDateString(), $to->toDateString())
            ->selectRaw('sale_date, SUM(total_amount) as revenue, SUM(quantity) as quantity')
            ->groupBy('sale_date')
            ->pluck('revenue', 'sale_date');

        $quantities = Sale::query()
            ->forBranchScope($branchId)
            ->betweenDates($from->toDateString(), $to->toDateString())
            ->selectRaw('sale_date, SUM(quantity) as quantity')
            ->groupBy('sale_date')
            ->pluck('quantity', 'sale_date');

        $series = collect();
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();

            $series->push([
                'date' => $key,
                'label' => $cursor->format('d M'),
                'revenue' => round((float) ($rows[$key] ?? 0), 2),
                'quantity' => round((float) ($quantities[$key] ?? 0), 3),
            ]);

            $cursor->addDay();
        }

        return $series;
    }

    /**
     * Revenue per month, useful for month-end reporting.
     *
     * @return Collection<int, array{month: string, label: string, revenue: float}>
     */
    public function monthlySeries(?int $branchId, int $months = 12): Collection
    {
        $from = today()->subMonths($months - 1)->startOfMonth();

        // Grouping happens in PHP so the query stays portable across MySQL and
        // SQLite (the test suite runs on an in-memory SQLite database).
        $rows = Sale::query()
            ->forBranchScope($branchId)
            ->where('sale_date', '>=', $from->toDateString())
            ->get(['sale_date', 'total_amount'])
            ->groupBy(fn (Sale $sale): string => Carbon::parse($sale->sale_date)->format('Y-m'))
            ->map(fn (Collection $group): float => round((float) $group->sum('total_amount'), 2));

        $series = collect();
        $cursor = $from->copy();

        while ($cursor->lte(today())) {
            $key = $cursor->format('Y-m');

            $series->push([
                'month' => $key,
                'label' => $cursor->format('M y'),
                'revenue' => (float) ($rows[$key] ?? 0.0),
            ]);

            $cursor->addMonth();
        }

        return $series;
    }

    /**
     * Revenue split by product, for the product breakdown chart.
     *
     * @return Collection<int, array{label: string, revenue: float, quantity: float, share: float}>
     */
    public function productBreakdown(?int $branchId, Carbon $from, Carbon $to): Collection
    {
        $rows = Sale::query()
            ->forBranchScope($branchId)
            ->betweenDates($from->toDateString(), $to->toDateString())
            ->join('catalog_items', 'catalog_items.id', '=', 'sales.catalog_item_id')
            ->whereIn('catalog_items.sku', ['FUEL-DSL', 'FUEL-P95', 'FUEL-U91'])
            ->selectRaw('catalog_items.id as catalog_item_id, catalog_items.name as product_name, catalog_items.fuel_type, SUM(sales.total_amount) as revenue, SUM(sales.quantity) as quantity')
            ->groupBy('catalog_items.id', 'catalog_items.name', 'catalog_items.fuel_type')
            ->orderByDesc('revenue')
            ->get();

        $total = (float) $rows->sum('revenue');

        return $rows->map(fn ($row) => [
            'label' => FuelType::tryFrom((string) $row->fuel_type)?->label()
                ?? $row->product_name,
            'revenue' => round((float) $row->revenue, 2),
            'quantity' => round((float) $row->quantity, 3),
            'share' => $total > 0 ? round(((float) $row->revenue / $total) * 100, 1) : 0.0,
        ]);
    }

    /**
     * Branch league table. Head office sees every branch, branch staff only
     * their own row.
     *
     * @return Collection<int, array{id: int, name: string, city: string, revenue: float, quantity: float, transactions: int}>
     */
    public function topBranches(Carbon $from, Carbon $to, ?int $restrictToBranchId = null, int $limit = 10): Collection
    {
        $query = Sale::query()
            ->forBranchScope($restrictToBranchId)
            ->betweenDates($from->toDateString(), $to->toDateString())
            ->selectRaw('branch_id, SUM(total_amount) as revenue, SUM(quantity) as quantity, COUNT(*) as transactions')
            ->groupBy('branch_id')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();

        $branches = Branch::whereIn('id', $query->pluck('branch_id'))->get()->keyBy('id');

        return $query->map(fn ($row) => [
            'id' => (int) $row->branch_id,
            'name' => $branches[$row->branch_id]->name ?? 'Unknown branch',
            'city' => $branches[$row->branch_id]->city ?? '—',
            'revenue' => round((float) $row->revenue, 2),
            'quantity' => round((float) $row->quantity, 3),
            'transactions' => (int) $row->transactions,
        ]);
    }

    /**
     * Payment method split for the dashboard donut.
     *
     * @return Collection<int, array{method: string, label: string, revenue: float, share: float}>
     */
    public function paymentSplit(?int $branchId, Carbon $from, Carbon $to): Collection
    {
        $rows = Sale::query()
            ->forBranchScope($branchId)
            ->betweenDates($from->toDateString(), $to->toDateString())
            ->selectRaw('payment_method, SUM(total_amount) as revenue')
            ->groupBy('payment_method')
            ->get();

        $total = (float) $rows->sum('revenue');

        return collect(PaymentMethod::cases())
            ->map(function (PaymentMethod $method) use ($rows, $total): array {
                $revenue = (float) ($rows->firstWhere('payment_method', $method->value)->revenue ?? 0);

                return [
                    'method' => $method->value,
                    'label' => $method->label(),
                    'revenue' => round($revenue, 2),
                    'share' => $total > 0 ? round(($revenue / $total) * 100, 1) : 0.0,
                ];
            })
            ->filter(fn (array $row): bool => $row['revenue'] > 0)
            ->values();
    }

    /**
     * Revenue for the current day, used on the admin dashboard cards.
     */
    public function todayRevenue(?int $branchId): float
    {
        return round((float) Sale::forBranchScope($branchId)->onDate(today()->toDateString())->sum('total_amount'), 2);
    }

    /**
     * Revenue for the current month to date.
     */
    public function monthToDateRevenue(?int $branchId): float
    {
        return round((float) Sale::forBranchScope($branchId)
            ->where('sale_date', '>=', today()->startOfMonth()->toDateString())
            ->sum('total_amount'), 2);
    }

    /**
     * The set of dates that actually have rows, for the entry calendar.
     *
     * @return Collection<int, string>
     */
    public function datesWithSales(?int $branchId, Carbon $from, Carbon $to): Collection
    {
        return Sale::forBranchScope($branchId)
            ->betweenDates($from->toDateString(), $to->toDateString())
            ->distinct()
            ->pluck('sale_date')
            ->map(fn (string $date): string => Carbon::parse($date)->toDateString())
            ->sort()
            ->values();
    }

    /**
     * Replace every sale recorded on a given day. Branch staff often enter a
     * whole day at once and correct it as figures come in from the pumps.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{inserted: int, replaced: int}
     */
    public function replaceDay(User $actor, string $date, array $rows): array
    {
        $branchId = $actor->branch_id;

        // Guard against a branch-less actor wiping every branch's figures for
        // the day: a null scope means "all branches" to the query builder.
        if ($branchId === null) {
            throw new LogicException('Sales can only be recorded against a branch.');
        }

        return DB::transaction(function () use ($actor, $branchId, $date, $rows): array {
            $replaced = Sale::forBranchScope($branchId)->onDate($date)->delete();

            foreach ($rows as $row) {
                Sale::create([
                    'branch_id' => $branchId,
                    'catalog_item_id' => $row['catalog_item_id'] ?? null,
                    'item_name' => $row['item_name'],
                    'sale_date' => $date,
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'total_amount' => $row['total_amount'],
                    'currency' => config('app.currency'),
                    'payment_method' => $row['payment_method'] ?? PaymentMethod::Cash->value,
                    'source' => $row['source'] ?? SaleSource::Manual->value,
                    'notes' => $row['notes'] ?? null,
                    'recorded_by' => $actor->id,
                ]);
            }

            return ['inserted' => count($rows), 'replaced' => $replaced];
        });
    }
}
