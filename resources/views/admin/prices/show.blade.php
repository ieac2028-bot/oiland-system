@extends('layouts.admin')

@section('title', $price->catalogItem?->displayName() ?? 'Price')
@section('subtitle', $price->branch?->name)

@php
    $currency = $price->currency;

    $directionTone = match ($summary30['direction']) {
        'up' => 'emerald',
        'down' => 'rose',
        default => 'slate',
    };
@endphp

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat
            label="Current price"
            :value="number_format((float) $price->price, 2).' '.$currency"
            hint="Effective from {{ $price->effective_from?->format('d M Y') }}"
            tone="brand"
            icon="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33" />
        <x-stat
            label="30 day low"
            :value="number_format((float) $summary30['lowest'], 2)"
            tone="slate"
            icon="M4.5 12.75l6 6 9-13.5" />
        <x-stat
            label="30 day high"
            :value="number_format((float) $summary30['highest'], 2)"
            tone="slate"
            icon="M4.5 19.5L15 9m0 0l6 6m-6-6v12" />
        <x-stat
            label="30 day change"
            :value="($summary30['change'] >= 0 ? '+' : '').number_format((float) $summary30['change'], 2)"
            :hint="$summary30['change_percentage'] !== null ? number_format((float) $summary30['change_percentage'], 2).'% over the window' : 'Not enough history'"
            :tone="$directionTone"
            icon="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.281m5.94 2.28l-2.28 5.941" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-card title="Last 7 days" subtitle="Daily closing price.">
            @if ($trend7->isEmpty())
                <x-empty-state title="No history yet" description="Once this price changes a few times a trend will appear." />
            @else
                <div class="h-64">
                    <canvas
                        data-chart="line"
                        data-series="{{ json_encode([
                            'labels' => $trend7->pluck('label')->all(),
                            'values' => $trend7->pluck('price')->map(fn ($p) => round((float) $p, 2))->all(),
                            'label' => 'Price',
                        ]) }}"></canvas>
                </div>
            @endif
        </x-card>

        <x-card title="Last 30 days" subtitle="Daily closing price.">
            @if ($trend30->isEmpty())
                <x-empty-state title="No history yet" description="The 30 day trend fills in as prices move." />
            @else
                <div class="h-64">
                    <canvas
                        data-chart="line"
                        data-series="{{ json_encode([
                            'labels' => $trend30->pluck('label')->all(),
                            'values' => $trend30->pluck('price')->map(fn ($p) => round((float) $p, 2))->all(),
                            'label' => 'Price',
                        ]) }}"></canvas>
                </div>
            @endif
        </x-card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2" title="Price history" subtitle="Most recent 50 changes." padding="p-0">
            @if ($price->histories->isEmpty())
                <div class="p-6">
                    <x-empty-state title="No recorded changes" description="Every approved change is written to the history for audit." />
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-6 py-3 font-medium">Effective</th>
                                <th class="px-6 py-3 text-right font-medium">Was</th>
                                <th class="px-6 py-3 text-right font-medium">Now</th>
                                <th class="px-6 py-3 font-medium">Changed by</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($price->histories as $history)
                                <tr>
                                    <td class="px-6 py-3 text-slate-600">{{ $history->effective_from?->format('d M Y') }}</td>
                                    <td class="px-6 py-3 text-right text-slate-500">
                                        {{ $history->previous_price !== null ? number_format((float) $history->previous_price, 2) : '—' }}
                                    </td>
                                    <td class="px-6 py-3 text-right font-semibold text-slate-900">
                                        {{ number_format((float) $history->price, 2) }}
                                    </td>
                                    <td class="px-6 py-3 text-xs text-slate-500">
                                        {{ $history->changer?->name ?? 'System' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <aside class="space-y-6">
            <x-card title="Record">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Branch</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">
                            <a href="{{ route('admin.branches.show', $price->branch) }}" class="hover:text-brand-700">
                                {{ $price->branch?->name }}
                            </a>
                        </dd>
                        <dd class="text-xs text-slate-500">{{ $price->branch?->fullAddress() }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Approved by</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $price->approver?->name ?? 'Not recorded' }}</dd>
                        @if ($price->approved_at)
                            <dd class="text-xs text-slate-500">{{ $price->approved_at->format('d M Y H:i') }}</dd>
                        @endif
                    </div>

                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Retires on</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">
                            {{ $price->effective_to?->format('d M Y') ?? 'Still live' }}
                        </dd>
                    </div>
                </dl>

                <x-button :href="route('admin.prices.index')" variant="secondary" class="mt-5 w-full">Back to price list</x-button>
            </x-card>

            <x-card title="7 day summary" padding="p-0">
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex justify-between px-6 py-3">
                        <dt class="text-slate-500">Average</dt>
                        <dd class="font-semibold text-slate-900">{{ number_format((float) $summary7['average'], 2) }}</dd>
                    </div>
                    <div class="flex justify-between px-6 py-3">
                        <dt class="text-slate-500">Change</dt>
                        <dd class="font-semibold text-slate-900">
                            {{ ($summary7['change'] >= 0 ? '+' : '').number_format((float) $summary7['change'], 2) }}
                        </dd>
                    </div>
                    <div class="flex justify-between px-6 py-3">
                        <dt class="text-slate-500">Direction</dt>
                        <dd>
                            <x-badge :tone="$directionTone">{{ ucfirst($summary7['direction']) }}</x-badge>
                        </dd>
                    </div>
                </dl>
            </x-card>
        </aside>
    </div>
@endsection
