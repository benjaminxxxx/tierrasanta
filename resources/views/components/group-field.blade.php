@props(['value'])

<div {{ $attributes->merge(['class' => 'mb-2']) }}>
    {{ $value ?? $slot }}
</div>
