@extends('layouts.app')

@section('title', 'My reviews')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">My reviews</h1>
                <p class="mt-1 text-slate-600">
                    Every review you have written. Published reviews appear on the station page; edits are re-checked by a moderator.
                </p>
            </div>

            <x-button :href="route('branches.index')" variant="secondary">Review a station</x-button>
        </header>

        <div class="mt-6 space-y-4">
            @forelse ($reviews as $review)
                <x-card>
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :tone="$review->status->tone()">{{ $review->status->label() }}</x-badge>
                                @if ($review->is_flagged)
                                    <x-badge tone="rose">Flagged</x-badge>
                                @endif
                                <x-star-rating :rating="(float) $review->rating" />
                            </div>

                            <a href="{{ route('branches.show', $review->branch) }}" class="mt-2 block font-semibold text-slate-900 hover:text-brand-700">
                                {{ $review->branch?->name ?? 'Removed station' }}
                            </a>

                            @if (filled($review->title))
                                <p class="mt-1 text-sm font-medium text-slate-700">{{ $review->title }}</p>
                            @endif
                            <p class="mt-1 text-sm text-slate-600">{{ $review->body }}</p>
                            <p class="mt-2 text-xs text-slate-400">Written {{ $review->created_at->format('d M Y') }}</p>
                        </div>

                        <div class="flex shrink-0 flex-col gap-2">
                            @if ($review->isPublished())
                                <x-button :href="route('branches.show', $review->branch)" variant="ghost">View live</x-button>
                            @endif
                        </div>
                    </div>

                    <details class="mt-4 border-t border-slate-100 pt-4">
                        <summary class="cursor-pointer text-sm font-semibold text-brand-700">Edit this review</summary>

                        <form method="POST" action="{{ route('my.reviews.update', $review) }}" class="mt-4 space-y-4">
                            @csrf
                            @method('PUT')

                            <x-form-field name="rating" label="Rating" required>
                                <x-select id="rating-{{ $review->id }}" name="rating" required>
                                    @for ($i = \App\Models\Review::MAX_RATING; $i >= 1; $i--)
                                        <option value="{{ $i }}" @selected((int) old('rating', $review->rating) === $i)>{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                                    @endfor
                                </x-select>
                            </x-form-field>

                            <x-form-field name="title" label="Headline">
                                <x-input id="title-{{ $review->id }}" name="title" :value="old('title', $review->title)" maxlength="120" />
                            </x-form-field>

                            <x-form-field name="body" label="Your experience" required>
                                <x-textarea id="body-{{ $review->id }}" name="body" rows="4" required maxlength="3000">{{ old('body', $review->body) }}</x-textarea>
                            </x-form-field>

                            <div class="flex flex-wrap items-center gap-3">
                                <x-button type="submit" variant="secondary">Resubmit for moderation</x-button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('my.reviews.destroy', $review) }}"
                              data-confirm="Delete this review permanently?"
                              class="mt-3">
                            @csrf
                            @method('DELETE')
                            <x-button type="submit" variant="ghost">Delete this review</x-button>
                        </form>
                    </details>
                </x-card>
            @empty
                <x-empty-state
                    title="You have not written any reviews yet"
                    description="Tell other drivers what a station is really like.">
                    <x-slot name="action">
                        <x-button :href="route('branches.index')">Find a station to review</x-button>
                    </x-slot>
                </x-empty-state>
            @endforelse
        </div>

        @if ($reviews->hasPages())
            <div class="mt-6">{{ $reviews->links() }}</div>
        @endif
    </div>
@endsection
