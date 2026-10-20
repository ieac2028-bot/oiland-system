@extends('layouts.admin')

@section('title', $review->title ?: 'Review')
@section('subtitle', $review->branch?->name ?? 'Unknown station')

@section('content')
    <nav class="text-sm text-slate-500">
        <a href="{{ route('admin.reviews.index') }}" class="hover:underline">Reviews</a>
        <span class="mx-2">/</span>
        <span class="text-slate-700">{{ $review->title ?: 'Review' }}</span>
    </nav>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card padding="p-0">
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :tone="$review->status->tone()">{{ $review->status->label() }}</x-badge>
                                @if ($review->is_flagged)
                                    <x-badge tone="rose">Flagged {{ $review->flagged_at?->format('d M Y') }}</x-badge>
                                @endif
                            </div>
                            <h1 class="mt-2 text-lg font-semibold text-slate-900">{{ $review->title ?: 'Untitled review' }}</h1>
                            <p class="mt-1 text-xs text-slate-500">
                                by {{ $review->user?->name ?? 'Deleted customer' }} · {{ $review->created_at->format('d M Y \a\t H:i') }}
                            </p>
                        </div>

                        <div class="flex items-center gap-0.5" title="{{ $review->rating }} out of 5">
                            @for ($star = 1; $star <= 5; $star++)
                                <svg viewBox="0 0 20 20" fill="{{ $star <= $review->rating ? 'currentColor' : 'none' }}"
                                     @class([
                                         'h-6 w-6',
                                         'text-amber-400' => $star <= $review->rating,
                                         'text-slate-200' => $star > $review->rating,
                                     ])
                                     aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.79L10 14.8l-5.2 2.73.99-5.79-4.21-4.1 5.82-.85L10 1.5z" />
                                </svg>
                            @endfor
                            <span class="ml-2 text-sm font-semibold text-slate-700">{{ $review->rating }}/5</span>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $review->body }}</p>
                </div>
            </x-card>

            @if ($canModerate)
                <x-card title="Moderation" subtitle="This is what customers see on the station page.">
                    <div class="flex flex-wrap gap-2">
                        @if (! $review->isPublished())
                            <form method="POST" action="{{ route('admin.reviews.publish', $review) }}">
                                @csrf
                                <x-button type="submit">Publish</x-button>
                            </form>
                        @endif

                        @if ($review->isPublished())
                            <form method="POST" action="{{ route('admin.reviews.hide', $review) }}">
                                @csrf
                                <x-button type="submit" variant="secondary">Hide from public pages</x-button>
                            </form>
                        @endif

                        @if (! $review->is_flagged)
                            <form method="POST" action="{{ route('admin.reviews.flag', $review) }}">
                                @csrf
                                <x-button type="submit" variant="ghost">Flag for attention</x-button>
                            </form>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" class="mt-5 border-t border-slate-200 pt-5"
                          onsubmit="return confirm('Delete this review permanently? This cannot be undone.');">
                        @csrf
                        @method('DELETE')

                        <p class="text-xs text-slate-500">
                            Deleting removes the review for good. Hiding keeps the record but takes it off the public station page.
                        </p>
                        <x-button type="submit" variant="danger" class="mt-3">Delete review</x-button>
                    </form>
                </x-card>
            @else
                <x-card title="Moderation" subtitle="Only head office can publish or hide a review.">
                    @if (! $review->is_flagged)
                        <form method="POST" action="{{ route('admin.reviews.flag', $review) }}">
                            @csrf
                            <x-button type="submit" variant="secondary">Flag for moderator attention</x-button>
                        </form>
                    @else
                        <p class="text-sm text-slate-600">Flagged{{ $review->flagger?->name ? ' by '.$review->flagger->name : '' }}. Head office has been notified.</p>
                    @endif
                </x-card>
            @endif
        </div>

        <aside class="space-y-6">
            <x-card title="Station">
                @if ($review->branch)
                    <p class="text-sm font-semibold text-slate-800">{{ $review->branch->name }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $review->branch->city }}</p>
                    <x-button :href="route('admin.branches.show', $review->branch)" variant="secondary" class="mt-4 w-full">
                        Open station
                    </x-button>
                @else
                    <p class="text-sm text-slate-500">The station was removed.</p>
                @endif
            </x-card>

            <x-card title="Audit trail">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-400">Posted</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $review->created_at->format('d M Y H:i') }}</dd>
                    </div>

                    @if ($review->moderated_at)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-400">Last moderated</dt>
                            <dd class="mt-0.5 text-slate-700">
                                {{ $review->moderated_at->format('d M Y H:i') }}
                                @if ($review->moderator)
                                    <span class="text-xs text-slate-500">by {{ $review->moderator->name }}</span>
                                @endif
                            </dd>
                        </div>
                    @endif

                    @if ($review->flagged_at)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-400">Flagged</dt>
                            <dd class="mt-0.5 text-slate-700">
                                {{ $review->flagged_at->format('d M Y H:i') }}
                                @if ($review->flagger)
                                    <span class="text-xs text-slate-500">by {{ $review->flagger->name }}</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </x-card>
        </aside>
    </div>
@endsection
