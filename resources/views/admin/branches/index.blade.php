@extends('layouts.admin')

@section('title', 'Stations')
@section('subtitle', $branches->total().' station(s) in the network')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <form method="GET" action="{{ route('admin.branches.index') }}" class="grid flex-1 gap-3 sm:grid-cols-2 lg:max-w-3xl lg:grid-cols-4">
            <x-form-field name="q" label="Search">
                <x-input name="q" :value="$filters['q'] ?? null" placeholder="Name, code, city or address" />
            </x-form-field>

            <x-form-field name="region" label="Region">
                <x-select name="region">
                    <option value="">All regions</option>
                    @foreach ($regions as $region)
                        <option value="{{ $region }}" @selected(($filters['region'] ?? null) === $region)>{{ $region }}</option>
                    @endforeach
                </x-select>
            </x-form-field>

            <x-form-field name="status" label="Status">
                <x-select name="status">
                    <option value="">All stations</option>
                    <option value="active" @selected(($filters['status'] ?? null) === 'active')>Active only</option>
                    <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>Inactive only</option>
                </x-select>
            </x-form-field>

            <div class="flex items-end gap-2">
                <x-button type="submit">Filter</x-button>
                @if (array_filter($filters))
                    <x-button :href="route('admin.branches.index')" variant="ghost">Reset</x-button>
                @endif
            </div>
        </form>

        @can('create', App\Models\Branch::class)
            <x-button :href="route('admin.branches.create')">Add a station</x-button>
        @endcan
    </div>

    <x-card class="mt-6" padding="p-0">
        @if ($branches->isEmpty())
            <div class="p-6">
                <x-empty-state
                    title="No stations"
                    description="Nothing matches these filters. Add the first station to start publishing prices." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-6 py-3 font-medium">Station</th>
                            <th class="px-6 py-3 font-medium">Region</th>
                            <th class="px-6 py-3 font-medium">Hours</th>
                            <th class="px-6 py-3 text-right font-medium">Rating</th>
                            <th class="px-6 py-3 text-right font-medium">Sales</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($branches as $branch)
                            <tr>
                                <td class="px-6 py-3">
                                    <a href="{{ route('admin.branches.show', $branch) }}" class="font-medium text-slate-800 hover:text-brand-700">
                                        {{ $branch->name }}
                                    </a>
                                    <p class="font-mono text-xs text-slate-500">{{ $branch->code }} · {{ $branch->city }}</p>
                                </td>
                                <td class="px-6 py-3 text-slate-600">{{ $branch->region }}</td>
                                <td class="px-6 py-3">
                                    @if ($branch->is_24_hours)
                                        <x-badge tone="brand">24 hours</x-badge>
                                    @else
                                        @php($todayHours = $branch->hoursForDay())
                                        <span class="text-xs text-slate-600">
                                            @if ($todayHours)
                                                {{ $todayHours['open'] }}&ndash;{{ $todayHours['close'] }} today
                                            @else
                                                Hours not set
                                            @endif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    @php($rating = round((float) $branch->reviews_avg_rating, 1))
                                    <span @class([
                                        'text-sm font-semibold',
                                        'text-amber-600' => $rating > 0,
                                        'text-slate-400' => $rating === 0,
                                    ])>
                                        {{ $rating > 0 ? number_format($rating, 1).' / 5' : '—' }}
                                    </span>
                                    <p class="text-xs text-slate-500">{{ $branch->reviews_count ?? 0 }} review(s)</p>
                                </td>
                                <td class="px-6 py-3 text-right text-slate-600">{{ number_format($branch->sales_count) }}</td>
                                <td class="px-6 py-3">
                                    <x-badge :tone="$branch->is_active ? 'emerald' : 'slate'">
                                        {{ $branch->is_active ? 'Active' : 'Inactive' }}
                                    </x-badge>
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.branches.show', $branch) }}"
                                           class="rounded-lg px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">View</a>

                                        @can('update', $branch)
                                            <a href="{{ route('admin.branches.edit', $branch) }}"
                                               class="rounded-lg px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">Edit</a>

                                            <form method="POST" action="{{ route('admin.branches.toggle-active', $branch) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rounded-lg px-2 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50">
                                                    {{ $branch->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($branches->hasPages())
                <div class="border-t border-slate-200 px-6 py-3">
                    {{ $branches->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection
