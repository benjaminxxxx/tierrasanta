@props(['label', 'value'])

<div {{ $attributes->merge(['class' => 'flex items-center justify-between px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-md border border-gray-100 dark:border-gray-800']) }}>
    <span class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase">
        {{ $label }}
    </span>
    <span class="text-base font-semibold text-gray-800 dark:text-gray-100">
        {{ $value }}
    </span>
</div>