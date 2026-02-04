@props(['value'])

<td {{ $attributes->merge(['class' => 'px-3 py-2 text-center']) }}>
    {{ $value ?? $slot }}
</td>
