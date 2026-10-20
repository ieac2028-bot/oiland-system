<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PriceChangeStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\Price;
use App\Models\PriceChangeRequest;
use App\Services\PriceApprovalService;
use App\Services\PriceTrendService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The tiered pricing workflow screen: branch staff submit requests, head
 * office approves or rejects them.
 */
class PriceChangeRequestController extends Controller
{
    public function __construct(
        private readonly PriceApprovalService $approvals,
        private readonly PriceTrendService $trends,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', PriceChangeRequest::class);

        $user = $request->user();
        $status = $request->filled('status') ? PriceChangeStatus::tryFrom($request->string('status')->toString()) : null;

        $requests = PriceChangeRequest::query()
            ->forBranch($user->branch_id)
            ->status($status)
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->with(['branch', 'catalogItem', 'requester', 'reviewer'])
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [PriceChangeStatus::Pending->value])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.price-changes.index', [
            'requests' => $requests,
            'statuses' => PriceChangeStatus::options(),
            'branches' => $user->isSuperAdmin() ? Branch::orderBy('name')->get(['id', 'name']) : collect(),
            'filters' => $request->only(['status', 'branch_id']),
            'pendingCount' => PriceChangeRequest::forBranch($user->branch_id)->pending()->count(),
            'canReview' => $user->isSuperAdmin(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', PriceChangeRequest::class);

        $user = $request->user();
        $branches = $user->isSuperAdmin()
            ? Branch::orderBy('name')->get()
            : collect([$user->branch]);

        $branchId = $user->branch_id ?? ($request->filled('branch_id') ? $request->integer('branch_id') : null);

        $availableItems = Branch::find($branchId)?->catalogItems()
            ->wherePivot('is_available', true)
            ->where('catalog_items.is_active', true)
            ->orderBy('catalog_items.name')
            ->get() ?? collect();

        return view('admin.price-changes.create', [
            'branches' => $branches->filter(),
            'availableItems' => $availableItems,
            'selectedBranchId' => $branchId,
            'existingRequest' => $branchId === null
                ? null
                : PriceChangeRequest::forBranch($branchId)->pending()->with('catalogItem')->first(),
            'currentPrice' => $this->currentPrice($branchId, $request->integer('catalog_item_id')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', PriceChangeRequest::class);

        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'catalog_item_id' => ['required', 'exists:catalog_items,id'],
            'new_price' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $branchId = (int) $validated['branch_id'];
        $catalogItemId = (int) $validated['catalog_item_id'];

        abort_unless($user->managesBranchId($branchId), 403);

        // A branch can only have one open request per item: reuse the pending
        // row so the reviewer sees a single thread, otherwise open a new one.
        $priceChangeRequest = PriceChangeRequest::query()
            ->where('branch_id', $branchId)
            ->where('catalog_item_id', $catalogItemId)
            ->pending()
            ->first() ?? new PriceChangeRequest([
                'branch_id' => $branchId,
                'catalog_item_id' => $catalogItemId,
            ]);

        $this->approvals->requestPriceChange(
            $priceChangeRequest,
            [
                'old_price' => $this->currentPrice($branchId, $catalogItemId)['price'],
                'new_price' => $validated['new_price'],
                'note' => $validated['note'] ?? null,
            ],
            $user,
        );

        return redirect()
            ->route('admin.price-changes.index')
            ->with('status', 'Price change submitted for head office approval.');
    }

    public function show(PriceChangeRequest $priceChangeRequest): View
    {
        Gate::authorize('view', $priceChangeRequest);

        $priceChangeRequest->load(['branch', 'catalogItem', 'requester', 'reviewer', 'histories']);

        return view('admin.price-changes.show', [
            'priceChangeRequest' => $priceChangeRequest,
            'trend' => $this->trends->dailySeries($priceChangeRequest->branch_id, $priceChangeRequest->catalog_item_id, 30),
            'summary' => $this->trends->summary($priceChangeRequest->branch_id, $priceChangeRequest->catalog_item_id, 30),
        ]);
    }

    public function approve(Request $request, PriceChangeRequest $priceChangeRequest): RedirectResponse
    {
        Gate::authorize('approve', $priceChangeRequest);

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->approvals->approve($priceChangeRequest, $request->user(), $validated['review_note'] ?? null);

        return back()->with('status', 'Price change approved and published.');
    }

    public function reject(Request $request, PriceChangeRequest $priceChangeRequest): RedirectResponse
    {
        Gate::authorize('reject', $priceChangeRequest);

        $validated = $request->validate([
            'review_note' => ['required', 'string', 'max:1000'],
        ]);

        $this->approvals->reject($priceChangeRequest, $request->user(), $validated['review_note']);

        return back()->with('status', 'Price change rejected.');
    }

    public function destroy(Request $request, PriceChangeRequest $priceChangeRequest): RedirectResponse
    {
        Gate::authorize('delete', $priceChangeRequest);

        $priceChangeRequest->delete();

        return redirect()->route('admin.price-changes.index')->with('status', 'Request withdrawn.');
    }

    /**
     * @return array{item: CatalogItem|null, price: string|null, history: mixed}
     */
    protected function currentPrice(?int $branchId, ?int $catalogItemId): array
    {
        if ($branchId === null || $catalogItemId === null) {
            return ['item' => null, 'price' => null, 'history' => null];
        }

        $price = Price::query()
            ->where('branch_id', $branchId)
            ->where('catalog_item_id', $catalogItemId)
            ->current()
            ->first();

        return [
            'item' => $price?->catalogItem,
            'price' => $price?->price,
            'history' => $price,
        ];
    }
}
