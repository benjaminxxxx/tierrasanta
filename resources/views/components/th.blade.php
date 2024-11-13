@props(['value'])

<th  scope="col" {{ $attributes->merge(['class' => 'px-2 py-1 text-gray-900 dark:text-primaryTextDark']) }}>
    {{ $value ?? $slot }}
</th>
