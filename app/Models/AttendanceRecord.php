<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'employee_id', 'import_batch_id', 'shift_date', 'time_in_at', 'time_out_at', 'approved_overtime_minutes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'shift_date' => 'date',
            'approved_overtime_minutes' => 'integer',
        ];
    }

    protected function timeInAt(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?\Carbon\CarbonImmutable => $value === null ? null : CarbonImmutable::parse($value, 'UTC'),
            set: fn ($value): ?string => $value === null ? null : CarbonImmutable::parse($value, 'UTC')->utc()->format('Y-m-d H:i:s'),
        );
    }

    protected function timeOutAt(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?\Carbon\CarbonImmutable => $value === null ? null : CarbonImmutable::parse($value, 'UTC'),
            set: fn ($value): ?string => $value === null ? null : CarbonImmutable::parse($value, 'UTC')->utc()->format('Y-m-d H:i:s'),
        );
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<AttendanceImportBatch, $this> */
    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(AttendanceImportBatch::class, 'import_batch_id');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<AttendanceCorrection, $this> */
    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
