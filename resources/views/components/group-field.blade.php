@props(['value'])

<div {{ $attributes->merge(['class' => 'mb-4 lg:mb-2']) }}>
    {{ $value ?? $slot }}
</div>
