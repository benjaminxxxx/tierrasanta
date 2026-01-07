@props([
    'label',
    'color' => 'blue',
])

@php
    $colorMap = [
        'blue' => 'text-blue-400',
        'slate' => 'text-slate-400',
    ];

    $textColor = $colorMap[$color] ?? $colorMap['blue'];
@endphp

<div class="bg-slate-800 bg-opacity-50 p-3 rounded-lg border border-slate-700">
    <p class="text-slate-400 mb-1">
        {{ $label }}
    </p>

    <p class="text-lg font-bold {{ $textColor }}">
        {{ $slot }}
    </p>
</div>
