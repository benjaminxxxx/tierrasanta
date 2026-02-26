@props(['value', 'sticky' => false])

<td {{ $attributes->merge(['class' => 'px-3 py-2 text-center' . ($sticky ? ' sticky left-0 z-10 bg-background' : '')]) }}>
    {{ $value ?? $slot }}
</td>