<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'user_name',
    'user_role',
    'branch_id',
    'action',
    'description',
    'subject_type',
    'subject_id',
    'properties',
    'ip_address',
    'user_agent',
])]
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(function (Builder $query) use ($like): void {
            $query->where('description', 'like', $like)
                ->orWhere('user_name', 'like', $like)
                ->orWhere('action', 'like', $like);
        });
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeAction(Builder $query, ?string $action): void
    {
        if (filled($action)) {
            $query->where('action', $action);
        }
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): void
    {
        if (filled($from)) {
            $query->whereDate('created_at', '>=', $from);
        }

        if (filled($to)) {
            $query->whereDate('created_at', '<=', $to);
        }
    }

    public function subjectLabel(): string
    {
        if ($this->subject_type === null) {
            return '—';
        }

        $short = class_basename($this->subject_type);

        return $this->subject_id ? "{$short} #{$this->subject_id}" : $short;
    }
}
