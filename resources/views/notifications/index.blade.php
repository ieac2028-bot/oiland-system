@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Notifications</h1>
                <p class="mt-1 text-slate-600">Replies to your inquiries, price changes at stations you follow and moderation decisions.</p>
            </div>

            @if (auth()->user()->unreadNotifications()->count() > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <x-button type="submit" variant="secondary">Mark all as read</x-button>
                </form>
            @endif
        </header>

        <div class="mt-6 space-y-2">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $url = $data['url'] ?? route('notifications.index');
                @endphp

                <a href="{{ route('notifications.read', $notification->id) }}"
                   class="flex items-start gap-4 rounded-xl border p-4 transition hover:bg-slate-50 {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-brand-200 bg-brand-50/60' }}">
                    <span @class([
                        'mt-1 h-2.5 w-2.5 shrink-0 rounded-full',
                        'bg-brand-600' => ! $notification->read_at,
                        'bg-slate-200' => $notification->read_at,
                    ])></span>

                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-slate-900">
                            {{ $data['heading'] ?? Str::headline(class_basename($notification->type)) }}
                        </span>

                        @foreach ($data['lines'] ?? [] as $line)
                            <span class="mt-1 block text-sm text-slate-600">{{ $line }}</span>
                        @endforeach

                        <span class="mt-1.5 block text-xs text-slate-400">
                            {{ $notification->created_at->diffForHumans() }}
                            @if ($notification->read_at)
                                · read
                            @endif
                        </span>
                    </span>
                </a>
            @empty
                <x-empty-state
                    title="Nothing here yet"
                    description="Follow a station or open an inquiry and the updates will show up here.">
                    <x-slot name="action">
                        <x-button :href="route('branches.index')">Find a station</x-button>
                    </x-slot>
                </x-empty-state>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="mt-6">{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
