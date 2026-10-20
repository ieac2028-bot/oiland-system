<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\ShiftTemplate;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AttendanceComputationService
{
    public const LOCAL_TIMEZONE = 'Asia/Manila';

    public function scheduleOn(Employee $employee, CarbonInterface|string $date): ?EmployeeSchedule
    {
        $dateKey = CarbonImmutable::parse($date, self::LOCAL_TIMEZONE)->toDateString();
        $schedule = $employee->schedules->first(fn (EmployeeSchedule $row): bool => $row->shift_date->toDateString() === $dateKey);

        if ($schedule !== null) {
            return $schedule;
        }

        if ($employee->defaultShift === null) {
            return null;
        }

        return new EmployeeSchedule([
            'employee_id' => $employee->id,
            'shift_date' => $dateKey,
            'shift_template_id' => $employee->default_shift_template_id,
            'is_rest_day' => false,
        ])->setRelation('shift', $employee->defaultShift);
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable, work_minutes: int} */
    public function shiftBounds(ShiftTemplate $shift, CarbonInterface|string $date): array
    {
        $dateKey = CarbonImmutable::parse($date, self::LOCAL_TIMEZONE)->toDateString();
        $start = CarbonImmutable::parse($dateKey.' '.$shift->start_time, self::LOCAL_TIMEZONE);
        $end = CarbonImmutable::parse($dateKey.' '.$shift->end_time, self::LOCAL_TIMEZONE);

        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        return [
            'start' => $start,
            'end' => $end,
            'work_minutes' => max(0, (int) $start->diffInMinutes($end) - $shift->break_minutes),
        ];
    }

    /** @return array{status: string, worked_minutes: int, late_minutes: int, undertime_minutes: int, overtime_minutes: int, approved_overtime_minutes: int, scheduled_minutes: int} */
    public function computeDay(
        ?EmployeeSchedule $schedule,
        ?AttendanceRecord $record,
        bool $approvedLeave,
        CarbonInterface|string $date,
        ?CarbonImmutable $nowUtc = null,
        bool $holiday = false,
    ): array {
        $nowUtc ??= CarbonImmutable::now('UTC');

        if ($holiday) {
            return $this->emptyDay('holiday');
        }

        if ($approvedLeave) {
            return $this->emptyDay('approved_leave');
        }

        if ($schedule === null) {
            return $this->emptyDay('unscheduled');
        }

        if ($schedule->is_rest_day) {
            return $this->emptyDay('rest_day');
        }

        $shift = $schedule->shift;

        if ($shift === null) {
            return $this->emptyDay('unscheduled');
        }

        $bounds = $this->shiftBounds($shift, $date);
        $startUtc = $bounds['start']->utc();
        $endUtc = $bounds['end']->utc();
        $scheduledMinutes = $bounds['work_minutes'];

        if ($record === null || $record->time_in_at === null) {
            return $this->emptyDay($nowUtc->greaterThanOrEqualTo($endUtc) ? 'absent' : 'scheduled', $scheduledMinutes);
        }

        $timeInUtc = CarbonImmutable::parse($record->time_in_at, 'UTC');
        $graceDeadline = $startUtc->addMinutes($shift->grace_period_minutes);
        $lateMinutes = $timeInUtc->greaterThan($graceDeadline) ? (int) $graceDeadline->diffInMinutes($timeInUtc) : 0;
        $timeOutUtc = $record->time_out_at === null ? null : CarbonImmutable::parse($record->time_out_at, 'UTC');
        $effectiveOut = $timeOutUtc ?? $nowUtc;
        $workedMinutes = max(0, (int) $timeInUtc->diffInMinutes($effectiveOut) - $shift->break_minutes);
        $status = $timeOutUtc === null ? 'incomplete' : ($lateMinutes > 0 ? 'late' : 'present');
        $completedWorkMinutes = $timeOutUtc === null ? 0 : $workedMinutes;

        return [
            'status' => $status,
            'worked_minutes' => $workedMinutes,
            'late_minutes' => $lateMinutes,
            'undertime_minutes' => $timeOutUtc === null ? 0 : max(0, $scheduledMinutes - $completedWorkMinutes),
            'overtime_minutes' => $timeOutUtc === null ? 0 : max(0, $completedWorkMinutes - $scheduledMinutes),
            'approved_overtime_minutes' => $record->approved_overtime_minutes,
            'scheduled_minutes' => $scheduledMinutes,
        ];
    }

    /** @param Collection<int, EmployeeSchedule> $schedules @param Collection<int, AttendanceRecord> $records @param Collection<int, LeaveRequest> $leaveRequests @return array<string, int|float> */
    public function summarize(Employee $employee, CarbonInterface|string $startDate, CarbonInterface|string $endDate, ?CarbonImmutable $nowUtc = null): array
    {
        $start = CarbonImmutable::parse($startDate, self::LOCAL_TIMEZONE)->startOfDay();
        $end = CarbonImmutable::parse($endDate, self::LOCAL_TIMEZONE)->startOfDay();
        $nowUtc ??= CarbonImmutable::now('UTC');
        $schedules = $employee->schedules;
        $records = $employee->attendanceRecords;
        $leaveRequests = $employee->leaveRequests->where('status', 'approved');
        $holidayDates = Holiday::query()->where('is_active', true)
            ->whereDate('holiday_date', '>=', $start->toDateString())
            ->whereDate('holiday_date', '<=', $end->toDateString())
            ->where(function ($query) use ($employee): void {
                $query->whereNull('branch_id')->orWhere('branch_id', $employee->user?->branch_id);
            })->pluck('holiday_date')->map(fn ($date): string => CarbonImmutable::parse($date)->toDateString())->all();
        $totals = [
            'scheduled_workdays' => 0,
            'present_days' => 0,
            'absences' => 0,
            'late_occurrences' => 0,
            'tardiness_minutes' => 0,
            'undertime_minutes' => 0,
            'regular_minutes' => 0,
            'approved_overtime_minutes' => 0,
            'approved_leave_days' => 0,
            'incomplete_records' => 0,
        ];

        for ($day = $start; $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
            $schedule = $this->scheduleOnLoaded($employee, $schedules, $day);
            $record = $records->first(fn (AttendanceRecord $row): bool => $row->shift_date->toDateString() === $day->toDateString());
            $approvedLeave = $leaveRequests->contains(fn (LeaveRequest $request): bool => $request->start_date->toDateString() <= $day->toDateString() && $request->end_date->toDateString() >= $day->toDateString());
            $metrics = $this->computeDay($schedule, $record, $approvedLeave, $day, $nowUtc, in_array($day->toDateString(), $holidayDates, true));

            if ($metrics['status'] === 'approved_leave') {
                $totals['approved_leave_days']++;
            }

            if (in_array($metrics['status'], ['rest_day', 'unscheduled', 'approved_leave', 'holiday'], true)) {
                continue;
            }

            $totals['scheduled_workdays']++;
            if (in_array($metrics['status'], ['present', 'late'], true)) {
                $totals['present_days']++;
            }
            if ($metrics['status'] === 'absent') {
                $totals['absences']++;
            }
            if ($metrics['late_minutes'] > 0) {
                $totals['late_occurrences']++;
            }
            if ($metrics['status'] === 'incomplete') {
                $totals['incomplete_records']++;
            }
            $totals['tardiness_minutes'] += $metrics['late_minutes'];
            $totals['undertime_minutes'] += $metrics['undertime_minutes'];
            $totals['regular_minutes'] += min($metrics['worked_minutes'], $metrics['scheduled_minutes']);
            $totals['approved_overtime_minutes'] += $metrics['approved_overtime_minutes'];
        }

        $totals['regular_hours'] = round($totals['regular_minutes'] / 60, 2);
        $totals['approved_overtime_hours'] = round($totals['approved_overtime_minutes'] / 60, 2);

        return $totals;
    }

    /** @param Collection<int, EmployeeSchedule> $schedules */
    private function scheduleOnLoaded(Employee $employee, Collection $schedules, CarbonImmutable $date): ?EmployeeSchedule
    {
        $dateKey = $date->toDateString();
        $schedule = $schedules->first(fn (EmployeeSchedule $row): bool => $row->shift_date->toDateString() === $dateKey);

        if ($schedule !== null) {
            return $schedule;
        }

        if ($employee->defaultShift === null) {
            return null;
        }

        return new EmployeeSchedule([
            'employee_id' => $employee->id,
            'shift_date' => $dateKey,
            'shift_template_id' => $employee->default_shift_template_id,
            'is_rest_day' => false,
        ])->setRelation('shift', $employee->defaultShift);
    }

    /** @return array{status: string, worked_minutes: int, late_minutes: int, undertime_minutes: int, overtime_minutes: int, approved_overtime_minutes: int, scheduled_minutes: int} */
    private function emptyDay(string $status, int $scheduledMinutes = 0): array
    {
        return [
            'status' => $status,
            'worked_minutes' => 0,
            'late_minutes' => 0,
            'undertime_minutes' => 0,
            'overtime_minutes' => 0,
            'approved_overtime_minutes' => 0,
            'scheduled_minutes' => $scheduledMinutes,
        ];
    }
}
