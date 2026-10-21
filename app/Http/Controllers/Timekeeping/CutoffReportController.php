<?php

namespace App\Http\Controllers\Timekeeping;

use App\Enums\LeaveRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Services\AttendanceComputationService;
use App\Support\CsvSafe;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CutoffReportController extends Controller
{
    public function index(Request $request, AttendanceComputationService $computation): View
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);

        return view('timekeeping.reports.index', $this->reportData($request, $computation));
    }

    public function csv(Request $request, AttendanceComputationService $computation): StreamedResponse
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $data = $this->reportData($request, $computation);
        $filename = 'attendance-cutoff-'.$data['start']->format('Y-m-d').'-'.$data['end']->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Employee ID', 'Employee', 'Position', 'Branch', 'Scheduled days', 'Present days', 'Absences', 'Late occurrences', 'Tardy minutes', 'Undertime minutes', 'Regular hours', 'Approved overtime hours', 'Approved leave days', 'Incomplete records']);
            foreach ($data['rows'] as $row) {
                fputcsv($output, [
                    CsvSafe::cell($row['employee']->employee_number), CsvSafe::cell($row['employee']->user->name), CsvSafe::cell($row['employee']->job_title),
                    CsvSafe::cell($row['employee']->user->branch?->name), $row['summary']['scheduled_workdays'], $row['summary']['present_days'],
                    $row['summary']['absences'], $row['summary']['late_occurrences'], $row['summary']['tardiness_minutes'],
                    $row['summary']['undertime_minutes'], $row['summary']['regular_hours'], $row['summary']['approved_overtime_hours'],
                    $row['summary']['approved_leave_days'], $row['summary']['incomplete_records'],
                ]);
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(Request $request, AttendanceComputationService $computation)
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $data = $this->reportData($request, $computation);

        return Pdf::loadView('timekeeping.reports.print', $data)
            ->setPaper('a4', 'landscape')
            ->download('attendance-cutoff-'.$data['start']->format('Y-m-d').'-'.$data['end']->format('Y-m-d').'.pdf');
    }

    public function excel(Request $request, AttendanceComputationService $computation): StreamedResponse
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $data = $this->reportData($request, $computation);
        $filename = 'attendance-cutoff-'.$data['start']->format('Y-m-d').'-'.$data['end']->format('Y-m-d').'.xls';
        $content = view('timekeeping.reports.excel', $data)->render();

        return response()->streamDownload(static function () use ($content): void {
            echo $content;
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']);
    }

    public function word(Request $request, AttendanceComputationService $computation): StreamedResponse
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $data = $this->reportData($request, $computation);
        $filename = 'attendance-cutoff-'.$data['start']->format('Y-m-d').'-'.$data['end']->format('Y-m-d').'.doc';
        $content = view('timekeeping.reports.word', $data)->render();

        return response()->streamDownload(static function () use ($content): void {
            echo $content;
        }, $filename, ['Content-Type' => 'application/msword; charset=UTF-8']);
    }

    /** @return array<string, mixed> */
    private function reportData(Request $request, AttendanceComputationService $computation): array
    {
        $input = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'cutoff' => ['nullable', 'integer', 'in:1,2'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'department' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:present,late,absent,incomplete,approved_leave,holiday'],
        ]);
        $month = (int) ($input['month'] ?? now(AttendanceComputationService::LOCAL_TIMEZONE)->month);
        $year = (int) ($input['year'] ?? now(AttendanceComputationService::LOCAL_TIMEZONE)->year);
        $cutoff = (int) ($input['cutoff'] ?? 1);
        $monthStart = CarbonImmutable::create($year, $month, 1, 0, 0, 0, AttendanceComputationService::LOCAL_TIMEZONE);
        $start = $cutoff === 1 ? $monthStart : $monthStart->day(16);
        $end = $cutoff === 1 ? $monthStart->day(15) : $monthStart->endOfMonth();

        $employees = Employee::query()->with([
            'user.branch', 'defaultShift',
            'schedules' => fn ($query) => $query->whereBetween('shift_date', [$start->toDateString(), $end->toDateString()])->with('shift'),
            'attendanceRecords' => fn ($query) => $query->whereBetween('shift_date', [$start->toDateString(), $end->toDateString()]),
            'leaveRequests' => fn ($query) => $query->where('status', LeaveRequestStatus::Approved->value)
                ->whereDate('start_date', '<=', $end->toDateString())->whereDate('end_date', '>=', $start->toDateString()),
        ])
            ->where('employment_status', 'active')
            ->whereNotNull('user_id')
            ->when(! $request->user()->isSuperAdmin(), fn ($query) => $query->whereHas('user', fn ($user) => $user->where('branch_id', $request->user()->branch_id)))
            ->when(isset($input['employee_id']), fn ($query) => $query->whereKey($input['employee_id']))
            ->when(isset($input['branch_id']) && $request->user()->isSuperAdmin(), fn ($query) => $query->whereHas('user', fn ($user) => $user->where('branch_id', $input['branch_id'])))
            ->when(isset($input['department']), fn ($query) => $query->where('department', $input['department']))
            ->orderBy('employee_number')->get();

        $rows = $employees->map(function (Employee $employee) use ($computation, $start, $end): array {
            return ['employee' => $employee, 'summary' => $computation->summarize($employee, $start, $end)];
        });

        if (isset($input['status'])) {
            $holidays = Holiday::query()->where('is_active', true)->whereBetween('holiday_date', [$start->toDateString(), $end->toDateString()])->get();
            $rows = $rows->filter(function (array $row) use ($input, $computation, $start, $end, $holidays): bool {
                $employee = $row['employee'];
                for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
                    $schedule = $employee->schedules->first(fn ($item) => $item->shift_date->toDateString() === $date->toDateString());
                    if ($schedule === null && $employee->defaultShift !== null) {
                        $schedule = new EmployeeSchedule(['shift_date' => $date->toDateString(), 'is_rest_day' => false]);
                        $schedule->setRelation('shift', $employee->defaultShift);
                    }
                    $record = $employee->attendanceRecords->first(fn ($item) => $item->shift_date->toDateString() === $date->toDateString());
                    $leave = $employee->leaveRequests->contains(fn ($item) => $item->start_date->toDateString() <= $date->toDateString() && $item->end_date->toDateString() >= $date->toDateString());
                    $holiday = $holidays->contains(fn ($item) => $item->holiday_date->toDateString() === $date->toDateString()
                        && ($item->branch_id === null || $item->branch_id === $employee->user->branch_id));
                    if ($computation->computeDay($schedule, $record, $leave, $date, null, $holiday)['status'] === $input['status']) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        return [
            'rows' => $rows,
            'start' => $start,
            'end' => $end,
            'month' => $month,
            'year' => $year,
            'cutoff' => $cutoff,
            'filters' => $input,
            'departments' => Employee::query()->where('employment_status', 'active')
                ->when(! $request->user()->isSuperAdmin(), fn ($query) => $query->whereHas('user', fn ($user) => $user->where('branch_id', $request->user()->branch_id)))
                ->distinct()->orderBy('department')->pluck('department'),
            'employeeOptions' => Employee::query()->with('user')->where('employment_status', 'active')
                ->whereNotNull('user_id')
                ->when(! $request->user()->isSuperAdmin(), fn ($query) => $query->whereHas('user', fn ($user) => $user->where('branch_id', $request->user()->branch_id)))
                ->orderBy('employee_number')->get(),
            'branches' => $request->user()->isSuperAdmin() ? Branch::orderBy('name')->get(['id', 'name']) : collect(),
        ];
    }
}
