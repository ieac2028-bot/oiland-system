@extends('layouts.admin')

@section('title', 'Dashboard')
@section('subtitle', $managedBranch?->name ?? 'Network-wide performance')

@php
    $currency = config('app.currency');
    $money = fn (float $amount): string => number_format($amount, 2);
@endphp

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat
            label="Today"
            :value="$money($todayRevenue)"
            :hint="$todaySalesCount.' sale line(s) recorded'"
            tone="emerald"
            icon="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33" />
        <x-stat
            label="Month to date"
            :value="$money($monthToDateRevenue)"
            hint="Since the first of this month"
            tone="brand"
            icon="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
        <x-stat
            label="Awaiting approval"
            :value="$pendingPriceChanges"
            :hint="'Price change requests in the queue'"
            tone="amber"
            icon="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        <x-stat
            label="Needs attention"
            :value="$openTickets + $flaggedReviews"
            :hint="$openTickets.' open inquiry/tickets · '.$flaggedReviews.' flagged review(s)'"
            tone="rose"
            icon="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-card
            class="lg:col-span-2"
            title="Revenue, last 30 days"
            subtitle="Daily takings for the period shown.">
            <x-slot name="actions">
                <x-button :href="route('admin.sales.index')" variant="ghost">Sales ledger</x-button>
            </x-slot>

            <div class="h-72">
                <canvas
                    data-chart="line"
                    data-series="{{ json_encode([
                        'labels' => $dailySeries->pluck('label')->all(),
                        'values' => $dailySeries->map(fn (array $point) => round((float) $point['revenue'], 2))->all(),
                        'label' => 'Revenue',
                    ]) }}"></canvas>
            </div>
        </x-card>

        <x-card title="Payment methods" subtitle="Share of revenue by tender type.">
            @if ($paymentSplit->isEmpty())
                <x-empty-state title="No sales in range" description="Record a day of sales to see the split." />
            @else
                <div class="h-72">
                    <canvas
                        data-chart="doughnut"
                        data-series="{{ json_encode([
                            'labels' => $paymentSplit->pluck('label')->all(),
                            'values' => $paymentSplit->map(fn (array $row) => round((float) $row['revenue'], 2))->all(),
                        ]) }}"></canvas>
                </div>
            @endif
        </x-card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2" title="Volume, last 12 months" subtitle="Revenue by calendar month.">
            <div class="h-64">
                <canvas
                    data-chart="bar"
                    data-series="{{ json_encode([
                        'labels' => $monthlySeries->pluck('label')->all(),
                        'values' => $monthlySeries->map(fn (array $point) => round((float) $point['revenue'], 2))->all(),
                        'label' => 'Revenue',
                    ]) }}"></canvas>
            </div>
        </x-card>

        <x-card title="Period summary" padding="p-0">
            <x-slot name="actions">
                <x-button :href="route('admin.sales.dashboard')" variant="ghost">Analytics</x-button>
            </x-slot>

            <dl class="divide-y divide-slate-100 text-sm">
                <div class="flex items-center justify-between gap-3 px-6 py-3">
                    <dt class="text-slate-500">Revenue</dt>
                    <dd class="font-semibold text-slate-900">{{ $money((float) $summary['total_revenue']) }} <span class="text-xs font-normal text-slate-500">{{ $currency }}</span></dd>
                </div>
                <div class="flex items-center justify-between gap-3 px-6 py-3">
                    <dt class="text-slate-500">Quantity</dt>
                    <dd class="font-semibold text-slate-900">{{ number_format((float) $summary['total_quantity'], 2) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 px-6 py-3">
                    <dt class="text-slate-500">Transactions</dt>
                    <dd class="font-semibold text-slate-900">{{ number_format((int) $summary['transaction_count']) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 px-6 py-3">
                    <dt class="text-slate-500">Average ticket</dt>
                    <dd class="font-semibold text-slate-900">{{ $money((float) $summary['average_ticket']) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 px-6 py-3">
                    <dt class="text-slate-500">Average unit price</dt>
                    <dd class="font-semibold text-slate-900">{{ $money((float) $summary['average_price']) }}</dd>
                </div>
            </dl>
        </x-card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-card title="Latest price change requests" padding="p-0">
            <x-slot name="actions">
                <x-button :href="route('admin.price-changes.index')" variant="ghost">Open queue</x-button>
            </x-slot>

            @if ($recentPriceChanges->isEmpty())
                <div class="p-6">
                    <x-empty-state
                        title="No requests yet"
                        description="Price changes submitted by a branch will land here for head office review." />
                </div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($recentPriceChanges as $change)
                        <li>
                            <a href="{{ route('admin.price-changes.show', $change) }}" class="block px-6 py-4 transition hover:bg-slate-50">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge :tone="$change->status->tone()">{{ $change->status->label() }}</x-badge>
                                    <span class="text-xs text-slate-500">{{ $change->branch?->name }}</span>
                                    <span class="ml-auto text-xs text-slate-400">{{ $change->created_at?->diffForHumans() }}</span>
                                </div>
                                <p class="mt-1.5 text-sm font-medium text-slate-800">
                                    {{ $change->catalogItem?->displayName() }}
                                </p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ number_format((float) $change->old_price, 2) }} &rarr;
                                    <span class="font-semibold text-slate-700">{{ number_format((float) $change->new_price, 2) }}</span>
                                    <span class="text-slate-400">{{ $currency }}</span>
                                    · by {{ $change->requester?->name ?? 'Unknown' }}
                                </p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card title="Latest inquiries" padding="p-0">
            <x-slot name="actions">
                <x-button :href="route('admin.tickets.index')" variant="ghost">Open inbox</x-button>
            </x-slot>

            @if ($recentTickets->isEmpty())
                <div class="p-6">
                    <x-empty-state
                        title="No inquiries"
                        description="Customer questions about a station will arrive here." />
                </div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($recentTickets as $ticket)
                        <li>
                            <a href="{{ route('admin.tickets.show', $ticket) }}" class="block px-6 py-4 transition hover:bg-slate-50">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge :tone="$ticket->status->tone()">{{ $ticket->status->label() }}</x-badge>
                                    <x-badge :tone="$ticket->priority->tone()">{{ $ticket->priority->label() }}</x-badge>
                                    <span class="font-mono text-xs text-slate-400">{{ $ticket->reference }}</span>
                                    <span class="ml-auto text-xs text-slate-400">{{ $ticket->lastActivityAt()->diffForHumans() }}</span>
                                </div>
                                <p class="mt-1.5 text-sm font-medium text-slate-800">{{ $ticket->subject }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $ticket->branch?->name }} · {{ $ticket->customer?->name ?? 'Deleted customer' }}
                                </p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2" title="Busiest stations" subtitle="Ranked by revenue over the last 30 days." padding="p-0">
            @if ($topBranches->isEmpty())
                <div class="p-6">
                    <x-empty-state title="No station activity" description="Sales recorded in this period will rank the network here." />
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
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($topBranches as $row)
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
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <x-card title="Product mix" padding="p-0">
            <x-slot name="actions">
                <x-button :href="route('admin.catalog.index')" variant="ghost">Catalogue</x-button>
            </x-slot>

            @if ($productBreakdown->isEmpty())
                <div class="p-6">
                    <x-empty-state title="Nothing sold yet" description="Once sales are recorded the product mix appears here." />
                </div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($productBreakdown as $row)
                        <li class="px-6 py-3">
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="truncate text-sm font-medium text-slate-800">{{ $row['label'] }}</span>
                                <span class="shrink-0 text-sm font-semibold text-slate-900">{{ number_format((float) $row['share'], 1) }}%</span>
                            </div>
                            <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-brand-500" style="width: {{ min(100, max(2, (float) $row['share'])) }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $money((float) $row['revenue']) }} {{ $currency }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
@endsection
