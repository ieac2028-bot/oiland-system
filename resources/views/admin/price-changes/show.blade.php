@extends('layouts.admin')

@section('title', 'Price change request')
@section('subtitle', $priceChangeRequest->branch?->name.' · '.$priceChangeRequest->catalogItem?->displayName())

@php
    $currency = config('app.currency');
    $isPending = $priceChangeRequest->isPending();
    $delta = $priceChangeRequest->old_price !== null
        ? round((float) $priceChangeRequest->new_price - (float) $priceChangeRequest->old_price, 2)
        : null;
@endphp

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat
            label="Current live price"
            :value="$priceChangeRequest->old_price !== null ? number_format((float) $priceChangeRequest->old_price, 2) : 'None'"
            :hint="$currency"
            tone="slate"
            icon="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33" />
        <x-stat
            label="Requested price"
            :value="number_format((float) $priceChangeRequest->new_price, 2)"
            :hint="$currency"
            tone="brand"
            icon="M4.5 12.75l6 6 9-13.5" />
        <x-stat
            label="Change"
            :value="$delta !== null ? ($delta >= 0 ? '+' : '').number_format($delta, 2) : '—'"
            :hint="$delta !== null && $delta != 0 ? round(($delta / (float) $priceChangeRequest->old_price) * 100, 2).'% on the old price' : 'First price for this item'"
            :tone="$delta !== null && $delta > 0 ? 'emerald' : ($delta !== null && $delta < 0 ? 'rose' : 'slate')"
            icon="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22" />
        <x-stat
            label="Status"
            :value="$priceChangeRequest->status->label()"
            :hint="$isPending ? 'Awaiting a head office decision' : 'Decided '.$priceChangeRequest->reviewed_at?->diffForHumans()"
            :tone="$priceChangeRequest->status->tone()"
            icon="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Request detail" padding="p-0">
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex flex-wrap items-baseline justify-between gap-2 px-6 py-3">
                        <dt class="text-slate-500">Branch</dt>
                        <dd class="font-medium text-slate-800">
                            <a href="{{ route('admin.branches.show', $priceChangeRequest->branch) }}" class="hover:text-brand-700">
                                {{ $priceChangeRequest->branch?->name }}
                            </a>
                        </dd>
                    </div>
                    <div class="flex flex-wrap items-baseline justify-between gap-2 px-6 py-3">
                        <dt class="text-slate-500">Item</dt>
                        <dd class="font-medium text-slate-800">{{ $priceChangeRequest->catalogItem?->displayName() }}</dd>
                    </div>
                    <div class="flex flex-wrap items-baseline justify-between gap-2 px-6 py-3">
                        <dt class="text-slate-500">Requested by</dt>
                        <dd class="font-medium text-slate-800">
                            {{ $priceChangeRequest->requester?->name ?? 'Unknown' }}
                            <span class="block text-xs font-normal text-slate-500">{{ $priceChangeRequest->created_at?->format('d M Y H:i') }}</span>
                        </dd>
                    </div>

                    @if ($priceChangeRequest->note)
                        <div class="px-6 py-3">
                            <dt class="text-slate-500">Reason given</dt>
                            <dd class="mt-1 whitespace-pre-line text-slate-700">{{ $priceChangeRequest->note }}</dd>
                        </div>
                    @endif

                    @unless ($isPending)
                        <div class="flex flex-wrap items-baseline justify-between gap-2 px-6 py-3">
                            <dt class="text-slate-500">Reviewed by</dt>
                            <dd class="font-medium text-slate-800">
                                {{ $priceChangeRequest->reviewer?->name ?? 'Unknown' }}
                                <span class="block text-xs font-normal text-slate-500">{{ $priceChangeRequest->reviewed_at?->format('d M Y H:i') }}</span>
                            </dd>
                        </div>

                        @if ($priceChangeRequest->review_note)
                            <div class="px-6 py-3">
                                <dt class="text-slate-500">Reviewer note</dt>
                                <dd class="mt-1 whitespace-pre-line text-slate-700">{{ $priceChangeRequest->review_note }}</dd>
                            </div>
                        @endif
                    @endunless
                </dl>
            </x-card>

            <x-card title="Price over the last 30 days" subtitle="Context for the decision.">
                @if ($trend->isEmpty())
                    <x-empty-state title="No price history" description="There is no recorded history for this item at this branch." />
                @else
                    <div class="h-64">
                        <canvas
                            data-chart="line"
                            data-series="{{ json_encode([
                                'labels' => $trend->pluck('label')->all(),
                                'values' => $trend->pluck('price')->map(fn ($p) => round((float) $p, 2))->all(),
                                'label' => 'Live price',
                            ]) }}"></canvas>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="rounded-lg bg-slate-50 px-3 py-2">
                            <dt class="text-xs text-slate-500">Low</dt>
                            <dd class="text-sm font-semibold text-slate-900">{{ number_format((float) $summary['lowest'], 2) }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2">
                            <dt class="text-xs text-slate-500">High</dt>
                            <dd class="text-sm font-semibold text-slate-900">{{ number_format((float) $summary['highest'], 2) }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2">
                            <dt class="text-xs text-slate-500">Average</dt>
                            <dd class="text-sm font-semibold text-slate-900">{{ number_format((float) $summary['average'], 2) }}</dd>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2">
                            <dt class="text-xs text-slate-500">Change</dt>
                            <dd class="text-sm font-semibold text-slate-900">
                                {{ ($summary['change'] >= 0 ? '+' : '').number_format((float) $summary['change'], 2) }}
                            </dd>
                        </div>
                    </dl>
                @endif
            </x-card>
        </div>

        <aside class="space-y-6">
            @if ($isPending && auth()->user()->isSuperAdmin())
                <x-card title="Decision" subtitle="Approving publishes the new price immediately.">
                    <form method="POST" action="{{ route('admin.price-changes.approve', $priceChangeRequest) }}" class="space-y-3">
                        @csrf
                        <x-form-field name="approve_note" label="Note (optional)">
                            <x-textarea name="review_note" rows="3" maxlength="1000" placeholder="Approved against the regional cap.">{{ old('review_note') }}</x-textarea>
                        </x-form-field>
                        <x-button type="submit" variant="success" class="w-full">Approve and publish</x-button>
                    </form>

                    <div class="my-4 border-t border-slate-200"></div>

                    <form method="POST" action="{{ route('admin.price-changes.reject', $priceChangeRequest) }}" class="space-y-3">
                        @csrf
                        <x-form-field name="reject_note" label="Reason for rejection" required>
                            <x-textarea name="review_note" rows="3" maxlength="1000" required placeholder="Above the regional cap for this grade.">{{ old('review_note') }}</x-textarea>
                        </x-form-field>
                        <x-button type="submit" variant="danger" class="w-full">Reject request</x-button>
                    </form>
                </x-card>
            @endif

            @if ($isPending && ! auth()->user()->isSuperAdmin())
                <x-card title="Awaiting head office">
                    <p class="text-sm text-slate-600">
                        This request is with head office for a decision. You will be notified as soon as it is
                        approved or rejected.
                    </p>
                </x-card>

                <x-card title="Withdraw">
                    <p class="mb-3 text-sm text-slate-600">
                        Withdrawing removes the request from the queue. It cannot be recovered.
                    </p>
                    <form method="POST" action="{{ route('admin.price-changes.destroy', $priceChangeRequest) }}"
                          onsubmit="return confirm('Withdraw this price change request?');">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger" class="w-full">Withdraw request</x-button>
                    </form>
                </x-card>
            @endif

            <x-card title="Quick links" padding="p-0">
                <div class="divide-y divide-slate-100 text-sm">
                    <a href="{{ route('admin.prices.index') }}" class="block px-6 py-3 font-medium text-slate-700 hover:bg-slate-50">
                        Price listing
                    </a>
                    @if ($priceChangeRequest->histories->isNotEmpty())
                        <a href="{{ route('admin.prices.show', $priceChangeRequest->histories->first()->price_id) }}" class="block px-6 py-3 font-medium text-slate-700 hover:bg-slate-50">
                            Latest price trend
                        </a>
                    @endif
                    <a href="{{ route('admin.price-changes.index') }}" class="block px-6 py-3 font-medium text-slate-700 hover:bg-slate-50">
                        Back to the queue
                    </a>
                </div>
            </x-card>
        </aside>
    </div>
@endsection
