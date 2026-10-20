<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\SaleSource;
use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Sale;
use App\Services\ActivityLogger;
use App\Services\SalesImportService;
use App\Services\SalesReportService;
use App\Support\ActivityAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Daily sales entry, CSV import and the raw ledger for a branch.
 */
class SaleController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly SalesReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Sale::class);

        $user = $request->user();
        $period = $this->resolvePeriod($request);

        $sales = Sale::query()
            ->forBranchScope($user->branch_id)
            ->betweenDates($period['from']->toDateString(), $period['to']->toDateString())
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('item'), fn ($query) => $query->where('item_name', 'like', '%'.$request->string('item')->toString().'%'))
            ->with(['branch', 'catalogItem', 'recorder'])
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.sales.index', [
            'sales' => $sales,
            'period' => $period,
            'summary' => $this->reports->summary($user->branch_id, $period['from'], $period['to']),
            'datesWithSales' => $this->reports->datesWithSales($user->branch_id, $period['from'], $period['to']),
            'filters' => $request->only(['from', 'to', 'branch_id', 'item']),
            'canImport' => Gate::allows('import', Sale::class),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Sale::class);

        $user = $request->user();
        $date = $request->date('date') ?? today();

        return view('admin.sales.create', [
            'date' => $date,
            'catalogItems' => $user->branch->catalogItems()
                ->wherePivot('is_available', true)
                ->orderBy('catalog_items.type')
                ->orderBy('catalog_items.name')
                ->get(),
            'existing' => Sale::forBranchScope($user->branch_id)
                ->onDate($date->toDateString())
                ->with('catalogItem')
                ->get(),
            'paymentMethods' => PaymentMethod::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Sale::class);

        $validated = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'rows' => ['required', 'array', 'min:1', 'max:100'],
            'rows.*.catalog_item_id' => ['required', 'integer', 'exists:catalog_items,id'],
            'rows.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'rows.*.unit_price' => ['required', 'numeric', 'min:0.01'],
            'rows.*.payment_method' => ['required', 'in:'.implode(',', PaymentMethod::values())],
            'rows.*.notes' => ['nullable', 'string', 'max:255'],
            'replace_existing' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $date = Carbon::parse($validated['date'])->toDateString();
        $catalogItems = CatalogItem::whereIn('id', collect($validated['rows'])->pluck('catalog_item_id'))->get()->keyBy('id');

        $payload = collect($validated['rows'])->map(function (array $row) use ($catalogItems): array {
            $item = $catalogItems[$row['catalog_item_id']];
            $quantity = (float) $row['quantity'];
            $unitPrice = (float) $row['unit_price'];

            return [
                'catalog_item_id' => $item->id,
                'item_name' => $item->displayName(),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => round($quantity * $unitPrice, 2),
                'payment_method' => $row['payment_method'],
                'source' => SaleSource::Manual->value,
                'notes' => $row['notes'] ?? null,
            ];
        })->all();

        $result = $this->reports->replaceDay($user, $date, $payload);

        $this->activityLogger->log(
            ActivityAction::SALE_RECORDED,
            sprintf(
                '%s recorded %d sales line(s) for %s (%s).',
                $user->name,
                $result['inserted'],
                $date,
                $user->branch?->name ?? 'the network',
            ),
            null,
            $result + ['date' => $date],
            $user,
            $user->branch,
        );

        return redirect()
            ->route('admin.sales.index')
            ->with('status', "{$result['inserted']} sale line(s) saved for {$date}.");
    }

    public function import(Request $request, SalesImportService $importer): View
    {
        Gate::authorize('import', Sale::class);

        $user = $request->user();
        $result = null;

        if ($request->hasFile('file')) {
            $validated = $request->validate([
                'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            ]);

            $result = $importer->import($validated['file'], $user);
        }

        return view('admin.sales.import', [
            'result' => $result,
            'template' => implode(',', SalesImportService::EXPECTED_HEADERS),
        ]);
    }

    public function template(Request $request, SalesImportService $importer): StreamedResponse
    {
        Gate::authorize('import', Sale::class);

        $user = $request->user();

        return response()->streamDownload(function () use ($importer, $user): void {
            echo $importer->templateCsv($user->branch);
        }, 'sales-import-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function update(Request $request, Sale $sale): RedirectResponse
    {
        Gate::authorize('update', $sale);

        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_price' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:'.implode(',', PaymentMethod::values())],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $quantity = (float) $validated['quantity'];

        $sale->update([
            ...$validated,
            'total_amount' => round($quantity * (float) $validated['unit_price'], 2),
        ]);

        return back()->with('status', 'Sale updated.');
    }

    public function destroy(Request $request, Sale $sale): RedirectResponse
    {
        Gate::authorize('delete', $sale);

        $sale->delete();

        return back()->with('status', 'Sale removed.');
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
