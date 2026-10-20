@props(['active' => false, 'href' => '#'])

<a href="{{ $href }}" @class([
    'rounded-lg px-3 py-2 text-sm font-medium transition',
    'bg-slate-100 text-slate-900' => $active,
    'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! $active,
]) {{ $attributes }}>
    {{ $slot }}
</a>
