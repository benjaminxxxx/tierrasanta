@props(['value'])

<h2  {{ $attributes->merge(['class' => 'font-semibold text-gray-800 text-lg']) }}>
    {{ $value ?? $slot }}
</h2>
