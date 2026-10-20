<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Writes an immutable audit trail entry describing a critical system action.
 */
class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $actor = null,
        ?Branch $branch = null,
    ): ActivityLog {
        $actor ??= Auth::user();
        $request = Request::instance();

        return ActivityLog::create([
            'user_id' => $actor?->id,
            'user_name' => $actor?->name ?? 'System',
            'user_role' => $actor?->role?->value,
            'branch_id' => $branch?->id ?? ($subject instanceof Branch ? $subject->id : null),
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000) ?: null,
        ]);
    }
}
