@extends('layouts.admin')

@section('title', 'Sales analytics')
@section('subtitle', ($managedBranch?->name ?? 'Whole network').' · '.$period['from']->format('d M Y').' to '.$period['to']->format('d M Y'))

@php
    $currency = config('app.currency');
    $money = fn (float $amount): string => number_format($amount, 2);
@endphp

@section('content')
    <x-card padding="p-0">
        <form method="GET" action="{{ route('admin.sales.dashboard') }}" class="grid gap-3 border-b border-slate-200 px-6 py-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-form-field name="from" label="From">
                <x-input name="from" type="date" :value="request('from', $period['from']->toDateString())" />
            </x-form-field>

            <x-form-field name="to" label="To">
                <x-input name="to" type="date" :value="request('to', $period['to']->toDateString())" />
            </x-form-field>

            @if ($branches->isNotEmpty())
                <x-form-field name="branch_id" label="Branch">
                    <x-select name="branch_id">
                        <option value="">Whole network</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) $selectedBranchId === $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </x-select>
                </x-form-field>
            @endif

            <div class="flex items-end gap-2">
                <x-button type="submit">Apply</x-button>
                <x-button :href="route('admin.sales.dashboard')" variant="ghost">Reset</x-button>
            </div>
        </form>

        <div class="grid gap-4 px-6 py-5 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat label="Revenue" :value="$money((float) $summary['total_revenue'])" :hint="$currency" tone="brand" icon="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33" />
            <x-stat label="Quantity" :value="number_format((float) $summary['total_quantity'], 2)" tone="sky" icon="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75Z" />
            <x-stat label="Transactions" :value="number_format((int) $summary['transaction_count'])" tone="violet" icon="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            <x-stat label="Average ticket" :value="$money((float) $summary['average_ticket'])" tone="emerald" icon="M4.5 12.75l6 6 9-13.5" />
        </div>
    </x-card>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2" title="Revenue over the period" subtitle="Daily takings.">
            @if ($daily->isEmpty())
                <x-empty-state title="No sales in this period" description="Widen the dates to see a trend." />
            @else
                <div class="h-72">
                    <canvas
                        data-chart="line"
                        data-series="{{ json_encode([
                            'labels' => $daily->pluck('label')->all(),
                            'values' => $daily->map(fn (array $p) => round((float) $p['revenue'], 2))->all(),
                            'label' => 'Revenue',
                        ]) }}"></canvas>
                </div>
            @endif
        </x-card>

        <x-card title="Payment methods" subtitle="Revenue by tender type.">
            @if ($payments->isEmpty())
                <x-empty-state title="Nothing to split" description="No sales in this period." />
            @else
                <div class="h-72">
                    <canvas
                        data-chart="doughnut"
                        data-series="{{ json_encode([
                            'labels' => $payments->pluck('label')->all(),
                            'values' => $payments->map(fn (array $p) => round((float) $p['revenue'], 2))->all(),
                        ]) }}"></canvas>
                </div>
            @endif
        </x-card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-card title="Volume, last 30 days" subtitle="Daily quantity sold.">
            @if ($weekly->isEmpty())
                <x-empty-state title="No volume" description="No sales recorded in the last month." />
            @else
                <div class="h-64">
                    <canvas
                        data-chart="bar"
                        data-series="{{ json_encode([
                            'labels' => $weekly->pluck('label')->all(),
                            'values' => $weekly->map(fn (array $p) => round((float) $p['quantity'], 2))->all(),
                            'label' => 'Quantity',
                            'unit' => 'L',
                        ]) }}"></canvas>
                </div>
            @endif
        </x-card>

        <x-card title="Revenue, last 12 months" subtitle="Calendar month totals.">
            @if ($monthly->isEmpty())
                <x-empty-state title="No history" description="Nothing recorded in the past year." />
            @else
                <div class="h-64">
                    <canvas
                        data-chart="bar"
                        data-series="{{ json_encode([
                            'labels' => $monthly->pluck('label')->all(),
                            'values' => $monthly->map(fn (array $p) => round((float) $p['revenue'], 2))->all(),
                            'label' => 'Revenue',
                        ]) }}"></canvas>
                </div>
            @endif
        </x-card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2" title="Branch comparison" subtitle="Revenue per station in this period." padding="p-0">
            @if ($compareBranches->isEmpty())
                <div class="p-6">
                    <x-empty-state title="No branch data" description="No sales recorded in this period." />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-6 py-3 font-medium">Station</th>
                                <th class="px-6 py-3 text-right font-medium">Revenue</th>
                                <th class="px-6 py-3 text-right font-medium">Quantity</th>
                                <th class="px-6 py-3 text-right font-medium">Sales</th>
                                <th class="px-6 py-3 font-medium">Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @php($networkTotal = (float) $compareBranches->sum('revenue'))
                            @foreach ($compareBranches as $row)
                                @php($share = $networkTotal > 0 ? round(((float) $row['revenue'] / $networkTotal) * 100, 1) : 0)
                                <tr>
                                    <td class="px-6 py-3">
                                        <a href="{{ route('admin.branches.show', $row['id']) }}" class="font-medium text-slate-800 hover:text-brand-700">
                                            {{ $row['name'] }}
                                        </a>
                                        <p class="text-xs text-slate-500">{{ $row['city'] }}</p>
                                    </td>
                                    <td class="px-6 py-3 text-right font-semibold text-slate-900">{{ $money((float) $row['revenue']) }}</td>
                                    <td class="px-6 py-3 text-right text-slate-600">{{ number_format((float) $row['quantity'], 2) }}</td>
                                    <td class="px-6 py-3 text-right text-slate-600">{{ number_format((int) $row['transactions']) }}</td>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-100">
                                                <div class="h-full rounded-full bg-brand-500" style="width: {{ min(100, $share) }}%"></div>
                                            </div>
                                            <span class="text-xs text-slate-500">{{ $share }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <x-card title="Product mix" padding="p-0">
            @if ($breakdown->isEmpty())
                <div class="p-6">
                    <x-empty-state title="Nothing sold" description="No product sales in this period." />
                </div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($breakdown as $row)
                        <li class="px-6 py-3">
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="truncate text-sm font-medium text-slate-800">{{ $row['label'] }}</span>
                                <span class="shrink-0 text-sm font-semibold text-slate-900">{{ number_format((float) $row['share'], 1) }}%</span>
                            </div>
                            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-brand-500" style="width: {{ min(100, max(2, (float) $row['share'])) }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $money((float) $row['revenue']) }} {{ $currency }} · {{ number_format((float) $row['quantity'], 2) }} sold
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
@endsection
