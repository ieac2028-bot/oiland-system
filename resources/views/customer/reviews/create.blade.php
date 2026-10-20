@extends('layouts.app')

@section('title', 'Review '.$branch->name)

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">
        <nav class="text-sm text-slate-500">
            <a href="{{ route('branches.index') }}" class="hover:underline">Stations</a>
            <span class="mx-2">/</span>
            <a href="{{ route('branches.show', $branch) }}" class="hover:underline">{{ $branch->name }}</a>
        </nav>

        <header class="mt-4">
            <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
                {{ $existingReview ? 'Edit your review' : 'Review '.$branch->name }}
            </h1>
            <p class="mt-1 text-slate-600">
                Reviews are checked by a moderator before they go live, so abusive or misleading content never appears.
            </p>
        </header>

        <x-card class="mt-6">
            <form method="POST" action="{{ route('reviews.store', $branch) }}" class="space-y-5">
                @csrf

                <x-form-field name="rating" label="How was your experience?" required>
                    <div class="space-y-2" data-rating-picker>
                        @for ($i = \App\Models\Review::MAX_RATING; $i >= 1; $i--)
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="radio" name="rating" value="{{ $i }}" required
                                       @checked((int) old('rating', $existingReview->rating ?? 0) === $i)
                                       class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                {{ $i }} star{{ $i > 1 ? 's' : '' }}
                            </label>
                        @endfor
                    </div>
                </x-form-field>

                <x-form-field name="title" label="Headline" hint="Optional. Summarise your visit in a few words.">
                    <x-input id="title" name="title" :value="old('title', $existingReview->title ?? null)" maxlength="120" />
                </x-form-field>

                <x-form-field name="body" label="What happened?" required hint="At least 10 characters. Mention the staff, the pump or the service.">
                    <x-textarea id="body" name="body" rows="6" required maxlength="3000">{{ old('body', $existingReview->body ?? null) }}</x-textarea>
                </x-form-field>

                <div class="flex items-center gap-3 border-t border-slate-100 pt-5">
                    <x-button type="submit">Submit review</x-button>
                    <x-button :href="route('branches.show', $branch)" variant="ghost">Cancel</x-button>
                </div>
            </form>
        </x-card>
    </div>
@endsection
