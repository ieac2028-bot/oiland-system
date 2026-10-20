<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\Price;
use App\Services\PriceApprovalService;
use App\Services\PriceTrendService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Head office price management. Branch staff get read-only access to their own
 * branch prices; only Super Admins may publish a new price directly.
 */
class PriceController extends Controller
{
    public function __construct(private readonly PriceTrendService $trends) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Price::class);

        $user = $request->user();

        $prices = Price::query()
            ->current()
            ->when($user->branch_id !== null, fn ($query) => $query->where('branch_id', $user->branch_id))
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('catalog_item_id'), fn ($query) => $query->where('catalog_item_id', $request->integer('catalog_item_id')))
            ->with(['branch', 'catalogItem', 'approver'])
            ->orderBy('branch_id')
            ->orderBy('catalog_item_id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.prices.index', [
            'prices' => $prices,
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'catalogItems' => CatalogItem::active()->orderBy('name')->get(),
            'filters' => $request->only(['branch_id', 'catalog_item_id']),
            'canPublish' => $user->isSuperAdmin(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Price::class);

        return view('admin.prices.create', [
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'catalogItems' => CatalogItem::active()->orderBy('name')->get(),
            'selectedBranchId' => $request->integer('branch_id') ?: null,
            'selectedItemId' => $request->integer('catalog_item_id') ?: null,
        ]);
    }

    public function store(Request $request, PriceApprovalService $approvals): RedirectResponse
    {
        Gate::authorize('create', Price::class);

        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'catalog_item_id' => ['required', 'exists:catalog_items,id'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $price = $approvals->publishDirectly(
            branchId: (int) $validated['branch_id'],
            catalogItemId: (int) $validated['catalog_item_id'],
            amount: (float) $validated['price'],
            actor: $request->user(),
            note: $validated['note'] ?? null,
        );

        return redirect()
            ->route('admin.prices.index')
            ->with('status', "Price published for {$price->branch->name}.");
    }

    public function show(Price $price): View
    {
        Gate::authorize('view', $price);

        $price->load(['branch', 'catalogItem', 'approver', 'histories' => fn ($query) => $query->with('changer')->latest()->take(50)]);

        return view('admin.prices.show', [
            'price' => $price,
            'trend7' => $this->trends->dailySeries($price->branch_id, $price->catalog_item_id, 7),
            'trend30' => $this->trends->dailySeries($price->branch_id, $price->catalog_item_id, 30),
            'summary7' => $this->trends->summary($price->branch_id, $price->catalog_item_id, 7),
            'summary30' => $this->trends->summary($price->branch_id, $price->catalog_item_id, 30),
        ]);
    }
}
