@extends('layouts.admin')

@section('title', 'Publish a price')
@section('subtitle', 'Head office can set a live price directly. Branches submit a change request instead.')

@section('content')
    <x-card>
        <form method="POST" action="{{ route('admin.prices.store') }}" class="grid gap-5 sm:grid-cols-2">
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

            <x-form-field name="catalog_item_id" label="Item" required>
                <x-select name="catalog_item_id" required>
                    <option value="">Choose an item</option>
                    @foreach ($catalogItems as $item)
                        <option value="{{ $item->id }}" @selected((int) old('catalog_item_id', $selectedItemId) === $item->id)>
                            {{ $item->displayName() }}
                        </option>
                    @endforeach
                </x-select>
            </x-form-field>

            <x-form-field name="price" label="Price" required hint="Amount in {{ config('app.currency') }}.">
                <x-input
                    name="price"
                    type="number"
                    step="0.01"
                    min="0.01"
                    :value="old('price')"
                    required />
            </x-form-field>

            <x-form-field name="note" label="Note" hint="Recorded in the price history for audit.">
                <x-input name="note" :value="old('note')" maxlength="500" />
            </x-form-field>

            <div class="sm:col-span-2 flex items-center gap-2 border-t border-slate-200 pt-5">
                <x-button type="submit">Publish price</x-button>
                <x-button :href="route('admin.prices.index')" variant="secondary">Cancel</x-button>
                <p class="ml-auto text-xs text-slate-500">
                    Any existing live price for this item is retired automatically.
                </p>
            </div>
        </form>
    </x-card>
@endsection
