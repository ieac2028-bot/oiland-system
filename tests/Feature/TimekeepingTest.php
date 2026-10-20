<?php

namespace Tests\Feature;

use App\Enums\LeaveRequestStatus;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Services\AttendanceComputationService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimekeepingTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_time_in_and_out_once_for_an_assigned_shift(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-20 08:59:00', 'Asia/Manila'));
        [$employee, $shift] = $this->employeeWithShift();
        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'shift_date' => '2026-10-20',
            'shift_template_id' => $shift->id,
            'assigned_by' => $employee->user_id,
        ]);

        $this->actingAs($employee->user)
            ->post(route('timekeeping.attendance.time-in'))
            ->assertRedirect();

        $record = AttendanceRecord::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('2026-10-20', $record->shift_date->toDateString());
        $this->assertNotNull($record->time_in_at);
        $this->assertNull($record->time_out_at);

        $this->actingAs($employee->user)
            ->from(route('timekeeping.dashboard'))
            ->post(route('timekeeping.attendance.time-in'))
            ->assertUnprocessable();

        Carbon::setTestNow(Carbon::parse('2026-10-20 17:01:00', 'Asia/Manila'));
        $this->actingAs($employee->user)
            ->post(route('timekeeping.attendance.time-out'))
            ->assertRedirect();
        $this->assertNotNull($record->fresh()->time_out_at);

        $this->actingAs($employee->user)
            ->from(route('timekeeping.dashboard'))
            ->post(route('timekeeping.attendance.time-in'))
            ->assertUnprocessable();

        Carbon::setTestNow();
    }

    public function test_employee_cannot_view_hr_employee_directory_or_other_employee_records(): void
    {
        [$employee] = $this->employeeWithShift();

        $this->actingAs($employee->user)
            ->get(route('timekeeping.employees.index'))
            ->assertForbidden();

        $this->actingAs($employee->user)
            ->get(route('timekeeping.dashboard'))
            ->assertOk()
            ->assertSee($employee->employee_number);
    }

    public function test_hr_dashboard_and_employee_list_are_scoped_to_its_branch(): void
    {
        $branch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $hr = User::factory()->hrOfficer($branch)->create();
        $employee = $this->employeeForBranch($branch, 'OIL-100');
        $otherEmployee = $this->employeeForBranch($otherBranch, 'OIL-200');

        $this->actingAs($hr)
            ->get(route('timekeeping.employees.index'))
            ->assertOk()
            ->assertSee('OIL-100')
            ->assertDontSee('OIL-200');

        $this->actingAs($hr)
            ->get(route('timekeeping.employees.edit', $otherEmployee))
            ->assertNotFound();
    }

    public function test_attendance_computation_handles_overnight_shifts_lateness_and_absence(): void
    {
        $service = app(AttendanceComputationService::class);
        $shift = ShiftTemplate::create([
            'name' => 'Overnight',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'break_minutes' => 60,
            'grace_period_minutes' => 5,
            'is_active' => true,
        ]);
        $bounds = $service->shiftBounds($shift, '2026-10-20');
        $this->assertSame('2026-10-20 22:00:00', $bounds['start']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-21 06:00:00', $bounds['end']->format('Y-m-d H:i:s'));
        $this->assertSame(420, $bounds['work_minutes']);

        $schedule = new EmployeeSchedule(['shift_date' => '2026-10-20', 'is_rest_day' => false]);
        $schedule->setRelation('shift', $shift);
        $record = new AttendanceRecord([
            'time_in_at' => '2026-10-20 14:10:00',
            'time_out_at' => '2026-10-20 22:30:00',
        ]);
        $late = $service->computeDay($schedule, $record, false, '2026-10-20', CarbonImmutable::parse('2026-10-21 01:00:00', 'UTC'));
        $this->assertSame('late', $late['status']);
        $this->assertSame(5, $late['late_minutes']);

        $absent = $service->computeDay($schedule, null, false, '2026-10-20', CarbonImmutable::parse('2026-10-21 02:00:00', 'UTC'));
        $this->assertSame('absent', $absent['status']);
    }

    public function test_approved_leave_and_holidays_are_not_counted_as_absences(): void
    {
        $branch = Branch::factory()->create();
        [$employee, $shift] = $this->employeeWithShift($branch);
        $employee->update(['default_shift_template_id' => $shift->id]);
        $leaveType = LeaveType::create(['name' => 'Annual leave', 'is_active' => true]);
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-20',
            'reason' => 'Personal leave',
            'status' => LeaveRequestStatus::Approved->value,
        ]);
        Holiday::create([
            'branch_id' => $branch->id,
            'holiday_date' => '2026-10-21',
            'name' => 'Branch holiday',
            'is_active' => true,
        ]);
        $employee->load(['user', 'defaultShift', 'schedules.shift', 'attendanceRecords', 'leaveRequests']);

        $summary = app(AttendanceComputationService::class)->summarize($employee, '2026-10-20', '2026-10-21', CarbonImmutable::parse('2026-10-23 00:00:00', 'UTC'));
        $this->assertSame(0, $summary['absences']);
        $this->assertSame(1, $summary['approved_leave_days']);
        $this->assertSame(0, $summary['scheduled_workdays']);
    }

    public function test_hr_correction_requires_a_reason_and_records_audit_history(): void
    {
        $branch = Branch::factory()->create();
        $hr = User::factory()->hrOfficer($branch)->create();
        $employee = $this->employeeForBranch($branch, 'OIL-300');
        $record = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'shift_date' => '2026-10-20',
            'time_in_at' => '2026-10-20 01:00:00',
            'time_out_at' => '2026-10-20 09:00:00',
            'created_by' => $employee->user_id,
            'updated_by' => $employee->user_id,
        ]);

        $this->actingAs($hr)
            ->patch(route('timekeeping.attendance.correct', $record), [
                'time_in_at' => '2026-10-20T09:15',
                'time_out_at' => '2026-10-20T17:00',
                'approved_overtime_minutes' => 30,
                'reason' => 'Corrected from signed station log.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_record_id' => $record->id,
            'corrected_by' => $hr->id,
            'reason' => 'Corrected from signed station log.',
        ]);
        $this->assertSame(30, $record->fresh()->approved_overtime_minutes);
    }

    /** @return array{Employee, ShiftTemplate} */
    private function employeeWithShift(?Branch $branch = null): array
    {
        $branch ??= Branch::factory()->create();
        $user = User::factory()->employee($branch)->create();
        $shift = ShiftTemplate::create([
            'name' => 'Day shift '.fake()->unique()->numberBetween(1000, 99999),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'break_minutes' => 60,
            'grace_period_minutes' => 0,
            'is_active' => true,
        ]);
        $employee = $user->employee()->create([
            'employee_number' => 'OIL-'.fake()->unique()->numerify('####'),
            'job_title' => 'Station Attendant',
            'department' => 'Operations',
            'date_hired' => '2025-01-01',
            'employment_status' => 'active',
            'default_shift_template_id' => $shift->id,
        ]);

        return [$employee, $shift];
    }

    private function employeeForBranch(Branch $branch, string $employeeNumber): Employee
    {
        $user = User::factory()->employee($branch)->create();
        $shift = ShiftTemplate::create([
            'name' => 'Shift for '.$employeeNumber,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'break_minutes' => 60,
            'grace_period_minutes' => 0,
            'is_active' => true,
        ]);

        return $user->employee()->create([
            'employee_number' => $employeeNumber,
            'job_title' => 'Station Attendant',
            'department' => 'Operations',
            'date_hired' => '2025-01-01',
            'employment_status' => 'active',
            'default_shift_template_id' => $shift->id,
        ]);
    }
}
