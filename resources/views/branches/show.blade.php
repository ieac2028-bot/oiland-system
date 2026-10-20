@extends('layouts.app')

@section('title', $branch->name)
@section('description', $branch->fullAddress())

@section('content')
    <div class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <nav class="text-sm text-slate-500">
                <a href="{{ route('home') }}" class="hover:underline">Home</a>
                <span class="mx-2">/</span>
                <a href="{{ route('branches.index') }}" class="hover:underline">Stations</a>
                <span class="mx-2">/</span>
                <span class="text-slate-700">{{ $branch->name }}</span>
            </nav>

            <div class="mt-4 flex flex-wrap items-start justify-between gap-6">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-semibold tracking-tight text-slate-900">{{ $branch->name }}</h1>
                        @if ($branch->isOpenNow())
                            <x-badge tone="emerald">Open now</x-badge>
                        @else
                            <x-badge tone="slate">Closed</x-badge>
                        @endif
                        @unless ($branch->is_active)
                            <x-badge tone="amber">Temporarily closed</x-badge>
                        @endunless
                    </div>

                    <p class="mt-2 text-slate-600">{{ $branch->fullAddress() }}</p>

                    <div class="mt-3 flex flex-wrap items-center gap-4 text-sm text-slate-600">
                        <span class="inline-flex items-center gap-1.5">
                            <x-star-rating :rating="(float) $averageRating" show-value />
                            <span class="text-xs text-slate-500">{{ $reviewCount }} {{ Str::plural('review', $reviewCount) }}</span>
                        </span>

                        @if ($branch->phone)
                            <a href="tel:{{ $branch->phone }}" class="inline-flex items-center gap-1.5 hover:text-brand-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                </svg>
                                {{ $branch->phone }}
                            </a>
                        @endif

                        @if ($branch->email)
                            <a href="mailto:{{ $branch->email }}" class="hover:text-brand-700">{{ $branch->email }}</a>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @auth
                        <form method="POST" action="{{ $isFollowing ? route('branches.unfollow', $branch) : route('branches.follow', $branch) }}">
                            @csrf
                            @if ($isFollowing)
                                @method('DELETE')
                            @endif
                            <x-button type="submit" :variant="$isFollowing ? 'secondary' : 'primary'">
                                {{ $isFollowing ? 'Following' : 'Follow station' }}
                            </x-button>
                        </form>

                        <x-button :href="route('my.tickets.create', ['branch_id' => $branch->id])" variant="secondary">
                            Contact this branch
                        </x-button>
                    @else
                        <x-button :href="route('login')" variant="secondary">Sign in to contact us</x-button>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="space-y-8 lg:col-span-2">
                @if ($announcements->isNotEmpty())
                    <x-card title="Notices for this station">
                        <ul class="space-y-4">
                            @foreach ($announcements as $announcement)
                                <li class="border-l-2 border-brand-500 pl-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">{{ $announcement->typeLabel() }}</p>
                                    <p class="text-sm font-semibold text-slate-900">{{ $announcement->title }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $announcement->summary() }}</p>
                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $announcement->scope->label() }} · until {{ $announcement->expires_at?->format('d M Y') ?? 'further notice' }}
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    </x-card>
                @endif

                <x-card title="What this station offers">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Fuel grades available</h3>
                            @if ($products->isEmpty())
                                <p class="mt-2 text-sm text-slate-500">No fuel grades listed.</p>
                            @else
                                <ul class="mt-2 space-y-1.5 text-sm text-slate-600">
                                    @foreach ($products as $item)
                                        <li class="flex items-center gap-2">
                                            <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                                            {{ $item->displayName() }}
                                            @if ($item->unit)
                                                <span class="text-xs text-slate-400">per {{ $item->unit }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                    </div>

                    @if ($branch->description)
                        <p class="mt-6 border-t border-slate-100 pt-4 text-sm text-slate-600">{{ $branch->description }}</p>
                    @endif
                </x-card>

                <x-card title="Customer reviews">
                    <x-slot name="actions">
                        @auth
                            <x-button
                                :href="$existingReview ? route('my.reviews.index') : route('reviews.create', $branch)"
                                variant="secondary">
                                {{ $existingReview ? 'Edit your review' : 'Write a review' }}
                            </x-button>
                        @endauth
                    </x-slot>

                    <div class="grid gap-6 sm:grid-cols-3">
                        <div class="text-center">
                            <p class="text-4xl font-semibold tracking-tight text-slate-900">{{ number_format((float) $averageRating, 1) }}</p>
                            <x-star-rating :rating="(float) $averageRating" size="md" class="mt-1" />
                            <p class="mt-1 text-xs text-slate-500">{{ $reviewCount }} {{ Str::plural('review', $reviewCount) }}</p>
                        </div>

                        <div class="space-y-1.5 sm:col-span-2">
                            @foreach ($ratingBreakdown as $row)
                                <div class="flex items-center gap-3 text-xs">
                                    <span class="w-8 shrink-0 text-slate-600">{{ $row['rating'] }} ★</span>
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-amber-400" style="width: {{ $row['percentage'] }}%"></div>
                                    </div>
                                    <span class="w-8 shrink-0 text-right text-slate-500">{{ $row['count'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <ul class="mt-6 divide-y divide-slate-100">
                        @forelse ($reviews as $review)
                            <li class="py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">
                                        {{ $review->user?->initials() ?? '?' }}
                                    </span>
                                    <span class="text-sm font-medium text-slate-800">{{ $review->user?->name ?? 'Deleted account' }}</span>
                                    <x-star-rating :rating="(float) $review->rating" />
                                    <span class="ml-auto text-xs text-slate-400">{{ $review->created_at->diffForHumans() }}</span>
                                </div>

                                @if (filled($review->title))
                                    <p class="mt-2 text-sm font-semibold text-slate-800">{{ $review->title }}</p>
                                @endif

                                <p class="mt-2 text-sm text-slate-600">{{ $review->body }}</p>
                            </li>
                        @empty
                            <li class="py-8 text-center text-sm text-slate-500">No published reviews for this station yet.</li>
                        @endforelse
                    </ul>

                    @if ($reviews->hasPages())
                        <div class="mt-4">{{ $reviews->links() }}</div>
                    @endif
                </x-card>
            </div>

            <aside class="space-y-6">
                @if (filled($branch->photos))
                    <x-card title="Station photos">
                        <div class="grid grid-cols-2 gap-3">
                            @foreach ($branch->photos as $photo)
                                <a href="{{ $photo }}" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-lg border border-slate-200">
                                    <img src="{{ $photo }}" alt="{{ $branch->name }} station photo {{ $loop->iteration }}" loading="lazy" class="h-32 w-full object-cover">
                                </a>
                            @endforeach
                        </div>
                    </x-card>
                @endif

                <x-card title="Opening hours" padding="p-0">
                    <dl class="divide-y divide-slate-100">
                        @foreach ($branch->hoursSummary() as $day => $value)
                            <div class="flex items-center justify-between px-5 py-2.5 text-sm">
                                <dt @class(['text-slate-700', 'font-semibold' => $day === now()->format('l')])>{{ $day }}</dt>
                                <dd @class(['text-slate-500', 'font-medium text-brand-700' => $day === now()->format('l')])>{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-card>

                @if ($branch->hasCoordinates())
                    <x-card title="Location" padding="p-0">
                        <a href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($branch->latitude.','.$branch->longitude) }}" target="_blank" rel="noopener noreferrer" class="m-5 inline-flex rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">
                            Open in Google Maps
                        </a>
                        <div class="h-64 w-full"
                             data-locator
                             data-map-state="ready"
                             data-branches="{{ \App\Models\Branch::mapPayloadFor(collect([$branch]))->toJson() }}"
                             data-initial-lat="{{ $branch->latitude }}"
                             data-initial-lng="{{ $branch->longitude }}"
                             data-initial-zoom="14"
                             data-tile-url="{{ config('maps.tile_url') }}"
                             data-tile-attribution="{{ config('maps.tile_attribution') }}"
                             data-max-zoom="{{ config('maps.max_zoom') }}"
                             data-nominatim-url="{{ route('branches.geocode') }}"
                             data-osrm-url="{{ config('maps.osrm_url') }}"></div>

                        <div class="border-t border-slate-100 p-5">
                            <label for="branch-address-search" class="text-sm font-medium text-slate-700">Get directions from</label>
                            <x-input id="branch-address-search" data-locator-search type="search" placeholder="Enter your address" class="mt-1.5" />
                            <p class="mt-2" data-locator-status aria-live="polite"></p>
                            <p class="mt-3 text-xs text-slate-400">
                                Map data &copy;
                                <a class="underline" href="https://www.openstreetmap.org/copyright" rel="noopener noreferrer" target="_blank">OpenStreetMap</a>
                                contributors. Address search by Nominatim, routing by OSRM.
                            </p>
                        </div>

                        <div class="hidden border-t border-slate-100" data-locator-route>
                            <p class="px-5 py-3 text-sm text-slate-600" data-locator-route-summary></p>
                            <ol class="max-h-56 overflow-y-auto border-t border-slate-100 px-5" data-locator-route-steps></ol>
                        </div>
                    </x-card>
                @endif
            </aside>
        </div>
    </div>
@endsection
