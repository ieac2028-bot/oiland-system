@extends('layouts.admin')

@section('title', 'Products & services')
@section('subtitle', 'The head office catalog every branch can price against.')

@section('content')
    <x-card padding="p-0">
        <x-slot name="actions">
            <x-button :href="route('admin.catalog.create')">Add item</x-button>
        </x-slot>

        <form method="GET" action="{{ route('admin.catalog.index') }}" class="grid gap-3 border-b border-slate-200 px-6 py-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-form-field name="q" label="Search">
                <x-input
                    name="q"
                    :value="$filters['q'] ?? null"
                    placeholder="Name or SKU" />
            </x-form-field>

            <x-form-field name="type" label="Type">
                <x-select name="type">
                    <option value="">All types</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-form-field>

            <x-form-field name="status" label="Status">
                <x-select name="status">
                    <option value="">Any status</option>
                    <option value="active" @selected(($filters['status'] ?? null) === 'active')>Active only</option>
                    <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>Inactive only</option>
                </x-select>
            </x-form-field>

            <div class="flex items-end gap-2">
                <x-button type="submit">Filter</x-button>
                @if (array_filter($filters))
                    <x-button :href="route('admin.catalog.index')" variant="ghost">Reset</x-button>
                @endif
            </div>
        </form>

        @if ($items->isEmpty())
            <div class="p-6">
                <x-empty-state
                    title="No catalog items"
                    description="Add the fuels and services your network sells so branches can start pricing them.">
                    <x-slot name="action">
                        <x-button :href="route('admin.catalog.create')">Add the first item</x-button>
                    </x-slot>
                </x-empty-state>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-6 py-3 font-medium">Item</th>
                            <th class="px-6 py-3 font-medium">Type</th>
                            <th class="px-6 py-3 font-medium">SKU</th>
                            <th class="px-6 py-3 font-medium">Unit</th>
                            <th class="px-6 py-3 text-right font-medium">Branches</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($items as $item)
                            <tr>
                                <td class="px-6 py-3">
                                    <p class="font-medium text-slate-800">{{ $item->displayName() }}</p>
                                    <p class="text-xs text-slate-500">{{ Str::limit($item->short_description, 70) }}</p>
                                </td>
                                <td class="px-6 py-3">
                                    <x-badge :tone="$item->type->tone()">{{ $item->type->label() }}</x-badge>
                                </td>
                                <td class="px-6 py-3 font-mono text-xs text-slate-500">{{ $item->sku ?? '—' }}</td>
                                <td class="px-6 py-3 text-slate-600">{{ $item->unit ?? '—' }}</td>
                                <td class="px-6 py-3 text-right text-slate-600">{{ number_format($item->branches_count) }}</td>
                                <td class="px-6 py-3">
                                    <x-badge :tone="$item->is_active ? 'emerald' : 'slate'">
                                        {{ $item->is_active ? 'Active' : 'Inactive' }}
                                    </x-badge>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-button :href="route('admin.catalog.edit', $item)" variant="secondary">Edit</x-button>

                                        <form method="POST" action="{{ route('admin.catalog.destroy', $item) }}"
                                              onsubmit="return confirm('Remove {{ addslashes($item->name) }} from the catalog? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <x-button type="submit" variant="ghost">Delete</x-button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="border-t border-slate-200 px-6 py-3">
                    {{ $items->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection
