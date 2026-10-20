@extends('layouts.app')

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6"><p class="text-sm font-semibold uppercase tracking-wide text-brand-700">Timekeeping</p><h1 class="mt-1 text-3xl font-bold text-slate-900">Attendance records</h1><p class="mt-1 text-sm text-slate-500">Review clock entries, import biometric files, and make reasoned corrections with an audit history.</p></div>
    <x-card title="Import biometric attendance" subtitle="Upload a CSV exported from Excel. Each employee and date must be unique, and every row is checked before import." class="mb-6">
        <form method="GET" action="{{ route('timekeeping.attendance.import-template') }}" class="mb-4 flex flex-wrap items-end gap-3">
            @if (auth()->user()->isSuperAdmin())
                <x-form-field label="Branch for sample file" for="template_branch_id"><x-select id="template_branch_id" name="branch_id" required><option value="">Choose branch</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</x-select></x-form-field>
            @endif
            <x-button type="submit" variant="secondary">Download sample CSV</x-button>
        </form>
        <form method="POST" action="{{ route('timekeeping.attendance.import.preview') }}" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
            @csrf
            @if (auth()->user()->isSuperAdmin())
                <x-form-field label="Import branch" for="import_branch_id"><x-select id="import_branch_id" name="branch_id" required><option value="">Choose branch</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>@endforeach</x-select></x-form-field>
            @endif
            <x-form-field label="Biometric CSV file" for="biometric_file"><x-input id="biometric_file" name="file" type="file" accept=".csv,text/csv" required /></x-form-field>
            <x-button>Preview import</x-button>
        </form>
        <p class="mt-3 text-xs text-slate-500">OIC uploads are locked to their own branch. The sample uses branch employee IDs and example times; review it before confirming an import.</p>
    </x-card>
    <x-card>
        <form method="GET" class="mb-5 flex flex-wrap items-end gap-3"><x-form-field label="Shift date" for="date"><x-input id="date" name="date" type="date" value="{{ $date }}" /></x-form-field><x-form-field label="Status" for="status"><x-select id="status" name="status"><option value="">All records</option><option value="incomplete" @selected(request('status') === 'incomplete')>Incomplete</option></x-select></x-form-field><x-button variant="secondary">Filter</x-button></form>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead><tr class="text-left text-xs uppercase tracking-wide text-slate-500"><th class="py-3 pr-4">Employee / branch</th><th class="py-3 pr-4">Time in</th><th class="py-3 pr-4">Time out</th><th class="py-3 pr-4">Approved OT</th><th class="py-3 pr-4">Status</th><th class="py-3">Review / correct</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse ($records as $record)
                @php
                    $schedule = $record->employee->schedules->first(fn ($row) => $row->shift_date->toDateString() === $date);
                    $schedule ??= $record->employee->defaultShift ? new \App\Models\EmployeeSchedule(['shift_date' => $date, 'is_rest_day' => false]) : null;
                    if ($schedule && ! $schedule->relationLoaded('shift') && $record->employee->defaultShift) $schedule->setRelation('shift', $record->employee->defaultShift);
                    $metrics = app(\App\Services\AttendanceComputationService::class)->computeDay($schedule, $record, false, $date);
                    $manila = fn ($value) => $value ? \Carbon\CarbonImmutable::parse($value, 'UTC')->setTimezone('Asia/Manila')->format('g:i A') : '—';
                @endphp
                <tr class="align-top"><td class="py-3 pr-4"><p class="font-semibold text-slate-900">{{ $record->employee->displayName() }}</p><p class="text-xs text-slate-500">{{ $record->employee->employee_number }} · {{ $record->employee->branch?->name ?? $record->employee->user?->branch?->name }}</p></td><td class="py-3 pr-4">{{ $manila($record->time_in_at) }}</td><td class="py-3 pr-4">{{ $manila($record->time_out_at) }}</td><td class="py-3 pr-4">{{ number_format($record->approved_overtime_minutes / 60, 2) }} h</td><td class="py-3 pr-4"><x-badge :tone="match($metrics['status']) {'late' => 'amber', 'incomplete' => 'violet', default => 'emerald'}">{{ ucfirst($metrics['status']) }}</x-badge>@if ($record->import_batch_id)<span class="mt-1 block" title="Uploaded by {{ $record->importBatch?->uploader?->name ?? 'OIC' }}"><x-badge tone="sky">Biometric import</x-badge><span class="mt-1 block text-xs text-slate-500">by {{ $record->importBatch?->uploader?->name ?? 'OIC' }}</span></span>@endif</td><td class="py-3">
                    <details class="min-w-56"><summary class="cursor-pointer font-semibold text-brand-700">Correct record</summary><form method="POST" action="{{ route('timekeeping.attendance.correct', $record) }}" class="mt-3 grid gap-3 rounded-lg bg-slate-50 p-3">@csrf @method('PATCH')
                        <x-form-field label="Time in (Philippine time)" for="in-{{ $record->id }}"><x-input id="in-{{ $record->id }}" name="time_in_at" type="datetime-local" value="{{ old('time_in_at', $record->time_in_at?->setTimezone('Asia/Manila')->format('Y-m-d\TH:i')) }}" /></x-form-field>
                        <x-form-field label="Time out (Philippine time)" for="out-{{ $record->id }}"><x-input id="out-{{ $record->id }}" name="time_out_at" type="datetime-local" value="{{ old('time_out_at', $record->time_out_at?->setTimezone('Asia/Manila')->format('Y-m-d\TH:i')) }}" /></x-form-field>
                        <x-form-field label="Approved overtime (minutes)" for="ot-{{ $record->id }}"><x-input id="ot-{{ $record->id }}" name="approved_overtime_minutes" type="number" min="0" value="{{ old('approved_overtime_minutes', $record->approved_overtime_minutes) }}" required /></x-form-field>
                        <x-form-field label="Correction reason" for="reason-{{ $record->id }}"><x-textarea id="reason-{{ $record->id }}" name="reason" rows="2" required>{{ old('reason') }}</x-textarea></x-form-field>
                        <x-button variant="primary">Save correction</x-button>
                    </form>
                    @if ($record->corrections->isNotEmpty())<div class="mt-3 space-y-2 text-xs text-slate-600"><p class="font-semibold text-slate-800">Correction history</p>@foreach ($record->corrections as $correction)<p>{{ $correction->created_at?->setTimezone('Asia/Manila')->format('M j, g:i A') }} · {{ $correction->editor?->name }}: {{ $correction->reason }}</p>@endforeach</div>@endif
                </details></td></tr>
            @empty <tr><td colspan="6" class="py-10 text-center text-slate-500">No attendance records for this date.</td></tr> @endforelse
        </tbody></table></div>
        <div class="mt-4">{{ $records->links() }}</div>
    </x-card>
</section>
@endsection
