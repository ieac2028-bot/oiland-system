@props(['active' => false, 'href' => '#'])

<a href="{{ $href }}" @class([
    'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
    'bg-brand-50 text-brand-700' => $active,
    'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! $active,
]) {{ $attributes }}>
    {{ $slot }}
</a>
