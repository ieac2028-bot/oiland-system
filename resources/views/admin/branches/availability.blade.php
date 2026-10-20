@extends('layouts.admin')

@section('title', 'Product availability')
@section('subtitle', $branch->name)

@section('content')
    <nav class="text-sm text-slate-500">
        <a href="{{ route('admin.branches.index') }}" class="hover:underline">Stations</a>
        <span class="mx-2">/</span>
        <a href="{{ route('admin.branches.show', $branch) }}" class="hover:underline">{{ $branch->name }}</a>
        <span class="mx-2">/</span>
        <span class="text-slate-700">Availability</span>
    </nav>

    <div class="mt-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">What does this station sell?</h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-500">
                Tick everything on sale at {{ $branch->name }}. Untick the rest and they disappear from the
                station page and from the price list for this branch.
            </p>
        </div>

        <x-button :href="route('admin.branches.show', $branch)" variant="secondary">Back to station</x-button>
    </div>

    <form method="POST" action="{{ route('admin.branches.availability.update', $branch) }}" class="mt-6 space-y-6">
        @csrf
        @method('PUT')

        <x-card padding="p-0">
            <x-slot name="title">Products</x-slot>
            <x-slot name="subtitle">{{ $products->count() }} active product(s) in the catalog.</x-slot>

            @if ($products->isEmpty())
                <div class="p-6">
                    <x-empty-state
                        title="No products in the catalog"
                        description="Add products to the catalog first, then choose which of them this station sells." />
                </div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($products as $product)
                        @php
                            $pivot = $availability->get($product->id);
                            $isAvailable = (bool) $pivot?->pivot?->is_available;
                        @endphp
                        <li class="flex items-start gap-3 px-6 py-3">
                            <input
                                type="checkbox"
                                name="items[]"
                                value="{{ $product->id }}"
                                id="item-{{ $product->id }}"
                                @checked($isAvailable)
                                class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <label for="item-{{ $product->id }}" class="min-w-0 flex-1 cursor-pointer">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-medium text-slate-800">{{ $product->name }}</span>
                                    <x-badge tone="slate">{{ $typeLabels[$product->type->value] ?? $product->type->label() }}</x-badge>
                                    @if ($product->unit)
                                        <span class="text-xs text-slate-500">per {{ $product->unit }}</span>
                                    @endif
                                </span>
                                @if ($product->description)
                                    <span class="mt-0.5 block text-xs text-slate-500">{{ Str::limit($product->description, 110) }}</span>
                                @endif
                            </label>

                            <div class="w-40 shrink-0">
                                <label for="note-{{ $product->id }}" class="sr-only">Note for {{ $product->name }}</label>
                                <input
                                    type="text"
                                    name="notes[{{ $product->id }}]"
                                    id="note-{{ $product->id }}"
                                    value="{{ old('notes.'.$product->id, $pivot?->pivot?->notes) }}"                                    placeholder="Optional note"
                                    class="block w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card padding="p-0">
            <x-slot name="title">Services</x-slot>
            <x-slot name="subtitle">{{ $services->count() }} active service(s) in the catalog.</x-slot>

            @if ($services->isEmpty())
                <div class="p-6">
                    <x-empty-state title="No services" description="There are no active services in the catalog." />
                </div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($services as $service)
                        @php
                            $pivot = $availability->get($service->id);
                            $isAvailable = (bool) $pivot?->pivot?->is_available;
                        @endphp
                        <li class="flex items-start gap-3 px-6 py-3">
                            <input
                                type="checkbox"
                                name="items[]"
                                value="{{ $service->id }}"
                                id="service-{{ $service->id }}"
                                @checked($isAvailable)
                                class="mt-1 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <label for="service-{{ $service->id }}" class="min-w-0 flex-1 cursor-pointer">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-medium text-slate-800">{{ $service->name }}</span>
                                    <x-badge tone="slate">{{ $typeLabels[$service->type->value] ?? $service->type->label() }}</x-badge>
                                </span>
                                @if ($service->description)
                                    <span class="mt-0.5 block text-xs text-slate-500">{{ Str::limit($service->description, 110) }}</span>
                                @endif
                            </label>

                            <div class="w-40 shrink-0">
                                <label for="service-note-{{ $service->id }}" class="sr-only">Note for {{ $service->name }}</label>
                                <input
                                    type="text"
                                    name="notes[{{ $service->id }}]"
                                    id="service-note-{{ $service->id }}"
                                    value="{{ old('notes.'.$service->id, $pivot?->pivot?->notes) }}"
                                    placeholder="Optional note"
                                    class="block w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <div class="flex flex-wrap items-center gap-2">
            <x-button type="submit">Save availability</x-button>
            <x-button :href="route('admin.branches.show', $branch)" variant="secondary">Cancel</x-button>

            <p class="ml-auto text-xs text-slate-500">
                Saving replaces the whole list, so untick anything that should stop being offered.
            </p>
        </div>
    </form>
@endsection
