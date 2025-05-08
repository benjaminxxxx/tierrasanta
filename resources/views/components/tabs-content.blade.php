@props(['value'])

<div x-show="selected === '{{ $value }}'" x-cloak>
    {{ $slot }}
</div>
