@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm font-semibold uppercase tracking-wide text-brand-700">Timekeeping</p><h1 class="mt-1 text-3xl font-bold text-slate-900">Employees</h1><p class="mt-1 text-sm text-slate-500">Station roster, positions and optional employee accounts.</p></div><x-button :href="route('timekeeping.employees.create')">Add employee</x-button></div>
    <x-card>
        <form method="GET" class="mb-5 grid gap-3 sm:grid-cols-4">
            <x-input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, ID, job title or department" aria-label="Search employees" />
            <x-select name="status" aria-label="Employment status"><option value="">All statuses</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option></x-select>
            @if ($branches->isNotEmpty())<x-select name="branch_id" aria-label="Branch"><option value="">All branches</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</x-select>@endif
            <x-button variant="secondary">Filter employees</x-button>
        </form>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead><tr class="text-left text-xs uppercase tracking-wide text-slate-500"><th class="py-3 pr-4">Employee</th><th class="py-3 pr-4">Position / department</th><th class="py-3 pr-4">Branch</th><th class="py-3 pr-4">Shift</th><th class="py-3 pr-4">Status</th><th class="py-3">Actions</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse ($employees as $employee)
                <tr><td class="py-3 pr-4"><p class="font-semibold text-slate-900">{{ $employee->displayName() }}</p><p class="text-xs text-slate-500">{{ $employee->employee_number }} · {{ $employee->user?->email ?? 'Roster only' }}</p></td><td class="py-3 pr-4">{{ $employee->job_title }}<span class="block text-xs text-slate-500">{{ $employee->department }}</span></td><td class="py-3 pr-4">{{ $employee->branch?->name ?? 'Unassigned' }}</td><td class="py-3 pr-4">{{ $employee->defaultShift?->name ?? 'Not assigned' }}</td><td class="py-3 pr-4"><x-badge :tone="$employee->employment_status === 'active' ? 'emerald' : 'slate'">{{ ucfirst($employee->employment_status) }}</x-badge></td><td class="py-3"><div class="flex flex-wrap gap-2"><x-button :href="route('timekeeping.employees.edit', $employee)" variant="secondary">Edit</x-button><form method="POST" action="{{ route('timekeeping.employees.status', $employee) }}">@csrf @method('PATCH')<input type="hidden" name="employment_status" value="{{ $employee->employment_status === 'active' ? 'inactive' : 'active' }}"><x-button variant="ghost">{{ $employee->employment_status === 'active' ? 'Deactivate' : 'Reactivate' }}</x-button></form></div></td></tr>
            @empty <tr><td colspan="6" class="py-10 text-center text-slate-500">No employee profiles match these filters.</td></tr> @endforelse
        </tbody></table></div>
        <div class="mt-4">{{ $employees->links() }}</div>
    </x-card>
</section>
@endsection
