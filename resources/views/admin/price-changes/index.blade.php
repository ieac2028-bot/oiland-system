@extends('layouts.admin')

@section('title', 'Price change approvals')
@section('subtitle', $pendingCount.' request(s) waiting on a decision')

@section('content')
    <x-card padding="p-0">
        <x-slot name="actions">
            <x-button :href="route('admin.price-changes.create')">Request a change</x-button>
        </x-slot>

        <form method="GET" action="{{ route('admin.price-changes.index') }}" class="grid gap-3 border-b border-slate-200 px-6 py-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-form-field name="status" label="Status">
                <x-select name="status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-form-field>

            @if ($branches->isNotEmpty())
                <x-form-field name="branch_id" label="Branch">
                    <x-select name="branch_id">
                        <option value="">All branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) ($filters['branch_id'] ?? 0) === $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </x-select>
                </x-form-field>
            @endif

            <div class="flex items-end gap-2">
                <x-button type="submit">Filter</x-button>
                @if (array_filter($filters))
                    <x-button :href="route('admin.price-changes.index')" variant="ghost">Reset</x-button>
                @endif
            </div>
        </form>

        @if ($requests->isEmpty())
            <div class="p-6">
                <x-empty-state
                    title="Nothing in the queue"
                    description="Branches submit price change requests here, and head office approves or rejects them.">
                    <x-slot name="action">
                        <x-button :href="route('admin.price-changes.create')" variant="secondary">Request a change</x-button>
                    </x-slot>
                </x-empty-state>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-6 py-3 font-medium">Branch</th>
                            <th class="px-6 py-3 font-medium">Item</th>
                            <th class="px-6 py-3 text-right font-medium">From</th>
                            <th class="px-6 py-3 text-right font-medium">To</th>
                            <th class="px-6 py-3 font-medium">Requested</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($requests as $change)
                            <tr>
                                <td class="px-6 py-3 font-medium text-slate-800">{{ $change->branch?->name }}</td>
                                <td class="px-6 py-3 text-slate-600">{{ $change->catalogItem?->displayName() }}</td>
                                <td class="px-6 py-3 text-right text-slate-500">
                                    {{ $change->old_price !== null ? number_format((float) $change->old_price, 2) : '—' }}
                                </td>
                                <td class="px-6 py-3 text-right font-semibold text-slate-900">
                                    {{ number_format((float) $change->new_price, 2) }}
                                </td>
                                <td class="px-6 py-3 text-xs text-slate-500">
                                    {{ $change->requester?->name ?? 'Unknown' }}
                                    <span class="block text-slate-400">{{ $change->created_at?->diffForHumans() }}</span>
                                </td>
                                <td class="px-6 py-3">
                                    <x-badge :tone="$change->status->tone()">{{ $change->status->label() }}</x-badge>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-button :href="route('admin.price-changes.show', $change)" variant="ghost">Review</x-button>

                                        @if ($canReview && $change->isPending())
                                            <form method="POST" action="{{ route('admin.price-changes.approve', $change) }}">
                                                @csrf
                                                <x-button type="submit" variant="success">Approve</x-button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($requests->hasPages())
                <div class="border-t border-slate-200 px-6 py-3">
                    {{ $requests->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection
