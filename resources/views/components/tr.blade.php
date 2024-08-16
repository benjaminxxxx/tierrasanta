@props(['value'])

<tr {{ $attributes->merge(['class' => 'dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600']) }}>
    {{ $value ?? $slot }}
</tr>
