@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6"><a href="{{ route('timekeeping.employees.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">← Employees</a><h1 class="mt-3 text-3xl font-bold text-slate-900">{{ $editing ? 'Edit employee' : 'Add employee' }}</h1><p class="mt-1 text-sm text-slate-500">Add a roster record, with an optional sign-in account.</p></div>
    <x-card>
        <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('timekeeping.employees.update', $employee) : route('timekeeping.employees.store') }}" class="grid gap-5 sm:grid-cols-2">
            @csrf @if ($editing) @method('PUT') @endif
            @if ($editing)
                <input type="hidden" name="employee_type" value="{{ $employeeType }}">
                <div class="sm:col-span-2 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">{{ $employeeType === 'roster' ? 'Roster only · no sign-in account' : 'Employee sign-in account' }}</div>
            @else
                <div class="sm:col-span-2"><x-form-field label="Record type" for="employee_type"><x-select id="employee_type" name="employee_type"><option value="roster" @selected(old('employee_type', $employeeType) === 'roster')>Roster only — no sign-in account</option><option value="account" @selected(old('employee_type', $employeeType) === 'account')>Employee with sign-in account</option></x-select></x-form-field></div>
            @endif
            <div><x-form-field label="Employee ID" for="employee_number"><x-input id="employee_number" name="employee_number" value="{{ old('employee_number', $employee->employee_number) }}" required /></x-form-field></div>
            <div><x-form-field label="Full name" for="name"><x-input id="name" name="name" value="{{ old('name', $employee->user?->name ?? $employee->name) }}" required /></x-form-field></div>
            @if (! $editing || $employeeType === 'account')
            <div data-account-field><x-form-field label="Email address" for="email"><x-input id="email" name="email" type="email" value="{{ old('email', $employee->user?->email) }}" @required($employeeType === 'account') /></x-form-field></div>
            <div data-account-field><x-form-field label="Contact number" for="phone"><x-input id="phone" name="phone" value="{{ old('phone', $employee->user?->phone) }}" /></x-form-field></div>
            <div data-account-field><x-form-field label="Profile photo (optional, max 2 MB)" for="avatar"><x-input id="avatar" name="avatar" type="file" accept="image/*" /></x-form-field></div>
            <div>
                <x-form-field label="Position / job title" for="job_title" hint="Choose a common station role or enter another title.">
                    <x-input id="job_title" name="job_title" list="station-job-titles" value="{{ old('job_title', $employee->job_title) }}" required />
                    <datalist id="station-job-titles">
                        <option value="Cashier"></option>
                        <option value="Officer in Charge (OIC)"></option>
                        <option value="Forecourt Attendant"></option>
                    </datalist>
                </x-form-field>
            </div>
            <div><x-form-field label="Department" for="department"><x-input id="department" name="department" value="{{ old('department', $employee->department ?: 'Station Operations') }}" /></x-form-field></div>
            <div><x-form-field label="Date hired (optional)" for="date_hired"><x-input id="date_hired" name="date_hired" type="date" value="{{ old('date_hired', $employee->date_hired?->format('Y-m-d')) }}" /></x-form-field></div>
            <div><x-form-field label="Default shift" for="default_shift_template_id"><x-select id="default_shift_template_id" name="default_shift_template_id"><option value="">No default shift</option>@foreach ($shifts as $shift)<option value="{{ $shift->id }}" @selected((string) old('default_shift_template_id', $employee->default_shift_template_id) === (string) $shift->id)>{{ $shift->name }} · {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}</option>@endforeach</x-select></x-form-field></div>
            @if ($branches->isNotEmpty())<div><x-form-field label="Assigned branch" for="branch_id"><x-select id="branch_id" name="branch_id" @required($employeeType === 'account')><option value="">Choose a branch</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected((string) old('branch_id', $employee->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</x-select></x-form-field></div>@endif
            <div class="sm:col-span-2"><x-form-field label="Address" for="address"><x-textarea id="address" name="address" rows="3">{{ old('address', $employee->address) }}</x-textarea></x-form-field></div>
            <div data-account-field><x-form-field :label="$editing ? 'New password (optional)' : 'Temporary password'" for="password"><x-input id="password" name="password" type="password" @required(!$editing && $employeeType === 'account') autocomplete="new-password" /></x-form-field></div>
            <div data-account-field><x-form-field label="Confirm password" for="password_confirmation"><x-input id="password_confirmation" name="password_confirmation" type="password" @required(!$editing && $employeeType === 'account') autocomplete="new-password" /></x-form-field></div>
            @endif
            <div class="sm:col-span-2 flex justify-end gap-3"><x-button :href="route('timekeeping.employees.index')" variant="secondary">Cancel</x-button><x-button>{{ $editing ? 'Save employee' : 'Save roster entry' }}</x-button></div>
        </form>
    </x-card>
</section>
@if (! $editing)
    <script>
        const employeeType = document.getElementById('employee_type');
        const accountFields = document.querySelectorAll('[data-account-field]');
        const syncAccountFields = () => {
            const showAccountFields = employeeType.value === 'account';
            accountFields.forEach((field) => {
                field.hidden = !showAccountFields;
                field.querySelectorAll('input').forEach((input) => {
                    input.required = showAccountFields && ['email', 'password', 'password_confirmation'].includes(input.name);
                });
            });
            const branchSelect = document.getElementById('branch_id');
            if (branchSelect) {
                branchSelect.required = showAccountFields;
            }
        };
        employeeType.addEventListener('change', syncAccountFields);
        syncAccountFields();
    </script>
@endif
@endsection
