@props(['value'])

<th  scope="col" {{ $attributes->merge(['class' => 'px-3 py-2']) }}>
    {{ $value ?? $slot }}
</th>
