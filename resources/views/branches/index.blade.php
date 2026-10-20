@extends('layouts.app')

@section('title', 'Find a station')
@section('description', 'Find the nearest Oiland station, check its current operating status, and get driving directions.')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <header>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Find a station</h1>
            <p class="mt-2 text-slate-600">
                {{ $branches->count() }} active {{ Str::plural('Oiland station', $branches->count()) }}.
                Search any address to see stations from nearest to farthest, with current operating status.
            </p>
        </header>

        <form method="GET" action="{{ route('branches.index') }}" class="mt-6 grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
            <x-input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Enter an address or location" class="station-filter-field h-12" data-locator-search />

            <div class="flex items-center gap-2">
                <x-button type="submit">Apply filters</x-button>
                <x-button :href="route('branches.index')" variant="secondary">Reset</x-button>
            </div>
        </form>

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-1">
                <x-card title="Station list" subtitle="Stations appear nearest first after searching an address or using your location." padding="p-0">
                    <ul class="max-h-80 divide-y divide-slate-100 overflow-y-auto px-5" data-locator-results>
                        @forelse ($branches as $branch)
                            <li class="flex items-start justify-between gap-3 py-3">
                                <a href="{{ route('branches.show', $branch) }}" class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-800">{{ $branch->name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $branch->fullAddress() }}</p>
                                    <p class="mt-1 text-xs font-medium {{ $branch->isOpenNow() ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ $branch->isOpenNow() ? 'Open now' : 'Closed now' }} · {{ $branch->hoursSummary()[now()->format('l')] }}
                                    </p>
                                </a>
                                <span class="shrink-0 text-right text-xs text-slate-500" data-locator-distance>{{ $locationQuery ? 'Finding distance…' : 'Enter a location' }}</span>
                            </li>
                        @empty
                            <li class="py-4 text-sm text-slate-500">No stations match those filters.</li>
                        @endforelse
                    </ul>

                    <div class="border-t border-slate-100 px-5 py-3">
                        <button type="button" data-locator-locate
                                class="inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:underline">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                            Use my location
                        </button>
                        <p class="mt-2 text-xs text-slate-500" data-locator-status aria-live="polite">{{ $locationQuery ? 'Finding stations near '.$locationQuery.'…' : 'Search an address or select “Use my location” to rank all active stations.' }}</p>
                    </div>
                </x-card>

                <div class="mt-6 hidden" data-locator-route>
                    <x-card title="Driving route" padding="p-0">
                        <p class="px-6 py-3 text-sm text-slate-600" data-locator-route-summary></p>
                        <ol class="max-h-64 overflow-y-auto border-t border-slate-100 px-6" data-locator-route-steps></ol>
                        <p class="border-t border-slate-100 px-6 py-3 text-xs text-slate-400">
                            Map data &copy;
                            <a class="underline" href="https://www.openstreetmap.org/copyright" rel="noopener noreferrer" target="_blank">OpenStreetMap</a>
                            contributors. Address search by Nominatim, routing by OSRM. Always check live traffic before
                            setting off.
                        </p>
                    </x-card>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="overflow-hidden rounded-xl border border-slate-200 shadow-sm">
                    <div class="h-[32rem] w-full"
                         data-locator
                         data-map-state="ready"
                         data-branches="{{ $mapPayload->toJson() }}"
                         data-initial-search="{{ $locationQuery ?? '' }}"
                         data-initial-lat="{{ $mapPayload->avg('latitude') ?? config('maps.default_latitude') }}"
                         data-initial-lng="{{ $mapPayload->avg('longitude') ?? config('maps.default_longitude') }}"
                         data-initial-zoom="{{ $branches->isEmpty() ? config('maps.default_zoom') : 11 }}"
                         data-tile-url="{{ config('maps.tile_url') }}"
                         data-tile-attribution="{{ config('maps.tile_attribution') }}"
                         data-max-zoom="{{ config('maps.max_zoom') }}"
                         data-nominatim-url="{{ route('branches.geocode') }}"
                         data-osrm-url="{{ config('maps.osrm_url') }}"></div>
                </div>
            </div>
        </div>

    </div>
@endsection
