<?php

namespace App\Http\Controllers\Timekeeping;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\ShiftTemplate;
use App\Services\AttendanceComputationService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $start = CarbonImmutable::parse($request->input('start', now(AttendanceComputationService::LOCAL_TIMEZONE)->toDateString()), AttendanceComputationService::LOCAL_TIMEZONE)->startOfDay();
        $end = CarbonImmutable::parse($request->input('end', $start->addDays(13)->toDateString()), AttendanceComputationService::LOCAL_TIMEZONE)->startOfDay();
        abort_if($end->lessThan($start) || $start->diffInDays($end) > 62, 422, 'Choose a schedule range of up to 63 days.');
        $schedules = EmployeeSchedule::query()->with(['employee.user.branch', 'shift'])
            ->whereBetween('shift_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('employee.user', fn ($query) => $query->when(! $request->user()->isSuperAdmin(), fn ($scoped) => $scoped->where('branch_id', $request->user()->branch_id)))
            ->orderBy('shift_date')->orderBy('employee_id')->paginate(50)->withQueryString();
        $employees = Employee::query()->active()->with('user.branch')
            ->whereHas('user', fn ($query) => $query->when(! $request->user()->isSuperAdmin(), fn ($scoped) => $scoped->where('branch_id', $request->user()->branch_id)))
            ->orderBy('employee_number')->get();

        return view('timekeeping.schedules.index', [
            'schedules' => $schedules,
            'employees' => $employees,
            'shifts' => ShiftTemplate::query()->where('is_active', true)->orderBy('start_time')->get(),
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ]);
    }

    public function store(Request $request, AttendanceComputationService $computation): RedirectResponse
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'shift_date' => ['required', 'date'],
            'shift_template_id' => ['nullable', 'integer', 'exists:shift_templates,id'],
            'is_rest_day' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $employee = Employee::query()->with(['user', 'defaultShift', 'schedules.shift'])
            ->whereKey($data['employee_id'])->firstOrFail();
        abort_unless($request->user()->isSuperAdmin() || $employee->user->branch_id === $request->user()->branch_id, 404);
        $isRestDay = $request->boolean('is_rest_day');
        $shift = $isRestDay ? null : ShiftTemplate::query()->where('is_active', true)->findOrFail($data['shift_template_id'] ?? 0);

        if ($shift !== null) {
            $this->assertNoOverlap($employee, $shift, $data['shift_date'], $computation);
        }

        EmployeeSchedule::updateOrCreate(
            ['employee_id' => $employee->id, 'shift_date' => CarbonImmutable::parse($data['shift_date'], AttendanceComputationService::LOCAL_TIMEZONE)->toDateString()],
            [
                'shift_template_id' => $shift?->id,
                'is_rest_day' => $isRestDay,
                'assigned_by' => $request->user()->id,
                'notes' => $data['notes'] ?? null,
            ],
        );

        return back()->with('status', 'Employee schedule saved.');
    }

    private function assertNoOverlap(Employee $employee, ShiftTemplate $candidate, string $date, AttendanceComputationService $computation): void
    {
        $candidateBounds = $computation->shiftBounds($candidate, $date);
        $day = CarbonImmutable::parse($date, AttendanceComputationService::LOCAL_TIMEZONE);
        $neighbors = $employee->schedules->filter(fn (EmployeeSchedule $schedule): bool => $schedule->shift_date->toDateString() !== $day->toDateString()
            && $schedule->shift_date->between($day->subDay(), $day->addDay())
            && ! $schedule->is_rest_day
            && $schedule->shift !== null
        );

        foreach ($neighbors as $schedule) {
            $bounds = $computation->shiftBounds($schedule->shift, $schedule->shift_date);
            if ($candidateBounds['start']->lessThan($bounds['end']) && $bounds['start']->lessThan($candidateBounds['end'])) {
                abort(422, 'This shift overlaps another scheduled shift for the employee.');
            }
        }
    }
}
