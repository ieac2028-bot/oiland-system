@extends('layouts.app')

@section('title', 'My inquiries')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">My inquiries</h1>
                <p class="mt-1 text-slate-600">Every question and complaint you have sent to a station, and the replies you received.</p>
            </div>

            <x-button :href="route('my.tickets.create')">Open an inquiry</x-button>
        </header>

        <form method="GET" class="mt-6 flex flex-wrap items-end gap-3">
            <x-select name="status" class="w-48">
                <option value="">Any status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </x-select>

            <x-select name="branch_id" class="w-64">
                <option value="">Any station</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((int) ($filters['branch_id'] ?? 0) === $branch->id)>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </x-select>

            <x-button type="submit" variant="secondary">Filter</x-button>
            <x-button :href="route('my.tickets.index')" variant="ghost">Clear</x-button>
        </form>

        <div class="mt-6 space-y-3">
            @forelse ($tickets as $ticket)
                <x-card padding="p-0">
                    <a href="{{ route('my.tickets.show', $ticket) }}" class="block px-6 py-4 transition hover:bg-slate-50">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs text-slate-400">{{ $ticket->reference }}</span>
                            <x-badge :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-badge>
                            <x-badge :tone="$ticket->priority->tone()">{{ $ticket->priority->label() }}</x-badge>
                            <span class="ml-auto text-xs text-slate-400">{{ $ticket->created_at->format('d M Y') }}</span>
                        </div>

                        <p class="mt-2 font-medium text-slate-900">{{ $ticket->subject }}</p>
                        <p class="mt-1 line-clamp-2 text-sm text-slate-600">{{ $ticket->message }}</p>

                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                            <span>{{ $ticket->branch?->name }}</span>
                            <span>{{ $ticket->categoryLabel() }}</span>
                            @if ($ticket->assignee)
                                <span>Handled by {{ $ticket->assignee->name }}</span>
                            @endif
                            <span>{{ $ticket->replies()->where('is_internal_note', false)->count() }} replies</span>
                        </div>
                    </a>
                </x-card>
            @empty
                <x-empty-state
                    title="No inquiries yet"
                    description="Billing questions, fuel quality concerns or anything else — the branch team replies here.">
                    <x-slot name="action">
                        <x-button :href="route('my.tickets.create')">Open your first inquiry</x-button>
                    </x-slot>
                </x-empty-state>
            @endforelse
        </div>

        @if ($tickets->hasPages())
            <div class="mt-6">{{ $tickets->links() }}</div>
        @endif
    </div>
@endsection
