@extends('layouts.admin')

@section('title', 'Customer inquiries')
@section('subtitle', $counts['pending'].' pending · '.$counts['in_progress'].' in progress · '.$counts['resolved'].' resolved')

@section('content')
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Pending" :value="$counts['pending']" tone="amber" icon="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        <x-stat label="In progress" :value="$counts['in_progress']" tone="sky" icon="M4.5 12.75l6 6 9-13.5" />
        <x-stat label="Resolved" :value="$counts['resolved']" tone="emerald" icon="M9 12.75L11.25 15 15 9.75" />
    </div>

    <x-card class="mt-6" padding="p-0">
        <form method="GET" action="{{ route('admin.tickets.index') }}" class="grid gap-3 border-b border-slate-200 px-6 py-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-form-field name="q" label="Search">
                <x-input name="q" :value="$filters['q'] ?? null" placeholder="Reference, subject or customer" />
            </x-form-field>

            <x-form-field name="status" label="Status">
                <x-select name="status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-form-field>

            <x-form-field name="priority" label="Priority">
                <x-select name="priority">
                    <option value="">All priorities</option>
                    @foreach ($priorities as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['priority'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-form-field>

            <x-form-field name="assigned_to" label="Assigned to">
                <x-select name="assigned_to">
                    <option value="">Anyone</option>
                    <option value="unassigned" @selected(($filters['assigned_to'] ?? null) === 'unassigned')>Unassigned</option>
                    @foreach ($agents as $agent)
                        <option value="{{ $agent->id }}" @selected((int) ($filters['assigned_to'] ?? 0) === $agent->id)>
                            {{ $agent->name }}
                        </option>
                    @endforeach
                </x-select>
            </x-form-field>

            <div class="flex items-end gap-2">
                <x-button type="submit">Filter</x-button>
                @if (array_filter($filters))
                    <x-button :href="route('admin.tickets.index')" variant="ghost">Reset</x-button>
                @endif
            </div>
        </form>

        @if ($tickets->isEmpty())
            <div class="p-6">
                <x-empty-state
                    title="No inquiries"
                    description="When a customer raises a question about a station it appears here for your branch to answer." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-6 py-3 font-medium">Reference</th>
                            <th class="px-6 py-3 font-medium">Subject</th>
                            <th class="px-6 py-3 font-medium">Branch</th>
                            <th class="px-6 py-3 font-medium">Customer</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 font-medium">Priority</th>
                            <th class="px-6 py-3 font-medium">Assigned</th>
                            <th class="px-6 py-3 text-right font-medium">Last activity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($tickets as $ticket)
                            <tr>
                                <td class="px-6 py-3">
                                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="font-mono text-xs font-semibold text-brand-700 hover:underline">
                                        {{ $ticket->reference }}
                                    </a>
                                </td>
                                <td class="px-6 py-3">
                                    <a href="{{ route('admin.tickets.show', $ticket) }}" class="font-medium text-slate-800 hover:text-brand-700">
                                        {{ Str::limit($ticket->subject, 46) }}
                                    </a>
                                    <p class="text-xs text-slate-500">{{ $ticket->categoryLabel() }}</p>
                                </td>
                                <td class="px-6 py-3 text-slate-600">{{ $ticket->branch?->name }}</td>
                                <td class="px-6 py-3 text-slate-600">{{ $ticket->customer?->name ?? 'Deleted customer' }}</td>
                                <td class="px-6 py-3">
                                    <x-badge :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-badge>
                                </td>
                                <td class="px-6 py-3">
                                    <x-badge :tone="$ticket->priority->tone()">{{ $ticket->priority->label() }}</x-badge>
                                </td>
                                <td class="px-6 py-3 text-xs text-slate-600">{{ $ticket->assignee?->name ?? 'Unassigned' }}</td>
                                <td class="px-6 py-3 text-right text-xs text-slate-500">{{ $ticket->lastActivityAt()->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($tickets->hasPages())
                <div class="border-t border-slate-200 px-6 py-3">
                    {{ $tickets->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection
