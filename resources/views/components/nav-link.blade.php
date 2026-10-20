@props(['active' => false, 'href' => '#'])

<a href="{{ $href }}" @class([
    'header-choice px-2 py-2 text-sm transition focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-600',
    'font-bold text-black dark:text-white' => $active,
    'font-medium text-black hover:text-brand-700 dark:text-white dark:hover:text-brand-200' => ! $active,
]) {{ $attributes }}>
    {{ $slot }}
</a>
