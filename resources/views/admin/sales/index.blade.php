@extends('layouts.admin')

@section('title', 'Sales ledger')
@section('subtitle', $period['from']->format('d M Y').' to '.$period['to']->format('d M Y'))

@php
    $currency = config('app.currency');
    $money = fn (float $amount): string => number_format($amount, 2);
@endphp

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Revenue" :value="$money((float) $summary['total_revenue'])" :hint="$currency" tone="brand" icon="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33" />
        <x-stat label="Quantity" :value="number_format((float) $summary['total_quantity'], 2)" hint="Across every item" tone="sky" icon="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75Z" />
        <x-stat label="Transactions" :value="number_format((int) $summary['transaction_count'])" hint="Recorded sale lines" tone="violet" icon="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        <x-stat label="Average ticket" :value="$money((float) $summary['average_ticket'])" :hint="'Average unit '.$money((float) $summary['average_price'])" tone="emerald" icon="M4.5 12.75l6 6 9-13.5" />
    </div>

    <x-card class="mt-6" padding="p-0">
        <x-slot name="actions">
            @can('import', App\Models\Sale::class)
                <x-button :href="route('admin.sales.create')" variant="secondary">Record a day</x-button>
                <x-button :href="route('admin.sales.import')">CSV import</x-button>
            @endcan
        </x-slot>

        <form method="GET" action="{{ route('admin.sales.index') }}" class="grid gap-3 border-b border-slate-200 px-6 py-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-form-field name="from" label="From">
                <x-input name="from" type="date" :value="$filters['from'] ?? $period['from']->toDateString()" />
            </x-form-field>

            <x-form-field name="to" label="To">
                <x-input name="to" type="date" :value="$filters['to'] ?? $period['to']->toDateString()" />
            </x-form-field>

            <x-form-field name="item" label="Item">
                <x-input name="item" :value="$filters['item'] ?? null" placeholder="Item name" />
            </x-form-field>

            <x-form-field name="branch_id" label="Branch">
                <x-select name="branch_id">
                    <option value="">All in scope</option>
                    @foreach (\App\Models\Branch::orderBy('name')->get() as $branch)
                        <option value="{{ $branch->id }}" @selected((int) ($filters['branch_id'] ?? 0) === $branch->id)>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </x-select>
            </x-form-field>

            <div class="flex items-end gap-2">
                <x-button type="submit">Filter</x-button>
                <x-button :href="route('admin.sales.index')" variant="ghost">Reset</x-button>
            </div>
        </form>

        @if ($sales->isEmpty())
            <div class="p-6">
                <x-empty-state
                    title="No sales in this period"
                    description="Record a day at the pump or import a CSV to start building the ledger.">
                    <x-slot name="action">
                        <x-button :href="route('admin.sales.dashboard')" variant="secondary">Open analytics</x-button>
                    </x-slot>
                </x-empty-state>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-6 py-3 font-medium">Date</th>
                            <th class="px-6 py-3 font-medium">Branch</th>
                            <th class="px-6 py-3 font-medium">Item</th>
                            <th class="px-6 py-3 text-right font-medium">Quantity</th>
                            <th class="px-6 py-3 text-right font-medium">Unit price</th>
                            <th class="px-6 py-3 text-right font-medium">Amount</th>
                            <th class="px-6 py-3 font-medium">Tender</th>
                            <th class="px-6 py-3 font-medium">Source</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($sales as $sale)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-3 text-slate-600">{{ $sale->sale_date?->format('d M Y') }}</td>
                                <td class="px-6 py-3 font-medium text-slate-800">{{ $sale->branch?->name }}</td>
                                <td class="px-6 py-3 text-slate-600">{{ $sale->item_name }}</td>
                                <td class="px-6 py-3 text-right text-slate-600">{{ number_format((float) $sale->quantity, 3) }}</td>
                                <td class="px-6 py-3 text-right text-slate-600">{{ $money((float) $sale->unit_price) }}</td>
                                <td class="px-6 py-3 text-right font-semibold text-slate-900">{{ $money((float) $sale->total_amount) }}</td>
                                <td class="px-6 py-3">
                                    <x-badge :tone="$sale->payment_method->tone()">{{ $sale->payment_method->label() }}</x-badge>
                                </td>
                                <td class="px-6 py-3 text-xs text-slate-500">{{ $sale->source?->label() ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($sales->hasPages())
                <div class="border-t border-slate-200 px-6 py-3">
                    {{ $sales->links() }}
                </div>
            @endif
        @endif
    </x-card>

    @if ($datesWithSales->isNotEmpty())
        <x-card class="mt-6" title="Jump to a day" subtitle="Days with recorded sales in this period.">
            <div class="flex flex-wrap gap-2">
                @foreach ($datesWithSales as $date)
                    @php($day = \Illuminate\Support\Carbon::parse($date))
                    <a href="{{ route('admin.sales.index', ['from' => $day->toDateString(), 'to' => $day->toDateString()]) }}"
                       @class([
                           'rounded-lg px-3 py-1.5 text-sm font-medium ring-1 ring-inset transition',
                           'bg-brand-50 text-brand-700 ring-brand-200' => $day->isToday(),
                           'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50' => ! $day->isToday(),
                       ])>
                        {{ $day->format('D d M') }}
                    </a>
                @endforeach
            </div>
        </x-card>
    @endif
@endsection
