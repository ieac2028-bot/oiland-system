@props(['name' => null, 'label', 'required' => false, 'hint' => null])

@php($fieldName = $name ?? $attributes->get('for'))

<div>
    <label for="{{ $fieldName }}" class="block text-sm font-medium text-slate-700">
        {{ $label }}
        @if ($required)
            <span class="text-rose-500" aria-hidden="true">*</span>
        @endif
    </label>

    @isset($slot)
        <div class="mt-1.5">{{ $slot }}</div>
    @endisset

    @if ($hint)
        <p class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if ($fieldName)
        @error($fieldName)
            <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
        @enderror
    @endif
</div>
