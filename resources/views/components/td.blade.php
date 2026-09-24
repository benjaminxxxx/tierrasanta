@props(['value', 'sticky' => false, 'compact' => false])

@php
    $padding = $compact ? 'px-1.5 py-0.5' : 'px-3 py-2';
@endphp

<td {{ $attributes->merge([
        'class' => $padding . ($sticky ? ' sticky left-0 z-10 bg-zinc-200 dark:bg-zinc-950' : ''),
    ]) }}>
    {{ $value ?? $slot }}
</td>