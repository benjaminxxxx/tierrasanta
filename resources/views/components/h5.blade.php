@props(['value'])

<h5 {{ $attributes->merge(['class' => 'block mb-2 font-bold text-gray-900 dark:text-white']) }}>
    {{ $value ?? $slot }}
</h5>
