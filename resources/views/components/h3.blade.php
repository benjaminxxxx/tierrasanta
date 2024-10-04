@props(['value'])

<h2  {{ $attributes->merge(['class' => 'font-semibold text-gray-800 dark:text-primaryTextDark text-lg']) }}>
    {{ $value ?? $slot }}
</h2>
