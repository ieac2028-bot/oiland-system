<?php

use App\Enums\UserRole;
use App\Http\Controllers\Timekeeping\AttendanceController;
use App\Http\Controllers\Timekeeping\AttendanceImportController;
use App\Http\Controllers\Timekeeping\CutoffReportController;
use App\Http\Controllers\Timekeeping\DashboardController;
use App\Http\Controllers\Timekeeping\EmployeeController;
use App\Http\Controllers\Timekeeping\HolidayController;
use App\Http\Controllers\Timekeeping\LeaveController;
use App\Http\Controllers\Timekeeping\ScheduleController;
use App\Http\Controllers\Timekeeping\ShiftController;
use Illuminate\Support\Facades\Route;

$managerRoles = implode(',', [UserRole::SuperAdmin->value, UserRole::BranchAdmin->value, UserRole::HrOfficer->value]);
$timekeepingRoles = implode(',', [...explode(',', $managerRoles), UserRole::Employee->value]);

Route::middleware(['auth', 'verified', 'role:'.$timekeepingRoles])
    ->prefix('timekeeping')->name('timekeeping.')
    ->group(function () use ($managerRoles): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/leaves', [LeaveController::class, 'index'])->name('leaves.index');
        Route::get('/leaves/{leaveRequest}/attachment', [LeaveController::class, 'downloadAttachment'])->name('leaves.attachment');

        Route::middleware('role:'.UserRole::Employee->value)->group(function (): void {
            Route::post('/attendance/time-in', [AttendanceController::class, 'timeIn'])->name('attendance.time-in');
            Route::post('/attendance/time-out', [AttendanceController::class, 'timeOut'])->name('attendance.time-out');
            Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
            Route::delete('/leaves/{leaveRequest}', [LeaveController::class, 'cancel'])->name('leaves.cancel');
        });

        Route::middleware('role:'.$managerRoles)->group(function (): void {
            Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
            Route::get('/attendance/import-template', [AttendanceImportController::class, 'template'])->name('attendance.import-template');
            Route::post('/attendance/import/preview', [AttendanceImportController::class, 'preview'])->name('attendance.import.preview');
            Route::post('/attendance/import/confirm', [AttendanceImportController::class, 'confirm'])->name('attendance.import.confirm');
            Route::patch('/attendance/{attendanceRecord}/correction', [AttendanceController::class, 'correct'])->name('attendance.correct');

            Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
            Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
            Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
            Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
            Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
            Route::patch('/employees/{employee}/status', [EmployeeController::class, 'setStatus'])->name('employees.status');

            Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
            Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');
            Route::get('/shifts/{shift}/edit', [ShiftController::class, 'edit'])->name('shifts.edit');
            Route::put('/shifts/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
            Route::patch('/shifts/{shift}/toggle', [ShiftController::class, 'toggle'])->name('shifts.toggle');

            Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
            Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');

            Route::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');
            Route::post('/holidays', [HolidayController::class, 'store'])->name('holidays.store');
            Route::patch('/holidays/{holiday}/toggle', [HolidayController::class, 'toggle'])->name('holidays.toggle');

            Route::post('/leaves/{leaveRequest}/decision', [LeaveController::class, 'decide'])->name('leaves.decide');
            Route::post('/leave-types', [LeaveController::class, 'storeType'])->name('leave-types.store');
            Route::patch('/leave-types/{leaveType}/toggle', [LeaveController::class, 'toggleType'])->name('leave-types.toggle');

            Route::get('/reports/cutoff', [CutoffReportController::class, 'index'])->name('reports.cutoff');
            Route::get('/reports/cutoff.csv', [CutoffReportController::class, 'csv'])->name('reports.cutoff.csv');
            Route::get('/reports/cutoff.pdf', [CutoffReportController::class, 'pdf'])->name('reports.cutoff.pdf');
            Route::get('/reports/cutoff.excel', [CutoffReportController::class, 'excel'])->name('reports.cutoff.excel');
            Route::get('/reports/cutoff.word', [CutoffReportController::class, 'word'])->name('reports.cutoff.word');
        });
    });
