@props(['value'])

<th  scope="col" {{ $attributes->merge(['class' => 'px-2 py-2 bg-gray-50 text-xs text-gray-700 uppercase  dark:bg-gray-700 dark:text-gray-400']) }}>
    {{ $value ?? $slot }}
</th>
