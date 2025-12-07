@props(['value'])

<h2  {{ $attributes->merge(['class' => 'font-medium text-gray-800 dark:text-gray-200 text-md']) }}>
    {{ $value ?? $slot }}
</h2>
