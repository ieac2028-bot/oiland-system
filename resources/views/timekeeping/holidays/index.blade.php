@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6"><p class="text-sm font-semibold uppercase tracking-wide text-brand-700">Timekeeping</p><h1 class="mt-1 text-3xl font-bold text-slate-900">Holidays</h1><p class="mt-1 text-sm text-slate-500">Active holidays are excluded from scheduled absences in attendance reports.</p></div>
    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="Add holiday">
            <form method="POST" action="{{ route('timekeeping.holidays.store') }}" class="space-y-4">@csrf
                <x-form-field label="Holiday name" for="name"><x-input id="name" name="name" placeholder="National holiday" required /></x-form-field>
                <x-form-field label="Date" for="holiday_date"><x-input id="holiday_date" name="holiday_date" type="date" required /></x-form-field>
                @if ($branches->isNotEmpty())<x-form-field label="Applies to" for="branch_id"><x-select id="branch_id" name="branch_id"><option value="">All branches</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</x-select></x-form-field>@endif
                <x-button>Add holiday</x-button>
            </form>
        </x-card>
        <x-card class="lg:col-span-2" title="Configured holidays" subtitle="Branch holidays affect employees assigned to that branch">
            <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead><tr class="text-left text-xs uppercase text-slate-500"><th class="py-3 pr-3">Date</th><th class="py-3 pr-3">Name</th><th class="py-3 pr-3">Branch</th><th class="py-3 pr-3">Status</th><th class="py-3">Action</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse ($holidays as $holiday)<tr><td class="py-3 pr-3">{{ $holiday->holiday_date->format('M j, Y') }}</td><td class="py-3 pr-3 font-medium text-slate-900">{{ $holiday->name }}</td><td class="py-3 pr-3">{{ $holiday->branch?->name ?? 'All branches' }}</td><td class="py-3 pr-3"><x-badge :tone="$holiday->is_active ? 'emerald' : 'slate'">{{ $holiday->is_active ? 'Active' : 'Inactive' }}</x-badge></td><td class="py-3"><form method="POST" action="{{ route('timekeeping.holidays.toggle', $holiday) }}">@csrf @method('PATCH')<x-button variant="ghost">{{ $holiday->is_active ? 'Deactivate' : 'Activate' }}</x-button></form></td></tr>@empty<tr><td colspan="5" class="py-8 text-center text-slate-500">No holidays configured.</td></tr>@endforelse
            </tbody></table></div><div class="mt-4">{{ $holidays->links() }}</div>
        </x-card>
    </div>
</section>
@endsection
