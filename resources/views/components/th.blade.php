@props(['value'])

<th  scope="col" {{ $attributes->merge(['class' => 'px-2 py-1']) }}>
    {{ $value ?? $slot }}
</th>
