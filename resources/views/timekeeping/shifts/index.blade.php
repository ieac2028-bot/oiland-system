@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6"><p class="text-sm font-semibold uppercase tracking-wide text-brand-700">Timekeeping</p><h1 class="mt-1 text-3xl font-bold text-slate-900">Shift templates</h1><p class="mt-1 text-sm text-slate-500">Templates support overnight shifts when the end time is earlier than the start time.</p></div>
    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-1" :title="$editingShift ? 'Edit shift' : 'Add shift'">
            <form method="POST" action="{{ $editingShift ? route('timekeeping.shifts.update', $editingShift) : route('timekeeping.shifts.store') }}" class="space-y-4">
                @csrf @if ($editingShift) @method('PUT') @endif
                <x-form-field label="Shift name" for="name"><x-input id="name" name="name" value="{{ old('name', $editingShift?->name) }}" placeholder="Morning shift" required /></x-form-field>
                <div class="grid grid-cols-2 gap-3"><x-form-field label="Start time" for="start_time"><x-input id="start_time" name="start_time" type="time" value="{{ old('start_time', $editingShift ? substr($editingShift->start_time, 0, 5) : '') }}" required /></x-form-field><x-form-field label="End time" for="end_time"><x-input id="end_time" name="end_time" type="time" value="{{ old('end_time', $editingShift ? substr($editingShift->end_time, 0, 5) : '') }}" required /></x-form-field></div>
                <div class="grid grid-cols-2 gap-3"><x-form-field label="Break (minutes)" for="break_minutes"><x-input id="break_minutes" name="break_minutes" type="number" min="0" max="360" value="{{ old('break_minutes', $editingShift?->break_minutes ?? 0) }}" required /></x-form-field><x-form-field label="Late grace (minutes)" for="grace_period_minutes"><x-input id="grace_period_minutes" name="grace_period_minutes" type="number" min="0" max="180" value="{{ old('grace_period_minutes', $editingShift?->grace_period_minutes ?? 0) }}" required /></x-form-field></div>
                <div class="flex justify-end gap-2">@if ($editingShift)<x-button :href="route('timekeeping.shifts.index')" variant="secondary">Cancel</x-button>@endif<x-button>{{ $editingShift ? 'Save changes' : 'Create shift' }}</x-button></div>
            </form>
        </x-card>
        <x-card class="lg:col-span-2" title="Configured shifts" subtitle="Deactivation preserves historical assignments">
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead><tr class="text-left text-xs uppercase text-slate-500"><th class="py-3 pr-3">Name</th><th class="py-3 pr-3">Hours</th><th class="py-3 pr-3">Break / grace</th><th class="py-3 pr-3">Status</th><th class="py-3">Actions</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse ($shifts as $shift)<tr><td class="py-3 pr-3 font-semibold text-slate-900">{{ $shift->name }}</td><td class="py-3 pr-3">{{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }} @if ($shift->end_time <= $shift->start_time)<span class="text-xs text-slate-500">(overnight)</span>@endif</td><td class="py-3 pr-3">{{ $shift->break_minutes }}m / {{ $shift->grace_period_minutes }}m</td><td class="py-3 pr-3"><x-badge :tone="$shift->is_active ? 'emerald' : 'slate'">{{ $shift->is_active ? 'Active' : 'Inactive' }}</x-badge></td><td class="py-3"><div class="flex gap-2"><x-button :href="route('timekeeping.shifts.edit', $shift)" variant="secondary">Edit</x-button><form method="POST" action="{{ route('timekeeping.shifts.toggle', $shift) }}">@csrf @method('PATCH')<x-button variant="ghost">{{ $shift->is_active ? 'Deactivate' : 'Activate' }}</x-button></form></div></td></tr>@empty<tr><td colspan="5" class="py-8 text-center text-slate-500">No shifts configured yet.</td></tr>@endforelse
            </tbody></table></div><div class="mt-4">{{ $shifts->links() }}</div>
        </x-card>
    </div>
</section>
@endsection
