@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @php
                $scheduledShift = $todaySchedule?->shift;
                $manilaTime = fn ($value) => $value ? \Carbon\CarbonImmutable::parse($value, 'UTC')->setTimezone('Asia/Manila')->format('g:i A') : '—';
            @endphp
            <div class="grid gap-5 lg:grid-cols-3">
                <x-card class="lg:col-span-2" title="Today’s attendance" subtitle="Times are stored in UTC and displayed in Philippine time">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Assigned shift</p>
                            <p class="mt-2 text-lg font-bold text-slate-900">{{ $todaySchedule?->is_rest_day ? 'Rest day' : ($scheduledShift?->name ?? 'No shift assigned') }}</p>
                            @if ($scheduledShift)
                                <p class="mt-1 text-sm text-slate-600">{{ \Carbon\Carbon::parse($scheduledShift->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($scheduledShift->end_time)->format('g:i A') }} · {{ $scheduledShift->break_minutes }} min break</p>
                            @endif
                            <p class="mt-3 text-sm text-slate-500">{{ $todaySchedule?->notes }}</p>
                        </div>
                        <div class="rounded-xl bg-brand-50 p-5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Status</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ str_replace('_', ' ', ucfirst($todayMetrics['status'])) }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ number_format($todayMetrics['worked_minutes'] / 60, 2) }} hours recorded so far</p>
                        </div>
                    </div>
                    <div class="mt-6 grid grid-cols-2 gap-4 border-y border-slate-100 py-4 text-sm">
                        <div><p class="text-slate-500">Latest time-in</p><p class="mt-1 font-semibold text-slate-900">{{ $manilaTime($openRecord?->time_in_at ?? $todayRecord?->time_in_at) }}</p></div>
                        <div><p class="text-slate-500">Latest time-out</p><p class="mt-1 font-semibold text-slate-900">{{ $manilaTime($todayRecord?->time_out_at) }}</p></div>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-3">
                        @if ($openRecord)
                            <form method="POST" action="{{ route('timekeeping.attendance.time-out') }}">@csrf<x-button variant="accent">Time out</x-button></form>
                        @elseif ($todayRecord)
                            <x-badge tone="emerald">Attendance completed</x-badge>
                        @elseif ($todaySchedule && ! $todaySchedule->is_rest_day && $scheduledShift)
                            <form method="POST" action="{{ route('timekeeping.attendance.time-in') }}">@csrf<x-button variant="primary">Time in</x-button></form>
                        @else
                            <x-badge tone="slate">Time-in is unavailable without a working shift.</x-badge>
                        @endif
                        <x-button :href="route('timekeeping.leaves.index')" variant="secondary">My leave requests</x-button>
                    </div>
                </x-card>
                <x-card title="Your branch" subtitle="Assigned work location">
                    <p class="text-xl font-bold text-slate-900">{{ $employee->user->branch?->name ?? 'Not assigned' }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ $employee->job_title }} · {{ $employee->department }}</p>
                    <p class="mt-4 text-xs text-slate-500">Employee ID · {{ $employee->employee_number }}</p>
                    <div class="mt-6"><x-button :href="route('timekeeping.leaves.index')" variant="secondary">Request leave</x-button></div>
                </x-card>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <x-card title="Attendance history" subtitle="Most recent records">
                    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead><tr class="text-left text-xs uppercase text-slate-500"><th class="py-3 pr-4">Date</th><th class="py-3 pr-4">Time in</th><th class="py-3 pr-4">Time out</th><th class="py-3">Hours</th></tr></thead><tbody class="divide-y divide-slate-100">
                        @forelse ($history as $record)
                            <tr><td class="py-3 pr-4">{{ $record->shift_date->format('M j, Y') }}</td><td class="py-3 pr-4">{{ $manilaTime($record->time_in_at) }}</td><td class="py-3 pr-4">{{ $manilaTime($record->time_out_at) }}</td><td class="py-3">{{ $record->time_in_at && $record->time_out_at ? number_format(\Carbon\CarbonImmutable::parse($record->time_in_at, 'UTC')->diffInMinutes(\Carbon\CarbonImmutable::parse($record->time_out_at, 'UTC')) / 60, 2) : '—' }}</td></tr>
                        @empty <tr><td colspan="4" class="py-8 text-center text-slate-500">No attendance records yet.</td></tr> @endforelse
                    </tbody></table></div>
                </x-card>
                <x-card title="Leave request status">
                    <div class="divide-y divide-slate-100">
                        @forelse ($recentLeaveRequests as $leave)
                            <div class="flex items-center justify-between gap-4 py-3"><div><p class="font-medium text-slate-900">{{ $leave->type?->name }}</p><p class="text-xs text-slate-500">{{ $leave->start_date->format('M j') }} – {{ $leave->end_date->format('M j, Y') }}</p></div><x-badge :tone="match($leave->status) {'approved' => 'emerald', 'rejected' => 'rose', 'pending' => 'amber', default => 'slate'}">{{ ucfirst($leave->status) }}</x-badge></div>
                        @empty <p class="py-8 text-center text-sm text-slate-500">No leave requests yet.</p> @endforelse
                    </div>
                </x-card>
            </div>
    </section>
@endsection
