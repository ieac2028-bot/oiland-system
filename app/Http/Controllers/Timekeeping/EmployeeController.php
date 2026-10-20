<?php

namespace App\Http\Controllers\Timekeeping;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeManager($request);
        $employees = $this->scopedEmployees($request)
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(fn ($employee) => $employee
                    ->where('employee_number', 'like', $term)
                    ->orWhere('job_title', 'like', $term)
                    ->orWhere('department', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term)));
            })
            ->when($request->filled('branch_id') && $request->user()->isSuperAdmin(), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('employment_status', $request->string('status')->toString()))
            ->orderBy('employee_number')->paginate(20)->withQueryString();

        return view('timekeeping.employees.index', [
            'employees' => $employees,
            'branches' => $request->user()->isSuperAdmin() ? Branch::orderBy('name')->get(['id', 'name']) : collect(),
            'filters' => $request->only(['q', 'branch_id', 'status']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeManager($request);

        return view('timekeeping.employees.form', [
            'employee' => new Employee(['department' => 'Station Operations']),
            'branches' => $request->user()->isSuperAdmin() ? Branch::active()->orderBy('name')->get(['id', 'name']) : collect(),
            'shifts' => ShiftTemplate::query()->where('is_active', true)->orderBy('start_time')->get(),
            'editing' => false,
            'employeeType' => 'roster',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);
        $data = $request->validate([
            'employee_number' => ['required', 'string', 'max:40', 'unique:employees,employee_number'],
            'name' => ['required', 'string', 'max:255'],
            'employee_type' => ['required', Rule::in(['roster', 'account'])],
            'email' => ['required_if:employee_type,account', 'nullable', 'email', 'lowercase', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required_if:employee_type,account', 'nullable', 'confirmed', Password::defaults()],
            'branch_id' => [$request->user()->isSuperAdmin() && $request->input('employee_type') === 'account' ? 'required' : 'nullable', 'integer', 'exists:branches,id'],
            'job_title' => ['required', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:2000'],
            'date_hired' => ['nullable', 'date', 'before_or_equal:today'],
            'default_shift_template_id' => ['nullable', 'integer', 'exists:shift_templates,id'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);
        $branchId = $this->branchId($request, $data['branch_id'] ?? null, $data['employee_type'] === 'roster');

        DB::transaction(function () use ($data, $request, $branchId): void {
            $account = null;
            if ($data['employee_type'] === 'account') {
                $account = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'password' => Hash::make($data['password']),
                    'role' => UserRole::Employee->value,
                    'branch_id' => $branchId,
                    'email_verified_at' => now(),
                    'avatar_path' => $request->file('avatar')?->store('employees', 'public'),
                ]);
            }

            Employee::create([
                'user_id' => $account?->id,
                'name' => $data['name'],
                'branch_id' => $branchId,
                'employee_number' => $data['employee_number'],
                'job_title' => $data['job_title'],
                'department' => $data['department'] ?: 'Station Operations',
                'address' => $data['address'] ?? null,
                'date_hired' => $data['date_hired'] ?? null,
                'default_shift_template_id' => $data['default_shift_template_id'] ?? null,
            ]);
        });

        return redirect()->route('timekeeping.employees.index')->with('status', 'Employee account and profile created.');
    }

    public function edit(Request $request, Employee $employee): View
    {
        $this->authorizeEmployee($request, $employee);

        return view('timekeeping.employees.form', [
            'employee' => $employee->load('user'),
            'branches' => $request->user()->isSuperAdmin() ? Branch::active()->orderBy('name')->get(['id', 'name']) : collect(),
            'shifts' => ShiftTemplate::query()->where('is_active', true)->orderBy('start_time')->get(),
            'editing' => true,
            'employeeType' => $employee->user_id === null ? 'roster' : 'account',
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);
        $data = $request->validate([
            'employee_number' => ['required', 'string', 'max:40', Rule::unique('employees', 'employee_number')->ignore($employee->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => [$employee->user_id === null ? 'nullable' : 'required', 'email', 'lowercase', 'max:255', Rule::unique('users', 'email')->ignore($employee->user_id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'branch_id' => [$request->user()->isSuperAdmin() ? 'required' : 'nullable', 'integer', 'exists:branches,id'],
            'job_title' => ['required', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:2000'],
            'date_hired' => ['nullable', 'date', 'before_or_equal:today'],
            'default_shift_template_id' => ['nullable', 'integer', 'exists:shift_templates,id'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);
        $branchId = $this->branchId($request, $data['branch_id'] ?? null, $employee->user_id === null);

        DB::transaction(function () use ($employee, $data, $branchId, $request): void {
            $avatarPath = $request->file('avatar')?->store('employees', 'public');
            if ($avatarPath !== null && $employee->user?->avatar_path !== null) {
                Storage::disk('public')->delete($employee->user->avatar_path);
            }

            $account = $employee->user;
            $account?->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'branch_id' => $branchId,
                ...($avatarPath ? ['avatar_path' => $avatarPath] : []),
                ...($account !== null && $account->email_verified_at === null ? ['email_verified_at' => now()] : []),
                ...(! empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
            ]);
            $employee->update([
                'name' => $data['name'],
                'branch_id' => $branchId,
                'employee_number' => $data['employee_number'],
                'job_title' => $data['job_title'],
                'department' => $data['department'] ?: 'Station Operations',
                'address' => $data['address'] ?? null,
                'date_hired' => $data['date_hired'] ?? null,
                'default_shift_template_id' => $data['default_shift_template_id'] ?? null,
            ]);
        });

        return redirect()->route('timekeeping.employees.index')->with('status', 'Employee profile updated.');
    }

    public function setStatus(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeEmployee($request, $employee);
        $data = $request->validate(['employment_status' => ['required', Rule::in(['active', 'inactive'])]]);
        $employee->update($data);

        return back()->with('status', 'Employee status updated.');
    }

    private function scopedEmployees(Request $request)
    {
        return Employee::query()->with(['user.branch', 'branch', 'defaultShift'])
            ->when(! $request->user()->isSuperAdmin(), fn ($query) => $query->where('branch_id', $request->user()->branch_id));
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
    }

    private function authorizeEmployee(Request $request, Employee $employee): void
    {
        $this->authorizeManager($request);
        abort_unless($request->user()->isSuperAdmin() || $employee->branch_id === $request->user()->branch_id, 404);
    }

    private function branchId(Request $request, ?int $submittedBranchId, bool $allowUnassigned = false): ?int
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            if ($submittedBranchId === null && ! $allowUnassigned) {
                abort(422, 'Choose a branch for this employee.');
            }

            return $submittedBranchId;
        }

        abort_unless($user->branch_id !== null, 403);

        return $user->branch_id;
    }
}
