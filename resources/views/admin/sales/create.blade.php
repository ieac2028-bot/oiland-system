@extends('layouts.admin')

@section('title', 'Record a day of sales')
@section('subtitle', 'Saving again for the same date replaces that day wholesale.')

@php
    $existingByItem = $existing->keyBy('catalog_item_id');
@endphp

@section('content')
    <form method="POST" action="{{ route('admin.sales.store') }}" data-sales-entry>
        @csrf

        <x-card padding="p-0">
            <x-slot name="actions">
                <x-button :href="route('admin.sales.index')" variant="secondary">Cancel</x-button>
            </x-slot>

            <div class="grid gap-5 border-b border-slate-200 px-6 py-5 sm:grid-cols-3">
                <x-form-field name="date" label="Sales date" required hint="Cannot be in the future.">
                    <x-input name="date" type="date" :value="old('date', $date->toDateString())" max="{{ today()->toDateString() }}" required />
                </x-form-field>

                <div class="sm:col-span-2 flex items-end">
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="replace_existing" value="1" @checked(old('replace_existing'))
                               class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <span>
                            <span class="block text-sm font-medium text-slate-700">Replace this date</span>
                            <span class="mt-0.5 block text-xs text-slate-500">
                                Tick to overwrite everything already recorded for the chosen date. Leave unticked to add to it.
                            </span>
                        </span>
                    </label>
                </div>
            </div>

            @error('rows')
                <div class="px-6 pt-4">
                    <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
                </div>
            @enderror

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-6 py-3 font-medium">Item</th>
                            <th class="px-6 py-3 text-right font-medium">Quantity</th>
                            <th class="px-6 py-3 text-right font-medium">Unit price</th>
                            <th class="px-6 py-3 text-right font-medium">Amount</th>
                            <th class="px-6 py-3 font-medium">Payment method</th>
                            <th class="px-6 py-3 font-medium">Notes</th>
                            <th class="px-6 py-3"><span class="sr-only">Remove</span></th>
                        </tr>
                    </thead>

                    <tbody id="sales-rows" class="divide-y divide-slate-100">
                        @php
                            $rows = old('rows', $catalogItems->map(fn ($item) => [
                                'catalog_item_id' => $item->id,
                                'quantity' => $existingByItem->get($item->id)?->quantity,
                                'unit_price' => $existingByItem->get($item->id)?->unit_price,
                                'payment_method' => $existingByItem->get($item->id)?->payment_method?->value ?? 'cash',
                                'notes' => null,
                            ])->filter(fn (array $row) => filled($row['quantity']) || filled($row['unit_price']))->values()->all());
                        @endphp

                        @for ($index = 0; $index < max(3, count($rows)); $index++)
                            @php($row = $rows[$index] ?? ['catalog_item_id' => null, 'quantity' => null, 'unit_price' => null, 'payment_method' => 'cash', 'notes' => null])
                            <tr data-row>
                                <td class="px-6 py-3">
                                    <select name="rows[{{ $index }}][catalog_item_id]"
                                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                            data-amount>
                                        <option value="">Choose an item</option>
                                        @foreach ($catalogItems as $item)
                                            <option value="{{ $item->id }}" @selected((int) $row['catalog_item_id'] === $item->id)>
                                                {{ $item->displayName() }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("rows.$index.catalog_item_id")
                                        <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <input type="number" step="0.001" min="0.001" name="rows[{{ $index }}][quantity]"
                                           value="{{ $row['quantity'] }}"
                                           class="block w-full rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                           data-amount>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <input type="number" step="0.01" min="0.01" name="rows[{{ $index }}][unit_price]"
                                           value="{{ $row['unit_price'] }}"
                                           class="block w-full rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                           data-amount>
                                </td>
                                <td class="px-6 py-3 text-right font-semibold text-slate-900" data-line-total>—</td>
                                <td class="px-6 py-3">
                                    <select name="rows[{{ $index }}][payment_method]"
                                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                        @foreach ($paymentMethods as $value => $label)
                                            <option value="{{ $value }}" @selected($row['payment_method'] === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-6 py-3">
                                    <input type="text" maxlength="255" name="rows[{{ $index }}][notes]" value="{{ $row['notes'] }}"
                                           class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                           placeholder="Optional">
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <button type="button" data-remove-row
                                            class="rounded-lg px-2 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-6 py-4">
                <x-button type="button" variant="secondary" data-add-row>Add another line</x-button>

                <p class="text-sm text-slate-600">
                    Day total
                    <span class="ml-1 text-lg font-semibold text-slate-900" data-day-total>0.00</span>
                    <span class="text-xs text-slate-500">{{ config('app.currency') }}</span>
                </p>
            </div>
        </x-card>

        <div class="mt-6 flex items-center gap-2">
            <x-button type="submit">Save the day</x-button>
            <x-button :href="route('admin.sales.index')" variant="secondary">Cancel</x-button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        // Vanilla row management: add, remove, and live line/day totals.
        (() => {
            const form = document.querySelector('[data-sales-entry]');
            if (!form) return;

            const body = form.querySelector('#sales-rows');
            const template = body.querySelector('[data-row]').outerHTML;
            const money = (value) =>
                new Intl.NumberFormat('en-AE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);

            const recalculate = () => {
                let day = 0;
                body.querySelectorAll('[data-row]').forEach((row) => {
                    const quantity = parseFloat(row.querySelector('input[name*="quantity"]')?.value || '0') || 0;
                    const price = parseFloat(row.querySelector('input[name*="unit_price"]')?.value || '0') || 0;
                    const total = Math.round(quantity * price * 100) / 100;
                    day += total;
                    row.querySelector('[data-line-total]').textContent = quantity && price ? money(total) : '—';
                });
                form.querySelector('[data-day-total]').textContent = money(Math.round(day * 100) / 100);
            };

            body.addEventListener('input', recalculate);
            body.addEventListener('change', recalculate);

            form.querySelector('[data-add-row]').addEventListener('click', () => {
                body.insertAdjacentHTML('beforeend', template);
                recalculate();
            });

            body.addEventListener('click', (event) => {
                if (!event.target.closest('[data-remove-row]')) return;
                const rows = body.querySelectorAll('[data-row]');
                if (rows.length <= 1) return;
                event.target.closest('[data-row]').remove();
                recalculate();
            });

            recalculate();
        })();
    </script>
@endpush
