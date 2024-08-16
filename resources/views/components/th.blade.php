@props(['value'])

<th  scope="col" {{ $attributes->merge(['class' => 'px-6 py-3']) }}>
    {{ $value ?? $slot }}
</th>
