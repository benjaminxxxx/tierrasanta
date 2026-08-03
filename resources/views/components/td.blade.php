@props(['value', 'sticky' => false])

<td {{ $attributes->merge([
        'class' => 'px-3 py-2' . ($sticky ? ' sticky left-0 z-10 bg-white dark:bg-zinc-950' : ''),
    ]) }}>
    {{ $value ?? $slot }}
</td>