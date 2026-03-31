@props([
    'label',
    'color' => 'blue',
])

@php
    $colorMap = [
        'blue' => 'text-blue-600 dark:text-blue-400',
        'slate' => 'text-slate-600 dark:text-slate-400',
    ];

    $textColor = $colorMap[$color] ?? $colorMap['blue'];
@endphp

<div class="p-3 rounded-lg border 
            bg-white dark:bg-slate-800 
            border-slate-200 dark:border-slate-700">
    
    <p class="text-slate-500 dark:text-slate-400 mb-1 text-xs">
        {{ $label }}
    </p>

    <p class="text-lg font-bold {{ $textColor }}">
        {{ $slot }}
    </p>
</div>