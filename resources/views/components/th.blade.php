@props(['value'])

<th  scope="col" {{ $attributes->merge(['class' => 'text-left p-3 text-gray-300 font-medium']) }}>
    {{ $value ?? $slot }}
</th>
