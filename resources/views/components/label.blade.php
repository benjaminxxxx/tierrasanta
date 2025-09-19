@props(['value'])

<label {{ $attributes->merge(['class' => 'font-medium text-gray-900 dark:text-gray-300']) }}>
    {{ $value ?? $slot }}
</label>
