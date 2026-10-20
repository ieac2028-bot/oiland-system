<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'branch_id',
        'employee_number',
        'job_title',
        'department',
        'address',
        'date_hired',
        'employment_status',
        'default_shift_template_id',
    ];

    protected function casts(): array
    {
        return ['date_hired' => 'date'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function displayName(): string
    {
        return $this->user?->name ?? $this->name ?? 'Unnamed employee';
    }

    /** @return BelongsTo<ShiftTemplate, $this> */
    public function defaultShift(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'default_shift_template_id');
    }

    /** @return HasMany<EmployeeSchedule, $this> */
    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    /** @return HasMany<AttendanceRecord, $this> */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /** @return HasMany<LeaveRequest, $this> */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /** @param Builder<$this> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('employment_status', 'active');
    }
}
