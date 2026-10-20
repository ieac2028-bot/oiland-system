<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\SalesExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sales exports in Excel compatible CSV and PDF.
 */
class SalesExportController extends Controller
{
    public function __construct(private readonly SalesExportService $exports) {}

    public function csv(Request $request): StreamedResponse
    {
        Gate::authorize('export', Sale::class);

        $user = $request->user();
        $period = $this->resolvePeriod($request);

        $sales = $this->exports->applyFilters(Sale::query()->with(['branch', 'recorder']), [
            'branch_scope' => $user->branch_id,
            'branch_id' => $request->integer('branch_id') ?: null,
            'from' => $period['from']->toDateString(),
            'to' => $period['to']->toDateString(),
            'product' => $request->string('item')->toString() ?: null,
        ])->orderBy('sale_date')->get();

        return $this->exports->toCsv(
            $sales,
            'sales-'.$period['from']->format('Ymd').'-'.$period['to']->format('Ymd').'.csv',
            $period['from'],
        );
    }

    public function pdf(Request $request): Response
    {
        Gate::authorize('export', Sale::class);

        $user = $request->user();
        $period = $this->resolvePeriod($request);

        $sales = $this->exports->applyFilters(Sale::query()->with(['branch', 'recorder']), [
            'branch_scope' => $user->branch_id,
            'branch_id' => $request->integer('branch_id') ?: null,
            'from' => $period['from']->toDateString(),
            'to' => $period['to']->toDateString(),
            'product' => $request->string('item')->toString() ?: null,
        ])->orderBy('sale_date')->get();

        $scopeLabel = $user->isSuperAdmin()
            ? 'All branches'
            : $user->branch?->name;

        return $this->exports->toPdf(
            $sales,
            'Fuel Sales Report',
            $scopeLabel,
            $period['from'],
        );
    }

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    protected function resolvePeriod(Request $request): array
    {
        $from = $request->date('from') ?? today()->startOfMonth();
        $to = $request->date('to') ?? today();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return ['from' => $from->startOfDay(), 'to' => $to->startOfDay()];
    }
}
