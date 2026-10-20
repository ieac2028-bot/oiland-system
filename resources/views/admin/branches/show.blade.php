@extends('layouts.admin')

@section('title', $branch->name)
@section('subtitle', $branch->code.' · '.$branch->fullAddress())

@section('content')
    <nav class="text-sm text-slate-500">
        <a href="{{ route('admin.branches.index') }}" class="hover:underline">Stations</a>
        <span class="mx-2">/</span>
        <span class="text-slate-700">{{ $branch->name }}</span>
    </nav>

    <div class="mt-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <x-badge :tone="$branch->is_active ? 'emerald' : 'slate'">
                    {{ $branch->is_active ? 'Active' : 'Inactive' }}
                </x-badge>
                @if ($branch->is_24_hours)
                    <x-badge tone="brand">24 hours</x-badge>
                @endif
                @if ($branch->isOpenNow())
                    <x-badge tone="emerald">Open now</x-badge>
                @else
                    <x-badge tone="slate">Closed now</x-badge>
                @endif
            </div>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $branch->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $branch->city }}, {{ $branch->region }}
                @if ($branch->phone) · {{ $branch->phone }} @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-button :href="route('branches.show', $branch)" variant="secondary">Public page</x-button>

            @can('manageAvailability', $branch)
                <x-button :href="route('admin.branches.availability.edit', $branch)" variant="secondary">Availability</x-button>
            @endcan

            @can('update', $branch)
                <x-button :href="route('admin.branches.edit', $branch)">Edit</x-button>
            @endcan
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Active prices" :value="$stats['prices']" tone="brand" icon="M4.5 12.75l6 6 9-13.5" />
        <x-stat label="Items offered" :value="$stats['catalog_items']" tone="sky" icon="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
        <x-stat label="Open inquiries" :value="$stats['open_tickets']" tone="amber" icon="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        <x-stat label="Average rating" :value="$stats['average_rating'] > 0 ? number_format($stats['average_rating'], 1).' / 5' : '—'" tone="violet" icon="M11.48 3.5l2.2 4.46 4.92.71-3.56 3.47.84 4.9-4.4-2.32-4.4 2.32.84-4.9L4.4 8.67l4.92-.71 2.2-4.46z" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Opening hours">
                @if ($branch->is_24_hours)
                    <p class="text-sm text-slate-600">This station is marked as open 24 hours a day.</p>
                @else
                    <dl class="divide-y divide-slate-100">
                        @foreach ($weekDays as $dayKey => $dayLabel)
                            @php
                                $isToday = $dayKey === strtolower(now()->format('l'));
                                $dayHours = $branch->hoursForDay($dayKey);
                            @endphp
                            <div @class(['flex items-center justify-between gap-4 py-2.5', 'font-semibold text-slate-900' => $isToday, 'text-slate-600' => ! $isToday])>
                                <dt>
                                    {{ $dayLabel }}
                                    @if ($isToday)
                                        <x-badge tone="brand" class="ml-2">Today</x-badge>
                                    @endif
                                </dt>
                                <dd class="text-sm">
                                    @if ($dayHours === null)
                                        <span class="text-slate-400">Closed</span>
                                    @elseif (blank($dayHours['close'] ?? null))
                                        <span>From {{ $dayHours['open'] }}</span>
                                    @else
                                        <span>{{ $dayHours['open'] }}&ndash;{{ $dayHours['close'] }}</span>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </x-card>

            <x-card title="Staff at this station" padding="p-0">
                @if ($branch->staff->isEmpty())
                    <div class="p-6">
                        <x-empty-state
                            title="No staff assigned"
                            description="Add an Officer in Charge from the users screen so someone can manage this station." />
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($branch->staff as $member)
                            <li class="flex items-center justify-between gap-4 px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-600">
                                        {{ Str::upper(Str::substr($member->name, 0, 1)) }}
                                    </span>
                                    <div>
                                        <p class="text-sm font-medium text-slate-800">{{ $member->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $member->email }}</p>
                                    </div>
                                </div>
                                <x-badge tone="slate">{{ $member->role->label() }}</x-badge>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card title="Recent announcements" padding="p-0">
                @if ($branch->announcements->isEmpty())
                    <div class="p-6">
                        <x-empty-state title="Nothing published" description="No announcement has been pinned to this station." />
                    </div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($branch->announcements as $announcement)
                            <li class="px-6 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-medium text-slate-800">{{ $announcement->title }}</p>
                                    <x-badge :tone="match ($announcement->statusLabel()) {
                                        'Live' => 'emerald',
                                        'Scheduled' => 'sky',
                                        'Expired' => 'slate',
                                        default => 'amber',
                                    }">{{ $announcement->statusLabel() }}</x-badge>
                                </div>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $announcement->publish_at?->format('d M Y') ?? 'Not scheduled' }}
                                    @if ($announcement->scope->value === 'global') · Network-wide @endif
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="border-t border-slate-200 px-6 py-3">
                    <a href="{{ route('admin.announcements.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">
                        Manage announcements
                    </a>
                </div>
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Location">
                @if ($branch->hasCoordinates())
                    <div class="overflow-hidden rounded-lg border border-slate-200">
                        <iframe
                            title="Map showing {{ $branch->name }}"
                            class="h-48 w-full"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://www.openstreetmap.org/export/embed.html?bbox={{ $branch->longitude - 0.01 }}%2C{{ $branch->latitude - 0.01 }}%2C{{ $branch->longitude + 0.01 }}%2C{{ $branch->latitude + 0.01 }}&layer=mapnik&marker={{ $branch->latitude }}%2C{{ $branch->longitude }}"></iframe>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">
                        {{ number_format($branch->latitude, 4) }}, {{ number_format($branch->longitude, 4) }}
                    </p>
                @else
                    <p class="text-sm text-slate-500">
                        No coordinates yet. They are looked up from the address when the station is saved.
                    </p>
                @endif
            </x-card>

            <x-card title="Contact">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">Address</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $branch->fullAddress() }}</dd>
                    </div>
                    @if ($branch->phone)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-400">Phone</dt>
                            <dd class="mt-0.5 text-slate-700">{{ $branch->phone }}</dd>
                        </div>
                    @endif
                    @if ($branch->email)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-400">Email</dt>
                            <dd class="mt-0.5 text-slate-700">{{ $branch->email }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">Station code</dt>
                        <dd class="mt-0.5 font-mono text-slate-700">{{ $branch->code }}</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Sales" subtitle="Lifetime for this station.">
                <x-button :href="route('admin.sales.index', ['branch_id' => $branch->id])" variant="secondary" class="w-full">
                    View sales ledger
                </x-button>
                <x-button :href="route('admin.prices.index', ['branch_id' => $branch->id])" variant="secondary" class="mt-2 w-full">
                    View prices
                </x-button>
            </x-card>
        </aside>
    </div>
@endsection
