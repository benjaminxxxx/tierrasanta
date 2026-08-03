@props(['value', 'level' => null])

@php
    $rowClasses = match ((int) $level) {
        1 => 'bg-zinc-200 dark:bg-zinc-950 divide-x divide-zinc-300 dark:divide-zinc-700',
        2 => 'bg-zinc-100 dark:bg-zinc-900 divide-x divide-zinc-300 dark:divide-zinc-700',
        default => 'hover:bg-zinc-50 dark:hover:bg-zinc-900/60 transition-colors',
    };
@endphp

<tr {{ $attributes->merge(['class' => $rowClasses]) }}>
    {{ $value ?? $slot }}
</tr>