<?php

namespace App\Http\Controllers\Timekeeping;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Holiday;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $user = $request->user();

        return view('timekeeping.holidays.index', [
            'holidays' => Holiday::query()->with('branch')
                ->when(! $user->isSuperAdmin(), fn ($query) => $query->where('branch_id', $user->branch_id))
                ->orderByDesc('holiday_date')->paginate(30),
            'branches' => $user->isSuperAdmin() ? Branch::active()->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $data = $request->validate([
            'holiday_date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:160'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);
        $branchId = $request->user()->isSuperAdmin() ? ($data['branch_id'] ?? null) : $request->user()->branch_id;
        abort_if(! $request->user()->isSuperAdmin() && $branchId === null, 403);
        $exists = Holiday::query()->where('holiday_date', $data['holiday_date'])->where('name', $data['name'])
            ->where(function ($query) use ($branchId): void {
                $branchId === null ? $query->whereNull('branch_id') : $query->where('branch_id', $branchId);
            })->exists();
        if ($exists) {
            return back()->withErrors(['name' => 'This holiday is already configured for the selected date and branch.'])->withInput();
        }

        Holiday::create([
            'branch_id' => $branchId,
            'holiday_date' => $data['holiday_date'],
            'name' => $data['name'],
            'created_by' => $request->user()->id,
            'is_active' => true,
        ]);

        return back()->with('status', 'Holiday added to attendance calculations.');
    }

    public function toggle(Request $request, Holiday $holiday): RedirectResponse
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        abort_unless($request->user()->isSuperAdmin() || $holiday->branch_id === $request->user()->branch_id, 404);
        $holiday->update(['is_active' => ! $holiday->is_active]);

        return back()->with('status', 'Holiday status updated.');
    }
}
