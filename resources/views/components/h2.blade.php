@props(['value'])

<h2  {{ $attributes->merge(['class' => 'font-bold text-2xl dark:text-gray-200']) }}>
    {{ $value ?? $slot }}
</h2>
