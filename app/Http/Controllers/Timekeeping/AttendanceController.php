<?php

namespace App\Http\Controllers\Timekeeping;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Employee;
use App\Services\AttendanceComputationService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $date = CarbonImmutable::parse($request->input('date', now(AttendanceComputationService::LOCAL_TIMEZONE)->toDateString()), AttendanceComputationService::LOCAL_TIMEZONE)->toDateString();
        $records = AttendanceRecord::query()
            ->with(['employee.user.branch', 'employee.branch', 'employee.defaultShift', 'corrections.editor', 'importBatch.uploader'])
            ->whereDate('shift_date', $date)
            ->whereHas('employee', fn ($query) => $query->when(! $request->user()->isSuperAdmin(), fn ($scoped) => $scoped->where('branch_id', $request->user()->branch_id)))
            ->when($request->filled('status'), function ($query) use ($request): void {
                if ($request->string('status')->toString() === 'incomplete') {
                    $query->whereNotNull('time_in_at')->whereNull('time_out_at');
                }
            })
            ->orderBy('time_in_at')->paginate(30)->withQueryString();

        return view('timekeeping.attendance.index', [
            'records' => $records,
            'date' => $date,
            'branches' => $request->user()->isSuperAdmin() ? Branch::active()->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    public function timeIn(Request $request, AttendanceComputationService $computation): RedirectResponse
    {
        $employee = $this->currentEmployee($request);
        abort_unless($employee->employment_status === 'active', 403, 'Your employee account is inactive.');
        $nowUtc = CarbonImmutable::now('UTC');
        $today = CarbonImmutable::now(AttendanceComputationService::LOCAL_TIMEZONE)->startOfDay();

        DB::transaction(function () use ($employee, $request, $computation, $nowUtc, $today): void {
            $employee->newQuery()->whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $schedule = $computation->scheduleOn($employee->loadMissing(['defaultShift', 'schedules.shift']), $today);

            if ($schedule === null || $schedule->is_rest_day || $schedule->shift === null) {
                abort(422, 'There is no working shift scheduled for today.');
            }

            $openRecord = AttendanceRecord::query()->where('employee_id', $employee->id)
                ->whereNotNull('time_in_at')->whereNull('time_out_at')->lockForUpdate()->first();
            if ($openRecord !== null) {
                abort(422, 'Complete your open attendance session before recording another time-in.');
            }

            $existing = AttendanceRecord::query()->where('employee_id', $employee->id)
                ->whereDate('shift_date', $today->toDateString())->lockForUpdate()->first();

            if ($existing !== null) {
                abort(422, $existing->time_out_at === null ? 'You already have an open attendance session.' : 'You have already completed attendance for today.');
            }

            AttendanceRecord::create([
                'employee_id' => $employee->id,
                'shift_date' => $today->toDateString(),
                'time_in_at' => $nowUtc->format('Y-m-d H:i:s'),
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('status', 'Time-in recorded at '.CarbonImmutable::now(AttendanceComputationService::LOCAL_TIMEZONE)->format('g:i A').'.');
    }

    public function timeOut(Request $request): RedirectResponse
    {
        $employee = $this->currentEmployee($request);
        abort_unless($employee->employment_status === 'active', 403, 'Your employee account is inactive.');
        $nowUtc = CarbonImmutable::now('UTC');

        DB::transaction(function () use ($employee, $request, $nowUtc): void {
            $record = AttendanceRecord::query()->where('employee_id', $employee->id)
                ->whereNotNull('time_in_at')->whereNull('time_out_at')
                ->orderByDesc('shift_date')->lockForUpdate()->first();

            if ($record === null) {
                abort(422, 'There is no open time-in session to complete.');
            }

            $record->update([
                'time_out_at' => $nowUtc->format('Y-m-d H:i:s'),
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('status', 'Time-out recorded at '.CarbonImmutable::now(AttendanceComputationService::LOCAL_TIMEZONE)->format('g:i A').'.');
    }

    public function correct(Request $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $attendanceRecord->load('employee');
        abort_unless($request->user()->isSuperAdmin() || $attendanceRecord->employee->branch_id === $request->user()->branch_id, 404);
        $data = $request->validate([
            'time_in_at' => ['nullable', 'date'],
            'time_out_at' => ['nullable', 'date', 'after_or_equal:time_in_at'],
            'approved_overtime_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);
        $newTimeIn = $this->localInputToUtc($data['time_in_at'] ?? null);
        $newTimeOut = $this->localInputToUtc($data['time_out_at'] ?? null);

        DB::transaction(function () use ($attendanceRecord, $request, $data, $newTimeIn, $newTimeOut): void {
            $record = AttendanceRecord::query()->lockForUpdate()->findOrFail($attendanceRecord->id);
            AttendanceCorrection::create([
                'attendance_record_id' => $record->id,
                'corrected_by' => $request->user()->id,
                'old_time_in_at' => $record->time_in_at,
                'new_time_in_at' => $newTimeIn,
                'old_time_out_at' => $record->time_out_at,
                'new_time_out_at' => $newTimeOut,
                'old_approved_overtime_minutes' => $record->approved_overtime_minutes,
                'new_approved_overtime_minutes' => $data['approved_overtime_minutes'],
                'reason' => $data['reason'],
                'created_at' => CarbonImmutable::now('UTC'),
            ]);
            $record->update([
                'time_in_at' => $newTimeIn,
                'time_out_at' => $newTimeOut,
                'approved_overtime_minutes' => $data['approved_overtime_minutes'],
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('status', 'Attendance correction saved with an audit record.');
    }

    private function currentEmployee(Request $request): Employee
    {
        abort_unless($request->user()->isTimekeepingEmployee(), 403);

        return Employee::query()->with(['user.branch', 'defaultShift', 'schedules.shift'])
            ->where('user_id', $request->user()->id)->firstOrFail();
    }

    private function localInputToUtc(?string $value): ?string
    {
        return blank($value) ? null : CarbonImmutable::parse($value, AttendanceComputationService::LOCAL_TIMEZONE)->utc()->format('Y-m-d H:i:s');
    }
}
