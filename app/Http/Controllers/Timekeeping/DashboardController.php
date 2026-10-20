<?php

namespace App\Http\Controllers\Timekeeping;

use App\Enums\LeaveRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Services\AttendanceComputationService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AttendanceComputationService $computation): View
    {
        $user = $request->user();
        $today = CarbonImmutable::now(AttendanceComputationService::LOCAL_TIMEZONE)->startOfDay();
        $holidayBranchIds = Holiday::query()->where('is_active', true)->whereDate('holiday_date', $today->toDateString())->pluck('branch_id');
        $globalHoliday = $holidayBranchIds->contains(null);
        $holidayForBranch = fn (?int $branchId): bool => $globalHoliday || $holidayBranchIds->contains($branchId);

        if ($user->isTimekeepingEmployee()) {
            $employee = Employee::query()
                ->with([
                    'user.branch', 'defaultShift',
                    'schedules' => fn ($query) => $query->whereBetween('shift_date', [$today->subDay()->toDateString(), $today->addDays(30)->toDateString()])->with('shift'),
                    'attendanceRecords' => fn ($query) => $query->whereBetween('shift_date', [$today->subDays(30)->toDateString(), $today->toDateString()]),
                    'leaveRequests' => fn ($query) => $query->whereDate('end_date', '>=', $today->subDays(90)->toDateString())->with('type'),
                ])
                ->where('user_id', $user->id)
                ->firstOrFail();
            $todaySchedule = $computation->scheduleOn($employee, $today);
            $todayRecord = $employee->attendanceRecords->first(fn (AttendanceRecord $record): bool => $record->shift_date->toDateString() === $today->toDateString());
            $onLeave = $employee->leaveRequests->contains(fn (LeaveRequest $leave): bool => $leave->status === LeaveRequestStatus::Approved->value
                && $leave->start_date->toDateString() <= $today->toDateString()
                && $leave->end_date->toDateString() >= $today->toDateString());

            return view('timekeeping.employee-dashboard', [
                'employee' => $employee,
                'today' => $today,
                'todaySchedule' => $todaySchedule,
                'todayRecord' => $todayRecord,
                'openRecord' => $employee->attendanceRecords->first(fn (AttendanceRecord $record): bool => $record->time_in_at !== null && $record->time_out_at === null),
                'todayMetrics' => $computation->computeDay($todaySchedule, $todayRecord, $onLeave, $today, null, $holidayForBranch($employee->user->branch_id)),
                'history' => $employee->attendanceRecords->sortByDesc('shift_date')->take(14),
                'upcomingSchedules' => $employee->schedules->where('shift_date', '>=', $today->toDateString())->sortBy('shift_date')->take(7),
                'recentLeaveRequests' => $employee->leaveRequests->sortByDesc('created_at')->take(5),
                'isManager' => false,
            ]);
        }

        abort_unless($user->canManageTimekeeping(), 403);

        $employees = Employee::query()
            ->with([
                'user.branch', 'defaultShift',
                'schedules' => fn ($query) => $query->whereBetween('shift_date', [$today->subDay()->toDateString(), $today->addDays(7)->toDateString()])->with('shift'),
                'attendanceRecords' => fn ($query) => $query->whereBetween('shift_date', [$today->subDays(30)->toDateString(), $today->toDateString()]),
                'leaveRequests' => fn ($query) => $query->whereIn('status', [LeaveRequestStatus::Pending->value, LeaveRequestStatus::Approved->value])
                    ->whereDate('end_date', '>=', $today->toDateString()),
            ])
            ->where('employment_status', 'active')
            ->whereNotNull('user_id')
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('branch_id', $user->branch_id)))
            ->get();
        $metricsByEmployee = $employees->mapWithKeys(function (Employee $employee) use ($computation, $today, $holidayForBranch): array {
            $schedule = $computation->scheduleOn($employee, $today);
            $record = $employee->attendanceRecords->first(fn (AttendanceRecord $row): bool => $row->shift_date->toDateString() === $today->toDateString());
            $leave = $employee->leaveRequests->contains(fn (LeaveRequest $row): bool => $row->status === LeaveRequestStatus::Approved->value
                && $row->start_date->toDateString() <= $today->toDateString()
                && $row->end_date->toDateString() >= $today->toDateString());

            return [$employee->id => $computation->computeDay($schedule, $record, $leave, $today, null, $holidayForBranch($employee->user->branch_id))];
        });

        $chartDays = collect(range(6, 0))->map(fn (int $offset) => $today->subDays($offset));
        $chart = [
            'labels' => $chartDays->map(fn (CarbonImmutable $date) => $date->format('M j'))->values(),
            'present' => $chartDays->map(fn (CarbonImmutable $date) => AttendanceRecord::query()
                ->whereDate('shift_date', $date->toDateString())
                ->whereNotNull('time_in_at')
                ->whereHas('employee.user', fn ($query) => $query->when(! $user->isSuperAdmin(), fn ($scoped) => $scoped->where('branch_id', $user->branch_id)))
                ->count())->values(),
        ];

        return view('timekeeping.dashboard', [
            'today' => $today,
            'employees' => $employees,
            'metricsByEmployee' => $metricsByEmployee,
            'activeCount' => $employees->count(),
            'presentCount' => $metricsByEmployee->filter(fn (array $m): bool => in_array($m['status'], ['present', 'late', 'incomplete'], true))->count(),
            'lateCount' => $metricsByEmployee->where('status', 'late')->count(),
            'absentCount' => $metricsByEmployee->where('status', 'absent')->count(),
            'leaveCount' => $metricsByEmployee->where('status', 'approved_leave')->count(),
            'incompleteCount' => $metricsByEmployee->where('status', 'incomplete')->count(),
            'pendingLeaveCount' => LeaveRequest::query()->where('status', LeaveRequestStatus::Pending->value)
                ->whereHas('employee.user', fn ($query) => $query->when(! $user->isSuperAdmin(), fn ($scoped) => $scoped->where('branch_id', $user->branch_id)))
                ->count(),
            'upcomingSchedules' => EmployeeSchedule::query()
                ->with(['employee.user.branch', 'shift'])
                ->whereBetween('shift_date', [$today->toDateString(), $today->addDays(7)->toDateString()])
                ->whereHas('employee.user', fn ($query) => $query->when(! $user->isSuperAdmin(), fn ($scoped) => $scoped->where('branch_id', $user->branch_id)))
                ->orderBy('shift_date')->limit(8)->get(),
            'chart' => $chart,
            'isManager' => true,
        ]);
    }
}
