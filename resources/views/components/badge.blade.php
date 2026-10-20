@props(['tone' => 'slate'])

@php
    $tones = [
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-500/20',
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-600/20',
        'deep' => 'bg-deep-50 text-deep-800 ring-deep-600/20',
        'accent' => 'bg-accent-50 text-accent-700 ring-accent-600/20',
        'emerald' => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
        'amber' => 'bg-amber-100 text-amber-800 ring-amber-600/20',
        'rose' => 'bg-rose-100 text-rose-800 ring-rose-600/20',
        'sky' => 'bg-sky-100 text-sky-800 ring-sky-600/20',
        'violet' => 'bg-violet-100 text-violet-800 ring-violet-600/20',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset', $tones[$tone] ?? $tones['slate']]) }}>
    {{ $slot }}
</span>
