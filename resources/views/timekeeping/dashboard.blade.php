@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-brand-700">Oiland · Timekeeping</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">{{ $isManager ? 'Attendance overview' : 'Your workday' }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $today->locale('en')->isoFormat('dddd, MMMM D, YYYY') }} · Philippine time</p>
            </div>
        </div>

        @if ($isManager)
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                @foreach ([['Active employees', $activeCount, 'brand'], ['Present / working', $presentCount, 'emerald'], ['Late', $lateCount, 'amber'], ['Absent', $absentCount, 'rose'], ['On leave', $leaveCount, 'sky'], ['Incomplete', $incompleteCount, 'violet']] as [$label, $value, $tone])
                    <x-card padding="p-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</p>
                        <p @class(['mt-2 text-3xl font-bold', 'text-brand-700' => $tone === 'brand', 'text-emerald-700' => $tone === 'emerald', 'text-amber-700' => $tone === 'amber', 'text-rose-700' => $tone === 'rose', 'text-sky-700' => $tone === 'sky', 'text-violet-700' => $tone === 'violet'])>{{ $value }}</p>
                    </x-card>
                @endforeach
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-3">
                <x-card class="lg:col-span-2" title="Attendance · last 7 days" subtitle="Employees with a recorded time-in">
                    <div class="h-72"><canvas data-timekeeping-chart data-series="{{ json_encode($chart, JSON_HEX_APOS | JSON_HEX_QUOT) }}" aria-label="Attendance over the last seven days"></canvas></div>
                </x-card>
                <x-card title="Pending leave requests" subtitle="Awaiting HR or administrator review">
                    <div class="flex h-72 flex-col items-center justify-center text-center">
                        <p class="text-5xl font-bold text-slate-900">{{ $pendingLeaveCount }}</p>
                        <p class="mt-2 text-sm text-slate-500">pending requests</p>
                        <x-button class="mt-5" :href="route('timekeeping.leaves.index', ['status' => 'pending'])" variant="secondary">Review leave</x-button>
                    </div>
                </x-card>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <x-card title="Today's attendance" subtitle="Status is computed from scheduled shifts and approved leave">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead><tr class="text-left text-xs uppercase tracking-wide text-slate-500"><th class="py-3 pr-4">Employee</th><th class="py-3 pr-4">Branch</th><th class="py-3 pr-4">Shift</th><th class="py-3">Status</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($employees as $employee)
                                    @php($metrics = $metricsByEmployee[$employee->id])
                                    <tr>
                                        <td class="py-3 pr-4"><span class="font-medium text-slate-900">{{ $employee->user->name }}</span><span class="block text-xs text-slate-500">{{ $employee->employee_number }}</span></td>
                                        <td class="py-3 pr-4">{{ $employee->user->branch?->name ?? '—' }}</td>
                                        <td class="py-3 pr-4">{{ $employee->defaultShift?->name ?? 'Date-based schedule' }}</td>
                                        <td class="py-3"><x-badge :tone="match($metrics['status']) {'late' => 'amber', 'absent' => 'rose', 'incomplete' => 'violet', 'approved_leave' => 'sky', 'rest_day' => 'slate', 'scheduled' => 'slate', default => 'emerald'}">{{ str_replace('_', ' ', ucfirst($metrics['status'])) }}</x-badge></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-8 text-center text-slate-500">No active employees have been added yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
                <x-card title="Upcoming shifts" subtitle="Next 7 days">
                    <div class="divide-y divide-slate-100">
                        @forelse ($upcomingSchedules as $schedule)
                            <div class="flex items-center justify-between gap-4 py-3">
                                <div><p class="font-medium text-slate-900">{{ $schedule->employee->user->name }}</p><p class="text-xs text-slate-500">{{ $schedule->employee->user->branch?->name }}</p></div>
                                <div class="text-right"><p class="text-sm font-medium text-slate-700">{{ $schedule->shift_date->format('M j') }}</p><p class="text-xs text-slate-500">{{ $schedule->is_rest_day ? 'Rest day' : ($schedule->shift?->name ?? 'No shift') }}</p></div>
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-slate-500">No date-specific schedules yet.</p>
                        @endforelse
                    </div>
                    <x-button class="mt-3" :href="route('timekeeping.schedules.index')" variant="secondary">Manage schedules</x-button>
                </x-card>
            </div>
        @endif
    </section>
@endsection
