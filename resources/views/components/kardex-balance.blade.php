@props(['color' => 'emerald'])

@php
    $colorMap = [
        'emerald' => 'text-emerald-400',
        'red' => 'text-red-400',
    ];

    $textColor = $colorMap[$color] ?? 'text-emerald-400';
@endphp

<div class="bg-slate-900 bg-opacity-70 p-3 rounded-lg border border-slate-700">
    <p class="text-slate-400 mb-1">
        Balance
    </p>

    <p class="text-lg font-bold {{ $textColor }}">
        {{ $slot }}
    </p>
</div>
