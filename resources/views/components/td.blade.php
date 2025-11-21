@props(['value'])

<td {{ $attributes->merge(['class' => 'px-2 py-1']) }}>
    {{ $value ?? $slot }}
</td>
