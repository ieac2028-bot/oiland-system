@extends('layouts.app')

@section('title', 'My dashboard')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Hello, {{ Str::before(auth()->user()->name, ' ') }}</h1>
                <p class="mt-1 text-slate-600">Track your inquiries, followed stations, and reviews.</p>
            </div>

            <div class="flex gap-2">
                <x-button :href="route('my.tickets.create')">Open an inquiry</x-button>
                <x-button :href="route('branches.index')" variant="secondary">Find a station</x-button>
            </div>
        </header>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat label="Pending" :value="$ticketCounts['pending']" tone="amber" icon="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            <x-stat label="In progress" :value="$ticketCounts['in_progress']" tone="sky" icon="M4.5 12.75l6 6 9-13.5" />
            <x-stat label="Resolved" :value="$ticketCounts['resolved']" tone="emerald" icon="M9 12.75 11.25 15 15 9.75" />
            <x-stat label="Reviews written" :value="$pendingReviewCount" tone="violet" icon="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-card title="Your open inquiries" padding="p-0">
                    <x-slot name="actions">
                        <x-button :href="route('my.tickets.index')" variant="ghost">View all</x-button>
                    </x-slot>

                    @if ($openTickets->isEmpty())
                        <x-empty-state
                            title="No open inquiries"
                            description="If something is not right at the pump, open an inquiry and the branch will reply here.">
                            <x-slot name="action">
                                <x-button :href="route('my.tickets.create')">Open an inquiry</x-button>
                            </x-slot>
                        </x-empty-state>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($openTickets as $ticket)
                                <li>
                                    <a href="{{ route('my.tickets.show', $ticket) }}" class="block px-6 py-4 transition hover:bg-slate-50">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-xs text-slate-400">{{ $ticket->reference }}</span>
                                            <x-badge :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-badge>
                                            <x-badge :tone="$ticket->priority->tone()">{{ $ticket->priority->label() }}</x-badge>
                                            <span class="ml-auto text-xs text-slate-400">{{ $ticket->lastActivityAt()->diffForHumans() }}</span>
                                        </div>
                                        <p class="mt-1.5 font-medium text-slate-800">{{ $ticket->subject }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $ticket->branch?->name }} · {{ $ticket->categoryLabel() }}</p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>

            </div>

            <aside class="space-y-6">
                <x-card title="Announcements" padding="p-0">
                    @if ($announcements->isEmpty())
                        <p class="px-6 py-6 text-sm text-slate-500">No announcements right now.</p>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($announcements as $announcement)
                                <li class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <x-badge :tone="$announcement->scope->tone()">{{ $announcement->scope->label() }}</x-badge>
                                        <span class="ml-auto text-xs text-slate-400">{{ $announcement->publish_at?->format('d M') }}</span>
                                    </div>
                                    <p class="mt-1.5 text-sm font-semibold text-slate-800">{{ $announcement->title }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $announcement->summary(120) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>

                <x-card title="Stations you follow" padding="p-0">
                    <x-slot name="actions">
                        <x-button :href="route('my.branches.index')" variant="ghost">Manage</x-button>
                    </x-slot>

                    @if ($followedBranches->isEmpty())
                        <p class="px-6 py-6 text-sm text-slate-500">You are not following any stations yet.</p>
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($followedBranches as $branch)
                                <li class="flex items-center gap-3 px-6 py-3">
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('branches.show', $branch) }}" class="block truncate text-sm font-medium text-slate-800 hover:text-brand-700">
                                            {{ $branch->name }}
                                        </a>
                                        <p class="truncate text-xs text-slate-500">{{ $branch->city }} · {{ $branch->reviews_count }} reviews</p>
                                    </div>
                                    <x-star-rating :rating="(float) ($branch->reviews_avg_rating ?? 0)" />
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>

                @if ($nearbyBranches->isNotEmpty())
                    <x-card title="More stations near you" padding="p-0">
                        <ul class="divide-y divide-slate-100">
                            @foreach ($nearbyBranches as $branch)
                                <li class="px-6 py-3">
                                    <a href="{{ route('branches.show', $branch) }}" class="block">
                                        <p class="text-sm font-medium text-slate-800 hover:text-brand-700">{{ $branch->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $branch->fullAddress() }}</p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </x-card>
                @endif
            </aside>
        </div>
    </div>
@endsection
