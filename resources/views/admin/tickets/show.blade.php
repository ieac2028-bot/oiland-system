@extends('layouts.admin')

@section('title', $ticket->reference)
@section('subtitle', $ticket->subject)

@section('content')
    <nav class="text-sm text-slate-500">
        <a href="{{ route('admin.tickets.index') }}" class="hover:underline">Customer inquiries</a>
        <span class="mx-2">/</span>
        <span class="text-slate-700">{{ $ticket->reference }}</span>
    </nav>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card padding="p-0">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-badge>
                            <x-badge :tone="$ticket->priority->tone()">{{ $ticket->priority->label() }} priority</x-badge>
                            @if ($ticket->resolved_at)
                                <x-badge tone="slate">Resolved {{ $ticket->resolved_at->format('d M Y') }}</x-badge>
                            @endif
                        </div>
                        <h1 class="mt-2 text-lg font-semibold text-slate-900">{{ $ticket->subject }}</h1>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $ticket->categoryLabel() }} · opened {{ $ticket->created_at->format('d M Y \a\t H:i') }}
                        </p>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Original message</p>
                    <p class="mt-1.5 whitespace-pre-line text-sm text-slate-700">{{ $ticket->message }}</p>
                </div>
            </x-card>

            <x-card title="Conversation" :subtitle="$ticket->replies->count().' '.Str::plural('reply', $ticket->replies->count())">
                @if ($ticket->replies->isEmpty())
                    <p class="text-sm text-slate-500">No replies yet. The customer is waiting on a first response.</p>
                @endif

                <div class="space-y-4">
                    @foreach ($ticket->replies->sortBy('created_at') as $reply)
                        <div @class([
                            'rounded-xl border p-4',
                            'border-amber-200 bg-amber-50' => $reply->is_internal_note,
                            'border-slate-200 bg-white' => ! $reply->is_internal_note,
                        ])>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold text-slate-800">{{ $reply->author?->name ?? 'Former team member' }}</span>
                                <x-badge :tone="$reply->is_internal_note ? 'amber' : ($reply->user_id === auth()->id() ? 'brand' : 'slate')">
                                    {{ $reply->is_internal_note ? 'Internal note' : ($reply->user_id === auth()->id() ? 'You' : ($reply->author?->role?->label() ?? 'Branch staff')) }}
                                </x-badge>
                                <span class="ml-auto text-xs text-slate-400">{{ $reply->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $reply->message }}</p>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <x-card title="Reply to the customer">
                <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}" class="space-y-4">
                    @csrf

                    <x-form-field name="message" label="Message" required hint="Internal notes stay inside the staff console and are never shown to the customer.">
                        <x-textarea id="message" name="message" rows="4" required maxlength="5000">{{ old('message') }}</x-textarea>
                    </x-form-field>

                    <div class="flex flex-wrap items-center gap-3">
                        <x-button type="submit">Send reply</x-button>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="is_internal_note" value="1" @checked(old('is_internal_note'))
                                   class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                            <span class="text-sm text-slate-600">Internal note only</span>
                        </label>
                    </div>
                </form>
            </x-card>
        </div>

        <aside class="space-y-6">
            <x-card title="Customer">
                <p class="text-sm font-semibold text-slate-800">{{ $ticket->customer?->name ?? 'Deleted customer' }}</p>
                @if ($ticket->customer?->email)
                    <p class="mt-0.5 text-xs text-slate-500">{{ $ticket->customer->email }}</p>
                @endif
                @if ($ticket->customer?->phone)
                    <p class="mt-0.5 text-xs text-slate-500">{{ $ticket->customer->phone }}</p>
                @endif

                <div class="mt-4 border-t border-slate-200 pt-4">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Inquiries from this customer</p>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $ticket->customer?->tickets()->count() ?? 0 }} in total,
                        {{ $ticket->customer?->tickets()->where('status', '!=', 'resolved')->count() ?? 0 }} still open.
                    </p>
                </div>
            </x-card>

            <x-card title="Station">
                @if ($ticket->branch)
                    <p class="text-sm font-semibold text-slate-800">{{ $ticket->branch->name }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $ticket->branch->address_line1 }}, {{ $ticket->branch->city }}</p>
                    <x-button :href="route('admin.branches.show', $ticket->branch)" variant="secondary" class="mt-4 w-full">
                        Open branch
                    </x-button>
                @else
                    <p class="text-sm text-slate-500">The station was removed.</p>
                @endif
            </x-card>

            <x-card title="Manage" subtitle="Changes apply immediately.">
                <div class="space-y-4">
                    <form method="POST" action="{{ route('admin.tickets.status', $ticket) }}">
                        @csrf
                        @method('PATCH')

                        <x-form-field name="status" label="Status">
                            <x-select name="status">
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected($ticket->status->value === $value)>{{ $label }}</option>
                                @endforeach
                            </x-select>
                        </x-form-field>

                        <x-button type="submit" variant="secondary" class="w-full">Update status</x-button>
                    </form>

                    <form method="POST" action="{{ route('admin.tickets.priority', $ticket) }}" class="border-t border-slate-200 pt-4">
                        @csrf
                        @method('PATCH')

                        <x-form-field name="priority" label="Priority">
                            <x-select name="priority">
                                @foreach ($priorities as $value => $label)
                                    <option value="{{ $value }}" @selected($ticket->priority->value === $value)>{{ $label }}</option>
                                @endforeach
                            </x-select>
                        </x-form-field>

                        <x-button type="submit" variant="secondary" class="w-full">Update priority</x-button>
                    </form>

                    <form method="POST" action="{{ route('admin.tickets.assign', $ticket) }}" class="border-t border-slate-200 pt-4">
                        @csrf
                        @method('PATCH')

                        <x-form-field name="assigned_to" label="Assigned to">
                            <x-select name="assigned_to">
                                <option value="">Unassigned</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id }}" @selected($ticket->assigned_to === $agent->id)>{{ $agent->name }}</option>
                                @endforeach
                            </x-select>
                        </x-form-field>

                        <x-button type="submit" variant="secondary" class="w-full">Assign</x-button>
                    </form>
                </div>
            </x-card>
        </aside>
    </div>
@endsection
