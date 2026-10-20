@extends('layouts.app')

@section('title', 'Careers at OiLand')
@section('description', 'Current job openings at OiLand fuel stations.')

@section('content')
    <section class="bg-slate-50 py-12">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-sm font-semibold text-brand-700">OiLand careers</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Current job openings</h1>
                <p class="mt-2 text-slate-600">Explore opportunities posted by OiLand head office.</p>
            </div>

            <div class="space-y-4">
                @forelse ($openings as $opening)
                    <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <h2 class="text-lg font-semibold text-slate-900">{{ $opening->title }}</h2>
                            <x-badge tone="brand">{{ $opening->scope->label() }}</x-badge>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ $opening->body }}</p>
                        @if ($opening->branches->isNotEmpty())
                            <p class="mt-4 text-sm font-medium text-slate-700">Station(s): {{ $opening->branches->map(fn ($branch) => $branch->name.' — '.$branch->city)->join(', ') }}</p>
                        @endif
                        <p class="mt-4 text-xs text-slate-400">Posted {{ $opening->publish_at?->format('d M Y') ?? $opening->created_at->format('d M Y') }}@if ($opening->expires_at) · Apply by {{ $opening->expires_at->format('d M Y') }}@endif</p>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                        <h2 class="font-semibold text-slate-800">No open positions right now</h2>
                        <p class="mt-1 text-sm text-slate-500">Please check back for future openings.</p>
                    </div>
                @endforelse
            </div>

            @if ($openings->hasPages())
                <div class="mt-6">{{ $openings->links() }}</div>
            @endif
        </div>
    </section>
@endsection
