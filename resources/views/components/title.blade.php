@props(['value'])

<h1  {{ $attributes->merge(['class' => 'font-semibold text-gray-800 dark:text-white text-xl']) }}>
    {{ $value ?? $slot }}
</h1>
