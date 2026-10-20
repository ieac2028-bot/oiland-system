<?php

namespace App\Http\Controllers\Timekeeping;

use App\Http\Controllers\Controller;
use App\Models\ShiftTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShiftController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);

        return view('timekeeping.shifts.index', [
            'shifts' => ShiftTemplate::query()->orderBy('start_time')->paginate(20),
            'editingShift' => null,
        ]);
    }

    public function edit(Request $request, ShiftTemplate $shift): View
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);

        return view('timekeeping.shifts.index', [
            'shifts' => ShiftTemplate::query()->orderBy('start_time')->paginate(20),
            'editingShift' => $shift,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        ShiftTemplate::create($this->validated($request));

        return back()->with('status', 'Shift template created.');
    }

    public function update(Request $request, ShiftTemplate $shift): RedirectResponse
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $shift->update($this->validated($request, $shift));

        return redirect()->route('timekeeping.shifts.index')->with('status', 'Shift template updated.');
    }

    public function toggle(Request $request, ShiftTemplate $shift): RedirectResponse
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $shift->update(['is_active' => ! $shift->is_active]);

        return back()->with('status', $shift->is_active ? 'Shift template activated.' : 'Shift template deactivated.');
    }

    /** @return array{name: string, start_time: string, end_time: string, break_minutes: int, grace_period_minutes: int} */
    private function validated(Request $request, ?ShiftTemplate $shift = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('shift_templates', 'name')->ignore($shift?->id)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:360'],
            'grace_period_minutes' => ['required', 'integer', 'min:0', 'max:180'],
        ]);
    }
}
