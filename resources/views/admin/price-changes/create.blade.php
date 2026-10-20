@extends('layouts.admin')

@section('title', 'Request a price change')
@section('subtitle', 'Head office reviews every request before a new price goes live.')

@section('content')
    @if ($existingRequest)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">
            <p class="text-sm font-semibold text-amber-900">This branch already has a request in the queue</p>
            <p class="mt-1 text-sm text-amber-800">
                A request for {{ $existingRequest->catalogItem?->displayName() }} to
                {{ number_format((float) $existingRequest->new_price, 2) }} {{ config('app.currency') }}
                is still pending. Submitting again will update that same request rather than create a second one.
            </p>
            <x-button :href="route('admin.price-changes.show', $existingRequest)" variant="secondary" class="mt-3">
                Review the open request
            </x-button>
        </div>
    @endif

    <x-card>
        <form method="POST" action="{{ route('admin.price-changes.store') }}" class="grid gap-5 sm:grid-cols-2">
            @csrf

            <x-form-field name="branch_id" label="Branch" required>
                <x-select name="branch_id" required>
                    <option value="">Choose a branch</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((int) old('branch_id', $selectedBranchId) === $branch->id)>
                            {{ $branch->name }} &mdash; {{ $branch->city }}
                        </option>
                    @endforeach
                </x-select>
            </x-form-field>

            <x-form-field name="catalog_item_id" label="Item" required hint="Only items this branch offers are listed.">
                <x-select name="catalog_item_id" required>
                    <option value="">Choose an item</option>
                    @foreach ($availableItems as $item)
                        <option value="{{ $item->id }}" @selected((int) old('catalog_item_id') === $item->id)>
                            {{ $item->displayName() }}
                        </option>
                    @endforeach
                </x-select>
            </x-form-field>

            <div class="sm:col-span-2">
                <x-form-field name="current_price_display" label="Current live price">
                    <x-input
                        name="current_price_display"
                        :value="$currentPrice['price'] !== null ? number_format((float) $currentPrice['price'], 2).' '.config('app.currency') : 'No live price for this item yet'"
                        disabled />
                    <p class="mt-1.5 text-xs text-slate-500">
                        The current price is read from the live record, not from the form, so an approval can never rest on a tampered figure.
                    </p>
                </x-form-field>
            </div>

            <x-form-field name="new_price" label="Requested price" required hint="Amount in {{ config('app.currency') }}.">
                <x-input name="new_price" type="number" step="0.01" min="0.01" max="99999" :value="old('new_price')" required />
            </x-form-field>

            <div class="sm:col-span-2">
                <x-form-field name="note" label="Reason" hint="Tell the reviewer why this price is needed.">
                    <x-textarea name="note" rows="4" maxlength="1000">{{ old('note') }}</x-textarea>
                </x-form-field>
            </div>

            <div class="sm:col-span-2 flex items-center gap-2 border-t border-slate-200 pt-5">
                <x-button type="submit">Submit for approval</x-button>
                <x-button :href="route('admin.price-changes.index')" variant="secondary">Cancel</x-button>
            </div>
        </form>
    </x-card>
@endsection
