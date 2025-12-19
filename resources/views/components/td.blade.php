@props(['value'])

<td {{ $attributes->merge(['class' => 'px-3 py-2']) }}>
    {{ $value ?? $slot }}
</td>
