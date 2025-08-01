@props(['value'])

<th  scope="col" {{ $attributes->merge(['class' => 'text-left px-2 py-1  text-gray-200 font-bold']) }}>
    {{ $value ?? $slot }}
</th>
