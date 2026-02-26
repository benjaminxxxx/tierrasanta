@props(['value', 'sticky' => false])

<th scope="col" {{ $attributes->merge(['class' => 'px-3 py-2 text-center' . ($sticky ? ' sticky left-0 z-20 bg-muted' : '')]) }}>
    {{ $value ?? $slot }}
</th>