<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use App\Support\ActivityAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The audit trail browser. Head office sees the whole network; branch staff
 * are clamped to their own branch's entries.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ActivityLog::class);

        $user = $request->user();

        $logs = ActivityLog::query()
            ->with('branch')
            ->when(
                $user->branch_id !== null,
                fn ($query) => $query->where('branch_id', $user->branch_id)
            )
            ->search($request->string('q')->toString() ?: null)
            ->action($request->string('action')->toString() ?: null)
            ->betweenDates(
                $request->date('from')?->toDateString(),
                $request->date('to')?->toDateString(),
            )
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.activity-log.index', [
            'logs' => $logs,
            'actions' => ActivityAction::options(),
            'users' => User::when($user->branch_id !== null, fn ($query) => $query->where('branch_id', $user->branch_id))->orderBy('name')->get(['id', 'name']),
            // A branch-scoped user is already clamped to their own station, so
            // the filter is hidden rather than listing stations they cannot see.
            'branches' => $user->isSuperAdmin()
                ? Branch::orderBy('name')->get(['id', 'name'])
                : collect(),
            'filters' => $request->only(['q', 'action', 'user_id', 'branch_id', 'from', 'to']),
            'scopeLabel' => $user->isSuperAdmin() ? 'All branches' : $user->branch?->name,
        ]);
    }

    public function show(ActivityLog $activityLog): View
    {
        Gate::authorize('view', $activityLog);

        $activityLog->load(['user', 'branch']);

        return view('admin.activity-log.show', [
            'log' => $activityLog,
            'actions' => ActivityAction::options(),
        ]);
    }
}
