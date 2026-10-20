@props([
    'align' => 'left',
    'width' => '40',
])

@php
    $alignment = match ($align) {
        'center' => 'left-1/2 -translate-x-1/2',
        'right' => 'right-0',
        default => 'left-0',
    };

    $widthClass = match ($width) {
        '48' => 'w-48',
        '56' => 'w-56',
        '72' => 'w-72',
        '80' => 'w-80',
        '96' => 'w-96',
        default => 'w-40',
    };
@endphp

<div class="relative inline-block text-left" data-dropdown>
    <div data-dropdown-trigger>
        {{ $trigger }}
    </div>

    <div data-dropdown-menu hidden
         class="absolute z-50 mt-2 {{ $widthClass }} {{ $alignment }} rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
        {{ $content }}
    </div>
</div>
