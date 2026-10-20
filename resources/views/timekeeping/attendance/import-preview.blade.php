@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6"><p class="text-sm font-semibold uppercase tracking-wide text-brand-700">Timekeeping · Attendance</p><h1 class="mt-1 text-3xl font-bold text-slate-900">Preview biometric import</h1><p class="mt-1 text-sm text-slate-500">{{ $filename }} · {{ $branch->name }} · Philippine local time</p></div>
    <x-card title="Import validation" subtitle="Nothing is saved until you confirm. Any errors must be fixed in the CSV before import.">
        @if ($errors !== [])
            <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><p class="font-semibold">{{ count($errors) }} issue(s) need attention</p><ul class="mt-2 list-inside list-disc space-y-1">@foreach ($errors as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <p class="mb-3 text-sm font-medium text-slate-700">{{ count($rows) }} valid row(s) ready to import.</p>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead><tr class="text-left text-xs uppercase tracking-wide text-slate-500"><th class="py-3 pr-4">Employee ID</th><th class="py-3 pr-4">Employee</th><th class="py-3 pr-4">Shift date</th><th class="py-3 pr-4">Time in</th><th class="py-3">Time out</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse ($rows as $row)
                <tr><td class="py-3 pr-4 font-medium">{{ $row['employee_number'] }}</td><td class="py-3 pr-4">{{ $row['employee_name'] }}</td><td class="py-3 pr-4">{{ $row['shift_date'] }}</td><td class="py-3 pr-4">{{ $row['time_in'] }}</td><td class="py-3">{{ $row['time_out'] }}</td></tr>
            @empty
                <tr><td colspan="5" class="py-6 text-center text-slate-500">No valid rows found. Download the sample CSV and check the required headers and branch employee IDs.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="mt-6 flex flex-wrap justify-end gap-3"><x-button :href="route('timekeeping.attendance.index')" variant="secondary">Cancel</x-button>@if ($rows !== [] && $errors === [])<form method="POST" action="{{ route('timekeeping.attendance.import.confirm') }}">@csrf<x-button>Confirm and import {{ count($rows) }} records</x-button></form>@endif</div>
    </x-card>
</section>
@endsection
