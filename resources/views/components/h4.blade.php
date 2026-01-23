@props(['value'])

<h2  {{ $attributes->merge(['class' => 'font-semibold text-lg dark:text-white']) }}>
    {{ $value ?? $slot }}
</h2>
