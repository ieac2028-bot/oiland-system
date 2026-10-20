@extends('layouts.admin')

@section('title', 'Reviews')
@section('subtitle', $counts['pending'].' awaiting moderation · '.$counts['flagged'].' flagged')

@section('content')
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Awaiting moderation" :value="$counts['pending']" tone="amber" icon="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        <x-stat label="Published" :value="$counts['published']" tone="emerald" icon="M9 12.75L11.25 15 15 9.75" />
        <x-stat label="Flagged" :value="$counts['flagged']" tone="rose" icon="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" />
    </div>

    <x-card class="mt-6" padding="p-0">
        <form method="GET" action="{{ route('admin.reviews.index') }}" class="grid gap-3 border-b border-slate-200 px-6 py-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-form-field name="q" label="Search">
                <x-input name="q" :value="$filters['q'] ?? null" placeholder="Words used in the review" />
            </x-form-field>

            <x-form-field name="status" label="Status">
                <x-select name="status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-form-field>

            @if ($branches->isNotEmpty())
                <x-form-field name="branch_id" label="Station">
                    <x-select name="branch_id">
                        <option value="">All stations</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) ($filters['branch_id'] ?? 0) === $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </x-select>
                </x-form-field>
            @endif

            <div class="flex items-end">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="flagged" value="1" @checked($filters['flagged'] ?? false)
                           class="h-4 w-4 rounded border-slate-300 text-rose-500 focus:ring-rose-500">
                    <span class="text-sm text-slate-600">Flagged only</span>
                </label>
            </div>

            <div class="flex items-end gap-2">
                <x-button type="submit">Filter</x-button>
                @if (array_filter($filters))
                    <x-button :href="route('admin.reviews.index')" variant="ghost">Reset</x-button>
                @endif
            </div>
        </form>

        @if ($reviews->isEmpty())
            <div class="p-6">
                <x-empty-state
                    title="No reviews"
                    description="Nothing here matches these filters. Customers can only leave one review per station." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-6 py-3 font-medium">Rating</th>
                            <th class="px-6 py-3 font-medium">Review</th>
                            <th class="px-6 py-3 font-medium">Station</th>
                            <th class="px-6 py-3 font-medium">Customer</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 text-right font-medium">Posted</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($reviews as $review)
                            <tr @class(['bg-rose-50/40' => $review->is_flagged])>
                                <td class="px-6 py-3">
                                    <a href="{{ route('admin.reviews.show', $review) }}" class="inline-flex items-center gap-0.5" title="{{ $review->rating }} out of 5">
                                        @for ($star = 1; $star <= 5; $star++)
                                            <svg viewBox="0 0 20 20" fill="{{ $star <= $review->rating ? 'currentColor' : 'none' }}"
                                                 @class([
                                                     'h-3.5 w-3.5',
                                                     'text-amber-400' => $star <= $review->rating,
                                                     'text-slate-200' => $star > $review->rating,
                                                 ])
                                                 aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                      d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.79L10 14.8l-5.2 2.73.99-5.79-4.21-4.1 5.82-.85L10 1.5z" />
                                            </svg>
                                        @endfor
                                        <span class="sr-only">{{ $review->rating }} out of 5</span>
                                    </a>
                                </td>
                                <td class="px-6 py-3">
                                    <a href="{{ route('admin.reviews.show', $review) }}" class="font-medium text-slate-800 hover:text-brand-700">
                                        {{ $review->title ?: 'Untitled review' }}
                                    </a>
                                    <p class="text-xs text-slate-500">{{ Str::limit($review->body, 68) }}</p>
                                </td>
                                <td class="px-6 py-3 text-slate-600">{{ $review->branch?->name }}</td>
                                <td class="px-6 py-3 text-slate-600">{{ $review->user?->name ?? 'Deleted customer' }}</td>
                                <td class="px-6 py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <x-badge :tone="$review->status->tone()">{{ $review->status->label() }}</x-badge>
                                        @if ($review->is_flagged)
                                            <x-badge tone="rose">Flagged</x-badge>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-right text-xs text-slate-500">{{ $review->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($reviews->hasPages())
                <div class="border-t border-slate-200 px-6 py-3">
                    {{ $reviews->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection
