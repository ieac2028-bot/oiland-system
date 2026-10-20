@props([
    'rating' => 0,
    'max' => 5,
    'size' => 'sm',
    'showValue' => false,
])

@php
    $sizes = [
        'xs' => 'h-3 w-3',
        'sm' => 'h-4 w-4',
        'md' => 'h-5 w-5',
    ];

    $iconSize = $sizes[$size] ?? $sizes['sm'];
    $full = (int) floor($rating);
    $partial = $rating - $full;
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1']) }} title="{{ number_format((float) $rating, 2) }} out of {{ $max }}">
    <span class="star-rating inline-flex" aria-hidden="true">
        @for ($star = 1; $star <= $max; $star++)
            <svg class="{{ $iconSize }} {{ $star <= $full ? 'text-amber-400' : 'text-slate-300' }}" viewBox="0 0 20 20" fill="currentColor">
                <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.36 4.18a1 1 0 0 0 .95.69h4.4c.97 0 1.37 1.24.59 1.81l-3.56 2.59a1 1 0 0 0-.36 1.12l1.36 4.18c.3.92-.76 1.69-1.54 1.12l-3.56-2.58a1 1 0 0 0-1.18 0l-3.56 2.58c-.78.57-1.84-.2-1.54-1.12l1.36-4.18a1 1 0 0 0-.36-1.12L1.75 9.61c-.78-.57-.38-1.81.59-1.81h4.4a1 1 0 0 0 .95-.69L9.05 2.93Z" />
            </svg>
        @endfor
    </span>

    @if ($showValue)
        <span class="text-xs font-medium text-slate-600">{{ number_format((float) $rating, 1) }}</span>
    @endif

    <span class="sr-only">{{ number_format((float) $rating, 2) }} out of {{ $max }} stars</span>
</span>
