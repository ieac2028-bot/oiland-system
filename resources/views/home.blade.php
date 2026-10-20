@extends('layouts.app')

@section('title', 'Fuel stations, products and offers')

@section('content')
    <section class="bg-gradient-to-br from-deep-950 via-deep-900 to-brand-800">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
            <div class="max-w-3xl">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-brand-100 ring-1 ring-inset ring-white/20">
                    <span class="h-1.5 w-1.5 rounded-full bg-accent-400"></span>
                    {{ $branchCount }} stations across {{ $cityCount }} cities
                </span>

                <h1 class="mt-5 text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                    Find the station that fits your trip.
                </h1>

                <p class="mt-4 text-lg text-brand-100/90">
                    Find Diesel, Premium, and Green at your nearest branch, check station hours,
                    and read the latest head-office notices.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <x-button :href="route('branches.index')" variant="accent" class="!px-5 !py-3">Find a station</x-button>
                    <a href="{{ route('branches.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-white/10 px-5 py-3 text-sm font-semibold text-white ring-1 ring-inset ring-white/25 transition hover:bg-white/20">
                        Browse the directory
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="-mt-20 rounded-2xl border border-slate-200 bg-white p-6 shadow-lg sm:p-8">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">Find a station</h2>
                <p class="mt-1 text-sm text-slate-500">Search the network or jump straight to a filter.</p>

                <form action="{{ route('branches.index') }}" method="GET" class="mt-5 flex flex-col gap-3 sm:flex-row">
                    <label for="q" class="sr-only">Search branches</label>
                    <x-input id="q" name="q" value="{{ request('q') }}" placeholder="Search by name, city or street" class="flex-1 !py-3" />
                    <x-button type="submit" variant="accent" class="!py-3">Search stations</x-button>
                </form>

                @if ($quickFilters->isNotEmpty())
                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        @foreach ($quickFilters as $filter)
                            <a href="{{ route('branches.index', $filter['query']) }}"
                                class="home-filter-card group flex items-center gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-brand-300 hover:bg-brand-50/50">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700 transition group-hover:bg-brand-600 group-hover:text-white">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $filter['icon'] }}" />
                                    </svg>
                                </span>
                                <span class="text-sm font-semibold text-slate-700 group-hover:text-brand-800">{{ $filter['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($regions->isNotEmpty())
                    <div class="mt-6 flex flex-wrap items-center gap-2">
                        <span class="text-xs font-medium uppercase tracking-wide text-slate-400">Popular regions</span>
                        @foreach ($regions->take(8) as $region)
                            <a href="{{ route('branches.index', ['region' => $region]) }}"
                                class="home-region-chip rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 transition hover:bg-brand-600 hover:text-white">
                                {{ $region }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if ($fuels->isNotEmpty())
        <section class="bg-white py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Fuels we carry</h2>
                        <p class="mt-1 text-sm text-slate-500">Check which fuel grades each station offers.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('careers.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">View job openings</a>
                        <a href="{{ route('branches.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">See all stations &rarr;</a>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($fuels as $fuel)
                        <div class="flex flex-col rounded-xl border border-slate-200 bg-white p-5">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-accent-50 text-accent-600 transition group-hover:bg-accent-500 group-hover:text-white">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M5 8h14M7 8l-3 6h6L7 8Zm10 0-3 6h6l-3-6Z" />
                                    </svg>
                                </span>
                                <h3 class="font-semibold text-slate-900">{{ $fuel->displayName() }}</h3>
                            </div>
                            <p class="mt-3 line-clamp-2 text-sm text-slate-600">{{ $fuel->short_description }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="bg-gradient-to-r from-brand-800 to-deep-900 py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 sm:grid-cols-3">
                <x-stat label="Network average rating" :value="number_format($averageRating, 1).' / 5'" tone="brand" class="!border-white/15 !bg-white/10" />
                <x-stat label="Cities served" :value="number_format($cityCount)" tone="accent" class="!border-white/15 !bg-white/10" />
                <x-stat label="My open inquiries" :value="number_format($openTicketCount)" tone="deep" class="!border-white/15 !bg-white/10" />
            </div>
        </div>
    </section>

    @if ($announcements->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Offers and notices</h2>
                        <p class="mt-1 text-sm text-slate-500">Offers and notices from head office. Expired posts hide automatically.</p>
                </div>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-3">
                @foreach ($announcements as $announcement)
                    <article class="flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-2 text-xs">
                            <x-badge tone="slate">{{ $announcement->typeLabel() }}</x-badge>
                            <x-badge :tone="$announcement->scope->tone()">{{ $announcement->scope->label() }}</x-badge>
                            <span class="text-slate-400">Until {{ $announcement->expires_at?->format('d M Y') ?? 'further notice' }}</span>
                        </div>

                        <h3 class="mt-3 font-semibold text-slate-900">{{ $announcement->title }}</h3>
                        <p class="mt-2 line-clamp-3 flex-1 text-sm text-slate-600">{{ $announcement->summary() }}</p>

                        <p class="mt-4 text-xs text-slate-400">
                            Published {{ $announcement->publish_at?->format('d M Y') ?? 'immediately' }}
                        </p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="bg-slate-50 py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Top rated stations</h2>
                    <p class="mt-1 text-sm text-slate-500">Based on published customer reviews.</p>
                </div>
                <a href="{{ route('branches.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">View full directory &rarr;</a>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-3">
                @forelse ($popularBranches as $branch)
                    <a href="{{ route('branches.show', $branch) }}" class="group rounded-xl border border-slate-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate font-semibold text-slate-900 group-hover:text-brand-700">{{ $branch->name }}</h3>
                                <p class="mt-0.5 text-sm text-slate-500">{{ $branch->city }}, {{ $branch->region }}</p>
                            </div>
                            @if ($branch->isOpenNow())
                                <x-badge tone="emerald">Open</x-badge>
                            @else
                                <x-badge tone="slate">Closed</x-badge>
                            @endif
                        </div>

                        <div class="mt-4 flex items-center gap-2">
                            <x-star-rating :rating="(float) ($branch->reviews_avg_rating ?? 0)" show-value />
                            <span class="text-xs text-slate-500">({{ $branch->reviews_count ?? 0 }})</span>
                        </div>

                        <p class="mt-4 line-clamp-2 text-sm text-slate-600">{{ $branch->description }}</p>
                    </a>
                @empty
                    <x-empty-state title="No branches yet" description="Once head office adds stations they will appear here." class="md:col-span-3" />
                @endforelse
            </div>
        </div>
    </section>
@endsection
