@props(['value'])

<th  scope="col" {{ $attributes->merge(['class' => 'px-6 py-4']) }}>
    {{ $value ?? $slot }}
</th>
