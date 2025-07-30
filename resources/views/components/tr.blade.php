@props(['value'])

<tr {{ $attributes->merge(['class' => 'border-b border-gray-600']) }}>
    {{ $value ?? $slot }}
</tr>
