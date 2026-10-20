<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftTemplate extends Model
{
    protected $fillable = ['name', 'start_time', 'end_time', 'break_minutes', 'grace_period_minutes', 'is_active'];

    protected function casts(): array
    {
        return ['break_minutes' => 'integer', 'grace_period_minutes' => 'integer', 'is_active' => 'boolean'];
    }

    /** @return HasMany<Employee, $this> */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'default_shift_template_id');
    }

    /** @return HasMany<EmployeeSchedule, $this> */
    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }
}
