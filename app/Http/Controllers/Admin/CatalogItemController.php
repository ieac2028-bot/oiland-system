<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CatalogItemType;
use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * The head office catalog of fuels and services.
 */
class CatalogItemController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CatalogItem::class);

        $items = CatalogItem::query()
            ->withCount('branches')
            ->when($request->filled('q'), fn ($query) => $query->search($request->string('q')->toString()))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status')->toString() === 'active'))
            ->orderBy('type')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.catalog.index', [
            'items' => $items,
            'types' => CatalogItemType::options(),
            'filters' => $request->only(['q', 'type', 'status']),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', CatalogItem::class);

        return view('admin.catalog.create', [
            'item' => new CatalogItem(['is_active' => true, 'type' => CatalogItemType::Product->value, 'unit' => 'liter']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', CatalogItem::class);

        $validated = $request->validate($this->rules());
        $validated['is_active'] = $request->boolean('is_active');
        $validated['fuel_type'] = ($validated['type'] ?? null) === CatalogItemType::Product->value
            ? ($validated['fuel_type'] ?? null)
            : null;
        $validated['slug'] = Str::slug($validated['name']);

        $item = CatalogItem::create($validated);

        $this->activityLogger->log(
            ActivityAction::CATALOG_CREATED,
            "{$item->type->label()} '{$item->name}' was added to the catalog.",
            $item,
            ['type' => $item->type->value, 'sku' => $item->sku],
            $request->user(),
        );

        return redirect()
            ->route('admin.catalog.index')
            ->with('status', "'{$item->name}' added to the catalog.");
    }

    public function edit(CatalogItem $catalogItem): View
    {
        Gate::authorize('update', $catalogItem);

        return view('admin.catalog.edit', [
            'item' => $catalogItem,
        ]);
    }

    public function update(Request $request, CatalogItem $catalogItem): RedirectResponse
    {
        Gate::authorize('update', $catalogItem);

        $validated = $request->validate($this->rules($catalogItem));
        $validated['is_active'] = $request->boolean('is_active');
        $validated['fuel_type'] = ($validated['type'] ?? null) === CatalogItemType::Product->value
            ? ($validated['fuel_type'] ?? null)
            : null;
        $validated['slug'] = Str::slug($validated['name']);

        $catalogItem->update($validated);

        $this->activityLogger->log(
            ActivityAction::CATALOG_UPDATED,
            "Catalog item '{$catalogItem->name}' was updated.",
            $catalogItem,
            ['type' => $catalogItem->type->value],
            $request->user(),
        );

        return redirect()
            ->route('admin.catalog.index')
            ->with('status', "'{$catalogItem->name}' updated.");
    }

    public function destroy(Request $request, CatalogItem $catalogItem): RedirectResponse
    {
        Gate::authorize('delete', $catalogItem);

        $name = $catalogItem->name;
        $catalogItem->delete();

        $this->activityLogger->log(
            ActivityAction::CATALOG_DELETED,
            "Catalog item '{$name}' was removed.",
            null,
            ['name' => $name],
            $request->user(),
        );

        return redirect()->route('admin.catalog.index')->with('status', "'{$name}' deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(?CatalogItem $catalogItem = null): array
    {
        $id = $catalogItem?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', CatalogItemType::values())],
            'sku' => ['nullable', 'string', 'max:40', 'unique:catalog_items,sku'.($id ? ','.$id : '')],
            'fuel_type' => ['nullable', 'string', 'max:30'],
            'unit' => ['nullable', 'string', 'max:30'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
