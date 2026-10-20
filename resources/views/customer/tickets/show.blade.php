@extends('layouts.app')

@section('title', $ticket->reference)

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <nav class="text-sm text-slate-500">
            <a href="{{ route('my.tickets.index') }}" class="hover:underline">My inquiries</a>
            <span class="mx-2">/</span>
            <span class="text-slate-700">{{ $ticket->reference }}</span>
        </nav>

        <header class="mt-4 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-badge>
                    <x-badge :tone="$ticket->priority->tone()">{{ $ticket->priority->label() }}</x-badge>
                </div>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $ticket->subject }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $ticket->branch?->name }} · opened {{ $ticket->created_at->format('d M Y \a\t H:i') }}
                </p>
            </div>

            @if ($ticket->assignee)
                <div class="text-right text-sm text-slate-600">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Handled by</p>
                    <p class="font-medium text-slate-800">{{ $ticket->assignee->name }}</p>
                </div>
            @endif
        </header>

        <x-card class="mt-6">
            <div class="rounded-lg bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Your message</p>
                <p class="mt-1.5 whitespace-pre-line text-sm text-slate-700">{{ $ticket->message }}</p>
            </div>
        </x-card>

        <div class="mt-6 space-y-4">
            <h2 class="text-lg font-semibold text-slate-900">Conversation</h2>

            @foreach ($replies as $reply)
                <div @class([
                    'rounded-xl border p-4',
                    'border-brand-200 bg-brand-50' => $reply->user_id === auth()->id(),
                    'border-slate-200 bg-white' => $reply->user_id !== auth()->id(),
                ])>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold text-slate-800">{{ $reply->author?->name ?? 'Former team member' }}</span>
                        <x-badge :tone="$reply->user_id === auth()->id() ? 'brand' : 'slate'">
                            {{ $reply->user_id === auth()->id() ? 'You' : ($reply->author?->role?->label() ?? 'Branch staff') }}
                        </x-badge>
                        <span class="ml-auto text-xs text-slate-400">{{ $reply->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $reply->message }}</p>
                </div>
            @endforeach

            @if ($replies->isEmpty())
                <p class="text-sm text-slate-500">No replies yet. The branch has been notified and usually answers within one working day.</p>
            @endif
        </div>

        @unless ($ticket->isResolved())
            <x-card title="Add a reply" class="mt-6">
                <form method="POST" action="{{ route('my.tickets.replies.store', $ticket) }}" class="space-y-4">
                    @csrf

                    <x-form-field name="message" label="Your reply" required>
                        <x-textarea id="message" name="message" rows="4" required maxlength="5000">{{ old('message') }}</x-textarea>
                    </x-form-field>

                    <x-button type="submit">Send reply</x-button>
                </form>
            </x-card>
        @else
            <p class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                This inquiry was marked resolved
                @if ($ticket->resolved_at)
                    on {{ $ticket->resolved_at->format('d M Y') }}
                @endif
                . Open a new inquiry if the problem comes back.
            </p>
        @endunless
    </div>
@endsection
