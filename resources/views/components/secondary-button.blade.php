@props(['type' => 'button', 'disabled' => false])

@php
    $classes = 'text-gray-900 bg-white border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:ring-4 focus:outline-none focus:ring-gray-100 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700';

    if ($disabled) {
        $classes .= ' opacity-50 cursor-not-allowed';
    } else {
        $classes .= ' cursor-pointer';
    }
@endphp

<button {{ $attributes->merge(['type' => $type, 'class' => $classes]) }} @if($disabled) disabled @endif>
    {{ $slot }}
</button>
