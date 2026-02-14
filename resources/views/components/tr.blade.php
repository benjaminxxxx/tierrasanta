@props(['value'])

<tr {{ $attributes->merge(['class' => 'border-border']) }}>
    {{ $value ?? $slot }}
</tr>
