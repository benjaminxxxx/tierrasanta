@props(['value'])

<td {{ $attributes->merge(['class' => 'px-6 py-4 dark:text-primaryTextDark']) }}>
    {{ $value ?? $slot }}
</td>
