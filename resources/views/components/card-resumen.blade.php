@props(['variant' => 'blue', 'label', 'value'])

@php
    $colors = [
        'blue'    => 'bg-blue-100 border-blue-200 text-blue-700 dark:bg-blue-700 dark:border-blue-600 dark:text-white',
        'emerald' => 'bg-emerald-100 border-emerald-200 text-emerald-700 dark:bg-emerald-700 dark:border-emerald-600 dark:text-white',
        'red'     => 'bg-red-100 border-red-200 text-red-700 dark:bg-red-700 dark:border-red-600 dark:text-white',
        'yellow'  => 'bg-yellow-100 border-yellow-200 text-yellow-700 dark:bg-yellow-700 dark:border-yellow-600 dark:text-white',
        'lime'    => 'bg-lime-100 border-lime-200 text-lime-700 dark:bg-lime-700 dark:border-lime-600 dark:text-white',
        'purple'  => 'bg-purple-100 border-purple-200 text-purple-700 dark:bg-purple-700 dark:border-purple-600 dark:text-white',
    ];

    $textColors = [
        'blue'    => 'text-blue-900 dark:text-blue-100',
        'emerald' => 'text-emerald-900 dark:text-emerald-100',
        'red'     => 'text-red-900 dark:text-red-100',
        'yellow'  => 'text-yellow-900 dark:text-yellow-100',
        'lime'    => 'text-lime-900 dark:text-lime-100',
        'purple'  => 'text-purple-900 dark:text-purple-100',
    ];

    $classes = $colors[$variant] ?? $colors['blue'];
    $valueClasses = $textColors[$variant] ?? $textColors['blue'];
@endphp

<div {{ $attributes->merge(['class' => "flex flex-col p-4 rounded-xl border $classes"]) }}>
    <span class="text-[10px] font-bold uppercase mb-1 opacity-80">
        {{ $label }}
    </span>
    <div {{ $attributes->whereStartsWith('x-ref') }} class="text-3xl font-black {{ $valueClasses }}">
        {{ $value }}
    </div>
</div>