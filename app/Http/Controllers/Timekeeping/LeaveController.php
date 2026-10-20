<?php

namespace App\Http\Controllers\Timekeeping;

use App\Enums\LeaveRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $user->canManageTimekeeping();
        abort_unless($isManager || $user->isTimekeepingEmployee(), 403);
        $employee = $isManager ? null : Employee::query()->where('user_id', $user->id)->firstOrFail();
        $requests = LeaveRequest::query()->with(['employee.user.branch', 'type', 'reviewer'])
            ->when($isManager, fn ($query) => $query->whereHas('employee.user', fn ($userQuery) => $userQuery->when(! $user->isSuperAdmin(), fn ($scoped) => $scoped->where('branch_id', $user->branch_id))))
            ->when(! $isManager, fn ($query) => $query->where('employee_id', $employee->id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()->paginate(20)->withQueryString();

        return view('timekeeping.leaves.index', [
            'requests' => $requests,
            'types' => LeaveType::query()->where('is_active', true)->orderBy('name')->get(),
            'allTypes' => $isManager ? LeaveType::query()->orderBy('name')->get() : collect(),
            'employee' => $employee,
            'isManager' => $isManager,
            'filters' => $request->only(['status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isTimekeepingEmployee(), 403);
        $employee = Employee::query()->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($employee->employment_status === 'active', 403);
        $data = $request->validate([
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')->where('is_active', true)],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:5', 'max:3000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $type = LeaveType::findOrFail($data['leave_type_id']);
        if ($type->requires_attachment && ! $request->hasFile('attachment')) {
            return back()->withErrors(['attachment' => 'This leave type requires a supporting document.'])->withInput();
        }

        $overlap = $employee->leaveRequests()
            ->whereIn('status', [LeaveRequestStatus::Pending->value, LeaveRequestStatus::Approved->value])
            ->whereDate('start_date', '<=', $data['end_date'])
            ->whereDate('end_date', '>=', $data['start_date'])
            ->exists();
        if ($overlap) {
            return back()->withErrors(['start_date' => 'These dates overlap another pending or approved leave request.'])->withInput();
        }

        $path = $request->file('attachment')?->store('timekeeping/leave-attachments', 'local');
        $employee->leaveRequests()->create([
            'leave_type_id' => $type->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'],
            'attachment_path' => $path,
            'status' => LeaveRequestStatus::Pending->value,
        ]);

        return back()->with('status', 'Leave request submitted for review.');
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($request->user()->isTimekeepingEmployee(), 403);
        $employee = Employee::query()->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($leaveRequest->employee_id === $employee->id && $leaveRequest->status === LeaveRequestStatus::Pending->value, 404);
        $leaveRequest->update(['status' => LeaveRequestStatus::Cancelled->value, 'cancelled_at' => now()]);

        return back()->with('status', 'Pending leave request cancelled.');
    }

    public function decide(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $leaveRequest->load('employee.user');
        abort_unless($request->user()->isSuperAdmin() || $leaveRequest->employee->user->branch_id === $request->user()->branch_id, 404);
        abort_unless($leaveRequest->status === LeaveRequestStatus::Pending->value, 409);
        $data = $request->validate([
            'decision' => ['required', Rule::in([LeaveRequestStatus::Approved->value, LeaveRequestStatus::Rejected->value])],
            'decision_comment' => ['nullable', 'string', 'max:2000', Rule::requiredIf(fn (): bool => $request->input('decision') === LeaveRequestStatus::Rejected->value)],
        ]);

        if ($data['decision'] === LeaveRequestStatus::Approved->value) {
            $conflict = $leaveRequest->employee->leaveRequests()
                ->whereKeyNot($leaveRequest->id)
                ->where('status', LeaveRequestStatus::Approved->value)
                ->whereDate('start_date', '<=', $leaveRequest->end_date->toDateString())
                ->whereDate('end_date', '>=', $leaveRequest->start_date->toDateString())
                ->exists();
            if ($conflict) {
                return back()->withErrors(['decision' => 'Another approved leave overlaps this date range.']);
            }
        }

        $leaveRequest->update([
            'status' => $data['decision'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'decision_comment' => $data['decision_comment'] ?? null,
        ]);

        return back()->with('status', 'Leave request '.($data['decision'] === LeaveRequestStatus::Approved->value ? 'approved' : 'rejected').'.');
    }

    public function storeType(Request $request): RedirectResponse
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:leave_types,name'],
            'requires_attachment' => ['nullable', 'boolean'],
        ]);
        LeaveType::create(['name' => $data['name'], 'requires_attachment' => $request->boolean('requires_attachment'), 'is_active' => true]);

        return back()->with('status', 'Leave type added.');
    }

    public function toggleType(Request $request, LeaveType $leaveType): RedirectResponse
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $leaveType->update(['is_active' => ! $leaveType->is_active]);

        return back()->with('status', 'Leave type updated.');
    }

    public function downloadAttachment(Request $request, LeaveRequest $leaveRequest): StreamedResponse
    {
        $leaveRequest->load('employee.user');
        $user = $request->user();
        $authorized = $user->canManageTimekeeping()
            ? ($user->isSuperAdmin() || $leaveRequest->employee->user->branch_id === $user->branch_id)
            : ($user->isTimekeepingEmployee() && $leaveRequest->employee->user_id === $user->id);
        abort_unless($authorized && $leaveRequest->attachment_path !== null, 404);

        return Storage::disk('local')->download($leaveRequest->attachment_path);
    }
}
