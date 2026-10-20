@extends('layouts.admin')

@section('title', 'Activity log')
@section('subtitle', $scopeLabel ?? 'All branches')

@php
    /**
     * Map an action namespace to a badge tone so the trail is scannable.
     */
    $actionTone = function (string $action): string {
        return match (Str::before($action, '.')) {
            'created', 'published', 'approved' => 'emerald',
            'updated', 'imported', 'recorded' => 'sky',
            'deleted', 'rejected', 'hidden' => 'rose',
            'requested', 'flagged', 'assigned', 'changed' => 'amber',
            default => 'slate',
        };
    };

    $actionLabel = fn (string $action): string => $actions[$action] ?? Str::headline($action);
@endphp

@section('content')
    <x-card padding="p-0">
        <form method="GET" action="{{ route('admin.activity-log.index') }}" class="grid gap-3 border-b border-slate-200 px-6 py-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-form-field name="q" label="Search">
                <x-input name="q" :value="$filters['q'] ?? null" placeholder="Description or person" />
            </x-form-field>

            <x-form-field name="action" label="Action">
                <x-select name="action">
                    <option value="">Every action</option>
                    @foreach ($actions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['action'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-form-field>

            <x-form-field name="from" label="From">
                <x-input name="from" type="date" :value="$filters['from'] ?? null" />
            </x-form-field>

            <x-form-field name="to" label="To">
                <x-input name="to" type="date" :value="$filters['to'] ?? null" />
            </x-form-field>

            <x-form-field name="user_id" label="Person">
                <x-select name="user_id">
                    <option value="">Anyone</option>
                    @foreach ($users as $person)
                        <option value="{{ $person->id }}" @selected((int) ($filters['user_id'] ?? 0) === $person->id)>
                            {{ $person->name }}
                        </option>
                    @endforeach
                </x-select>
            </x-form-field>

            @if ($branches->isNotEmpty())
                <x-form-field name="branch_id" label="Station">
                    <x-select name="branch_id">
                        <option value="">All stations</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) ($filters['branch_id'] ?? 0) === $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </x-select>
                </x-form-field>
            @endif

            <div class="flex items-end gap-2">
                <x-button type="submit">Filter</x-button>
                @if (array_filter($filters))
                    <x-button :href="route('admin.activity-log.index')" variant="ghost">Reset</x-button>
                @endif
            </div>
        </form>

        @if ($logs->isEmpty())
            <div class="p-6">
                <x-empty-state
                    title="Nothing recorded"
                    description="No activity matches these filters. Prices, sales, tickets, and account changes all appear here." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-6 py-3 font-medium">When</th>
                            <th class="px-6 py-3 font-medium">Action</th>
                            <th class="px-6 py-3 font-medium">What happened</th>
                            <th class="px-6 py-3 font-medium">Person</th>
                            <th class="px-6 py-3 font-medium">Station</th>
                            <th class="px-6 py-3 text-right font-medium">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-3 text-xs text-slate-500">
                                    {{ $log->created_at->format('d M Y') }}
                                    <span class="block text-slate-400">{{ $log->created_at->format('H:i') }}</span>
                                </td>
                                <td class="px-6 py-3">
                                    <x-badge :tone="$actionTone($log->action)">{{ $actionLabel($log->action) }}</x-badge>
                                </td>
                                <td class="px-6 py-3 text-slate-700">{{ $log->description ?: '—' }}</td>
                                <td class="px-6 py-3">
                                    <p class="text-xs font-medium text-slate-700">{{ $log->user_name }}</p>
                                    @if ($log->user_role)
                                        <p class="text-xs text-slate-500">{{ Str::headline($log->user_role) }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-xs text-slate-600">{{ $log->branch?->name ?? '—' }}</td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('admin.activity-log.show', $log) }}"
                                       class="rounded-lg px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="border-t border-slate-200 px-6 py-3">
                    {{ $logs->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection
