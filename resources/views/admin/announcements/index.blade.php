@extends('layouts.admin')

@section('title', 'Announcements')
@section('subtitle', $counts['live'].' live · '.$counts['scheduled'].' scheduled · '.$counts['draft'].' draft')

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Live" :value="$counts['live']" tone="emerald" icon="M9 12.75L11.25 15 15 9.75" />
        <x-stat label="Scheduled" :value="$counts['scheduled']" tone="sky" icon="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        <x-stat label="Expired" :value="$counts['expired']" tone="slate" icon="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        <x-stat label="Draft" :value="$counts['draft']" tone="amber" icon="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
    </div>

    <div class="mt-6 flex flex-wrap items-end justify-between gap-3">
        <form method="GET" action="{{ route('admin.announcements.index') }}" class="grid flex-1 gap-3 sm:grid-cols-2 lg:max-w-3xl lg:grid-cols-5">
            <x-form-field name="q" label="Search">
                <x-input name="q" :value="$filters['q'] ?? null" placeholder="Title" />
            </x-form-field>

            <x-form-field name="type" label="Post type">
                <x-select name="type">
                    <option value="">All types</option>
                    <option value="notice" @selected(($filters['type'] ?? null) === 'notice')>Offer or notice</option>
                    <option value="event" @selected(($filters['type'] ?? null) === 'event')>Employee event</option>
                    <option value="job" @selected(($filters['type'] ?? null) === 'job')>Job opening</option>
                </x-select>
            </x-form-field>

            <x-form-field name="scope" label="Audience">
                <x-select name="scope">
                    <option value="">All audiences</option>
                    @foreach ($scopes as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['scope'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-form-field>

            <x-form-field name="status" label="Status">
                <x-select name="status">
                    <option value="">Any status</option>
                    <option value="live" @selected(($filters['status'] ?? null) === 'live')>Live</option>
                    <option value="scheduled" @selected(($filters['status'] ?? null) === 'scheduled')>Scheduled</option>
                    <option value="expired" @selected(($filters['status'] ?? null) === 'expired')>Expired</option>
                    <option value="draft" @selected(($filters['status'] ?? null) === 'draft')>Draft</option>
                </x-select>
            </x-form-field>

            <div class="flex items-end gap-2">
                <x-button type="submit">Filter</x-button>
                @if (array_filter($filters))
                    <x-button :href="route('admin.announcements.index')" variant="ghost">Reset</x-button>
                @endif
            </div>
        </form>

        @can('create', App\Models\Announcement::class)
            <x-button :href="route('admin.announcements.create')">New announcement</x-button>
        @endcan
    </div>

    <x-card class="mt-6" padding="p-0">
        @if ($announcements->isEmpty())
            <div class="p-6">
                <x-empty-state
                    title="No announcements"
                    description="Nothing matches these filters. Announcements appear on the home page and station pages." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-6 py-3 font-medium">Title</th>
                            <th class="px-6 py-3 font-medium">Audience</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 font-medium">Runs</th>
                            <th class="px-6 py-3 font-medium">Author</th>
                            <th class="px-6 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($announcements as $announcement)
                            <tr>
                                <td class="px-6 py-3">
                                    <a href="{{ route('admin.announcements.edit', $announcement) }}"
                                       class="font-medium text-slate-800 hover:text-brand-700">
                                        {{ $announcement->title }}
                                    </a>
                                    <p class="text-xs font-medium text-brand-700">{{ $announcement->typeLabel() }}</p>
                                    @if ($announcement->is_featured)
                                        <x-badge tone="violet" class="mt-1">Featured</x-badge>
                                    @endif
                                    <p class="text-xs text-slate-500">{{ $announcement->summary(70) }}</p>
                                </td>
                                <td class="px-6 py-3 text-slate-600">
                                    @if ($announcement->branches->isEmpty())
                                        <span class="text-xs">Everyone</span>
                                    @else
                                        <span class="text-xs">{{ $announcement->branches->count() }} station(s)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3">
                                    @php($statusLabel = $announcement->statusLabel())
                                    <x-badge :tone="match ($statusLabel) {
                                        'Live' => 'emerald',
                                        'Scheduled' => 'sky',
                                        'Expired' => 'slate',
                                        default => 'amber',
                                    }">{{ $statusLabel }}</x-badge>
                                </td>
                                <td class="px-6 py-3 text-xs text-slate-500">
                                    {{ $announcement->publish_at?->format('d M Y') ?? 'Immediately' }}
                                    @if ($announcement->expires_at)
                                        <br>until {{ $announcement->expires_at->format('d M Y') }}
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-xs text-slate-600">{{ $announcement->author?->name ?? 'Former staff' }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        @can('update', $announcement)
                                            <a href="{{ route('admin.announcements.edit', $announcement) }}"
                                               class="rounded-lg px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100">Edit</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($announcements->hasPages())
                <div class="border-t border-slate-200 px-6 py-3">
                    {{ $announcements->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection
