<?php

namespace App\Services;

use App\Models\Sale;
use App\Support\CsvSafe;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Renders sales data as an Excel compatible CSV or as a PDF report.
 */
class SalesExportService
{
    /**
     * @param  Collection<int, Sale>  $sales
     */
    public function toCsv(Collection $sales, string $filename, ?Carbon $period = null): StreamedResponse
    {
        $headers = [
            'Date',
            'Branch',
            'Item',
            'Quantity',
            'Unit Price',
            'Total Amount',
            'Currency',
            'Payment Method',
            'Source',
            'Recorded By',
        ];

        return response()->streamDownload(function () use ($sales, $headers): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, $headers);

            foreach ($sales as $sale) {
                fputcsv($handle, [
                    $sale->sale_date?->toDateString(),
                    CsvSafe::cell($sale->branch?->name),
                    CsvSafe::cell($sale->item_name),
                    number_format((float) $sale->quantity, 3, '.', ''),
                    number_format((float) $sale->unit_price, 2, '.', ''),
                    number_format((float) $sale->total_amount, 2, '.', ''),
                    $sale->currency,
                    $sale->payment_method?->label() ?? $sale->payment_method,
                    $sale->source?->label() ?? $sale->source,
                    CsvSafe::cell($sale->recorder?->name),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * @param  Collection<int, Sale>  $sales
     */
    public function toPdf(Collection $sales, string $title, ?string $scopeLabel = null, ?Carbon $period = null): Response
    {
        $rows = $sales->map(fn (Sale $sale) => [
            $sale->sale_date?->toDateString(),
            $sale->branch?->name,
            $sale->item_name,
            number_format((float) $sale->quantity, 3),
            number_format((float) $sale->unit_price, 2),
            number_format((float) $sale->total_amount, 2),
        ])->all();

        $totals = [
            'quantity' => number_format((float) $sales->sum(fn (Sale $sale) => (float) $sale->quantity), 3),
            'revenue' => number_format((float) $sales->sum(fn (Sale $sale) => (float) $sale->total_amount), 2),
        ];

        $pdf = Pdf::loadView('exports.sales-report', [
            'title' => $title,
            'scopeLabel' => $scopeLabel,
            'period' => $period?->copy()->format('d M Y') ?? $period,
            'generatedAt' => now()->format('d M Y H:i'),
            'rows' => $rows,
            'totals' => $totals,
        ])->setPaper('a4', 'landscape');

        return $pdf->download(str($title)->slug().'.pdf');
    }

    /**
     * Apply the export filters shared by the CSV and PDF endpoints.
     *
     * @param  Builder<Sale>  $query
     * @return Builder<Sale>
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->forBranchScope($filters['branch_scope'] ?? null)
            ->when(
                isset($filters['branch_id']),
                fn (Builder $query) => $query->where('branch_id', (int) $filters['branch_id'])
            )
            ->betweenDates($filters['from'] ?? now()->startOfMonth()->toDateString(), $filters['to'] ?? today()->toDateString())
            ->when(
                filled($filters['product'] ?? null),
                fn (Builder $query) => $query->where('item_name', 'like', '%'.$filters['product'].'%')
            );
    }
}
