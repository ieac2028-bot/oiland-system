@props(['disabled' => false, 'type' => 'text'])

<input type="{{ $type }}" @disabled($disabled)
    {{ $attributes->class([
        'block w-full rounded-lg border-slate-300 text-sm shadow-sm transition',
        'focus:border-brand-500 focus:ring-brand-500',
        'disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500',
    ]) }}>
