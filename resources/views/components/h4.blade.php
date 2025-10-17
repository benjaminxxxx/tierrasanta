@props(['value'])

<h2  {{ $attributes->merge(['class' => 'font-semibold text-lg']) }}>
    {{ $value ?? $slot }}
</h2>
