@props(['value', 'orientation' => 'horizontal'])

<div x-show="selected === '{{ $value }}'" x-cloak class="{{ $orientation === 'vertical' ? 'flex-1 min-w-0' : 'mt-4' }}">
    {{ $slot }}
</div>