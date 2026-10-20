<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CatalogItemType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CatalogItem;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Toggles which products and services a branch actually offers.
 */
class BranchAvailabilityController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function edit(Request $request, Branch $branch): View
    {
        Gate::authorize('manageAvailability', $branch);

        $catalogItems = CatalogItem::query()
            ->active()
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $availability = $branch->catalogItems()->get()->keyBy('id');

        return view('admin.branches.availability', [
            'branch' => $branch,
            'products' => $catalogItems->filter->isProduct(),
            'services' => $catalogItems->filter->isService(),
            'availability' => $availability,
            'typeLabels' => CatalogItemType::options(),
        ]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        Gate::authorize('manageAvailability', $branch);

        $validated = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*' => ['integer', 'exists:catalog_items,id'],
        ]);

        $selected = collect($validated['items'] ?? [])->map(fn ($id) => (int) $id)->all();
        $notes = $request->input('notes', []);

        $branch->catalogItems()->detach();

        $sync = collect($selected)->mapWithKeys(fn (int $id): array => [
            $id => [
                'is_available' => true,
                'notes' => is_array($notes) ? ($notes[$id] ?? null) : null,
            ],
        ])->all();

        $branch->catalogItems()->sync($sync);

        $this->activityLogger->log(
            ActivityAction::CATALOG_AVAILABILITY_UPDATED,
            sprintf('%s set the availability list for %s (%d item(s)).', $request->user()->name, $branch->name, count($selected)),
            $branch,
            ['count' => count($selected)],
            $request->user(),
            $branch,
        );

        return back()->with('status', 'Availability updated.');
    }
}
