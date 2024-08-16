@props(['value'])

<h2  {{ $attributes->merge(['class' => 'font-bold text-2xl']) }}>
    {{ $value ?? $slot }}
</h2>
