@props(['value'])

<tr {{ $attributes->merge(['class' => '']) }}>
    {{ $value ?? $slot }}
</tr>
