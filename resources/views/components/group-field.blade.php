@props(['value'])

<div {{ $attributes->merge(['class' => '']) }}>
    {{ $value ?? $slot }}
</div>
