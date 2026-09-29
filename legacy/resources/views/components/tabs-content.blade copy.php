@props(['value'])

<div x-show="selected === '{{ $value }}'" x-cloak class="mt-4">
    {{ $slot }}
</div>
