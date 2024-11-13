@props(['value'])

<tr {{ $attributes->merge(['class' => 'bg-white border-b  dark:bg-boxdark dark:border-primaryDark hover:bg-gray-50 dark:hover:bg-primaryDark']) }}>
    {{ $value ?? $slot }}
</tr>
