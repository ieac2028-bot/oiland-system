@extends('layouts.admin')

@section('title', 'Price listing')
@section('subtitle', 'Currently effective prices across the network.')

@section('content')
    <x-card padding="p-0">
        <x-slot name="actions">
            <x-button :href="route('admin.prices.create')">Publish a price</x-button>
        </x-slot>

        <form method="GET" action="{{ route('admin.prices.index') }}" class="grid gap-3 border-b border-slate-200 px-6 py-4 sm:grid-cols-2 lg:grid-cols-3">
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

            <x-form-field name="catalog_item_id" label="Item">
                <x-select name="catalog_item_id">
                    <option value="">All items</option>
                    @foreach ($catalogItems as $item)
                        <option value="{{ $item->id }}" @selected((int) ($filters['catalog_item_id'] ?? 0) === $item->id)>
                            {{ $item->displayName() }}
                        </option>
                    @endforeach
                </x-select>
            </x-form-field>

            <div class="flex items-end gap-2">
                <x-button type="submit">Filter</x-button>
                @if (array_filter($filters))
                    <x-button :href="route('admin.prices.index')" variant="ghost">Reset</x-button>
                @endif
            </div>
        </form>

        @if ($prices->isEmpty())
            <div class="p-6">
                <x-empty-state
                    title="No live prices"
                    description="Once a branch prices an item, the current figure appears in this list.">
                    <x-slot name="action">
                        <x-button :href="route('admin.prices.create')">Publish the first price</x-button>
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
                            <th class="px-6 py-3 text-right font-medium">Price</th>
                            <th class="px-6 py-3 font-medium">Effective</th>
                            <th class="px-6 py-3 font-medium">Approved by</th>
                            <th class="px-6 py-3 text-right font-medium">History</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($prices as $price)
                            <tr>
                                <td class="px-6 py-3">
                                    <a href="{{ route('admin.branches.show', $price->branch) }}" class="font-medium text-slate-800 hover:text-brand-700">
                                        {{ $price->branch?->name }}
                                    </a>
                                    <p class="text-xs text-slate-500">{{ $price->branch?->city }}</p>
                                </td>
                                <td class="px-6 py-3 text-slate-600">{{ $price->catalogItem?->displayName() }}</td>
                                <td class="px-6 py-3 text-right">
                                    <span class="font-semibold text-slate-900">{{ number_format((float) $price->price, 2) }}</span>
                                    <span class="text-xs text-slate-500">{{ $price->currency }}</span>
                                </td>
                                <td class="px-6 py-3 text-slate-600">
                                    {{ $price->effective_from?->format('d M Y') }}
                                    @if ($price->effective_to)
                                        <span class="block text-xs text-slate-400">to {{ $price->effective_to->format('d M Y') }}</span>
                                    @else
                                        <x-badge tone="emerald" class="mt-0.5">Live</x-badge>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-xs text-slate-500">
                                    {{ $price->approver?->name ?? '—' }}
                                    @if ($price->approved_at)
                                        <span class="block text-slate-400">{{ $price->approved_at->format('d M Y') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <x-button :href="route('admin.prices.show', $price)" variant="ghost">View trend</x-button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($prices->hasPages())
                <div class="border-t border-slate-200 px-6 py-3">
                    {{ $prices->links() }}
                </div>
            @endif
        @endif
    </x-card>

    @unless ($canPublish)
        <p class="mt-4 text-xs text-slate-500">
            Officers in Charge can review live prices here. Only head office can publish a price directly &mdash;
            branches submit a price change request for approval instead.
        </p>
    @endunless
@endsection
