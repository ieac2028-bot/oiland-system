<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attendance_record_id', 'corrected_by', 'old_time_in_at', 'new_time_in_at', 'old_time_out_at', 'new_time_out_at',
        'old_approved_overtime_minutes', 'new_approved_overtime_minutes', 'reason', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
        ];
    }

    protected function oldTimeInAt(): Attribute
    {
        return $this->utcDateTime('old_time_in_at');
    }

    protected function newTimeInAt(): Attribute
    {
        return $this->utcDateTime('new_time_in_at');
    }

    protected function oldTimeOutAt(): Attribute
    {
        return $this->utcDateTime('old_time_out_at');
    }

    protected function newTimeOutAt(): Attribute
    {
        return $this->utcDateTime('new_time_out_at');
    }

    private function utcDateTime(string $column): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?\Carbon\CarbonImmutable => $value === null ? null : CarbonImmutable::parse($value, 'UTC'),
            set: fn ($value): ?string => $value === null ? null : CarbonImmutable::parse($value, 'UTC')->utc()->format('Y-m-d H:i:s'),
        );
    }

    /** @return BelongsTo<AttendanceRecord, $this> */
    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }
}
