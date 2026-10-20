<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Staff account provisioning and role assignment.
 */
class UserController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $user = $request->user();

        $users = User::query()
            ->with('branch')
            // Branch staff only see the colleagues on their own branch.
            ->when($user->branch_id !== null, fn ($query) => $query->where('branch_id', $user->branch_id))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $like = '%'.$request->string('q')->toString().'%';
                $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')->toString()))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $this->roleOptions(),
            'filters' => $request->only(['q', 'role']),
            'canManage' => $user->isSuperAdmin(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create', [
            'branches' => Branch::orderBy('name')->get(),
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:'.implode(',', array_keys($this->roleOptions()))],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $role = UserRole::from($validated['role']);

        if ($role->isBranchScoped() && blank($validated['branch_id'] ?? null)) {
            return back()->withErrors(['branch_id' => 'Branch staff must be attached to a branch.'])->withInput();
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $role->value,
            'branch_id' => $role->isBranchScoped() ? $validated['branch_id'] : null,
            'email_verified_at' => ($role->isStaff() || $role->isTimekeepingManager()) ? now() : null,
        ]);

        $this->activityLogger->log(
            ActivityAction::USER_CREATED,
            "{$request->user()->name} created {$role->label()} account for {$user->name}.",
            $user,
            ['role' => $role->value, 'branch_id' => $user->branch_id],
            $request->user(),
        );

        return redirect()->route('admin.users.index')->with('status', "Account created for {$user->name}.");
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.edit', [
            'managedUser' => $user,
            'branches' => Branch::orderBy('name')->get(),
            'roles' => $this->roleOptions(),
            'canManage' => request()->user()->can('manageRoles', $user),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $canManageRoles = $request->user()->can('manageRoles', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => [$canManageRoles ? 'required' : 'nullable', 'in:'.implode(',', array_keys($this->roleOptions()))],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ];

        if ($canManageRoles) {
            $role = UserRole::from($validated['role']);

            if ($role->isBranchScoped() && blank($validated['branch_id'] ?? null)) {
                return back()->withErrors(['branch_id' => 'Branch staff must be attached to a branch.'])->withInput();
            }

            $attributes['role'] = $role->value;
            $attributes['branch_id'] = $role->isBranchScoped() ? $validated['branch_id'] : null;
        }

        $user->update($attributes);

        if ($canManageRoles) {
            $this->activityLogger->log(
                ActivityAction::USER_ROLE_CHANGED,
                "{$request->user()->name} set {$user->name} to {$user->role->label()} on ".($user->branch?->name ?? 'head office').'.',
                $user,
                ['role' => $user->role->value, 'branch_id' => $user->branch_id],
                $request->user(),
            );
        }

        return redirect()->route('admin.users.index')->with('status', "{$user->name} updated.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $name = $user->name;
        $user->delete();

        $this->activityLogger->log(
            ActivityAction::USER_CREATED,
            "{$request->user()->name} deleted the account for {$name}.",
            null,
            ['name' => $name],
            $request->user(),
        );

        return back()->with('status', "Account for {$name} deleted.");
    }

    /** @return array<string, string> */
    private function roleOptions(): array
    {
        $options = UserRole::options();
        unset($options[UserRole::Employee->value]);

        return $options;
    }
}
