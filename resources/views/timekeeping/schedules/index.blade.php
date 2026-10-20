@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6"><p class="text-sm font-semibold uppercase tracking-wide text-brand-700">Timekeeping</p><h1 class="mt-1 text-3xl font-bold text-slate-900">Employee schedules</h1><p class="mt-1 text-sm text-slate-500">Assign a shift to a specific date or mark it as a rest day. Overnight shifts roll into the next calendar day.</p></div>
    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="Assign a shift or rest day">
            <form method="POST" action="{{ route('timekeeping.schedules.store') }}" class="space-y-4">@csrf
                <x-form-field label="Employee" for="employee_id"><x-select id="employee_id" name="employee_id" required><option value="">Choose employee</option>@foreach ($employees as $employee)<option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>{{ $employee->employee_number }} · {{ $employee->user->name }}{{ $employee->user->branch ? ' · '.$employee->user->branch->name : '' }}</option>@endforeach</x-select></x-form-field>
                <x-form-field label="Date" for="shift_date"><x-input id="shift_date" name="shift_date" type="date" value="{{ old('shift_date', $start) }}" required /></x-form-field>
                <x-form-field label="Shift template" for="shift_template_id"><x-select id="shift_template_id" name="shift_template_id"><option value="">Choose shift</option>@foreach ($shifts as $shift)<option value="{{ $shift->id }}" @selected(old('shift_template_id') == $shift->id)>{{ $shift->name }} · {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}</option>@endforeach</x-select></x-form-field>
                <label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="is_rest_day" value="1" @checked(old('is_rest_day')) class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Mark as rest day</label>
                <x-form-field label="Notes (optional)" for="notes"><x-textarea id="notes" name="notes" rows="2" placeholder="Coverage or shift notes">{{ old('notes') }}</x-textarea></x-form-field>
                <x-button class="w-full">Save schedule</x-button>
            </form>
        </x-card>
        <div class="space-y-5 lg:col-span-2">
            <x-card title="Schedule list" subtitle="Use the date range to review assignments">
                <form method="GET" class="mb-5 flex flex-wrap items-end gap-3"><x-form-field label="From" for="start"><x-input id="start" name="start" type="date" value="{{ $start }}" /></x-form-field><x-form-field label="To" for="end"><x-input id="end" name="end" type="date" value="{{ $end }}" /></x-form-field><x-button variant="secondary">Show schedule</x-button></form>
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead><tr class="text-left text-xs uppercase text-slate-500"><th class="py-3 pr-3">Date</th><th class="py-3 pr-3">Employee</th><th class="py-3 pr-3">Branch</th><th class="py-3 pr-3">Shift</th><th class="py-3">Notes</th></tr></thead><tbody class="divide-y divide-slate-100">
                    @forelse ($schedules as $schedule)<tr><td class="py-3 pr-3">{{ $schedule->shift_date->format('D, M j, Y') }}</td><td class="py-3 pr-3 font-medium text-slate-900">{{ $schedule->employee->user->name }}<span class="block text-xs text-slate-500">{{ $schedule->employee->employee_number }}</span></td><td class="py-3 pr-3">{{ $schedule->employee->user->branch?->name ?? '—' }}</td><td class="py-3 pr-3">{{ $schedule->is_rest_day ? 'Rest day' : ($schedule->shift?->name ?? 'No shift') }}</td><td class="py-3">{{ $schedule->notes ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="py-8 text-center text-slate-500">No date-specific schedules in this range.</td></tr>@endforelse
                </tbody></table></div><div class="mt-4">{{ $schedules->links() }}</div>
            </x-card>
        </div>
    </div>
</section>
@endsection
