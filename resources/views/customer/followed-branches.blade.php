@extends('layouts.app')

@section('title', 'Stations you follow')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <header>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Stations you follow</h1>
            <p class="mt-1 text-slate-600">
                Following a station means you are told whenever one of its live prices changes — nothing else, and no marketing email.
            </p>
        </header>

        <x-card title="Following" class="mt-6">
            @if ($branches->isEmpty())
                <x-empty-state
                    title="You are not following anything yet"
                    description="Pick a station below and you will get a notification for every price change." />
            @else
                <ul class="grid gap-4 md:grid-cols-2">
                    @foreach ($branches as $branch)
                        <li class="flex items-start justify-between gap-4 rounded-lg border border-slate-200 p-4">
                            <div class="min-w-0">
                                <a href="{{ route('branches.show', $branch) }}" class="font-semibold text-slate-900 hover:text-brand-700">
                                    {{ $branch->name }}
                                </a>
                                <p class="mt-0.5 text-sm text-slate-500">{{ $branch->fullAddress() }}</p>
                                <div class="mt-2 flex items-center gap-2">
                                    <x-star-rating :rating="(float) ($branch->reviews_avg_rating ?? 0)" show-value />
                                    <span class="text-xs text-slate-500">{{ $branch->reviews_count }} reviews</span>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('branches.unfollow', $branch) }}">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="secondary">Unfollow</x-button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        @if ($suggestions->isNotEmpty())
            <x-card title="More stations to follow" class="mt-6">
                <ul class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($suggestions as $branch)
                        <li class="flex flex-col justify-between rounded-lg border border-slate-200 p-4">
                            <div>
                                <a href="{{ route('branches.show', $branch) }}" class="font-semibold text-slate-900 hover:text-brand-700">
                                    {{ $branch->name }}
                                </a>
                                <p class="mt-0.5 text-sm text-slate-500">{{ $branch->fullAddress() }}</p>
                            </div>

                            <form method="POST" action="{{ route('branches.follow', $branch) }}" class="mt-4">
                                @csrf
                                <x-button type="submit" variant="secondary" class="w-full">Follow</x-button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        @endif
    </div>
@endsection
