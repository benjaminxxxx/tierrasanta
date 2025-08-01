@props(['value'])

<td {{ $attributes->merge(['class' => 'px-2 py-1 dark:text-gray-300']) }}>
    {{ $value ?? $slot }}
</td>
