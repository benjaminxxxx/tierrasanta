@props(['type' => 'submit', 'disabled' => false])

@php
    $classes = 'text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none space-x-2 focus:ring-blue-300 font-medium rounded-lg text-sm sm:w-auto px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800';

    if ($disabled) {
        $classes .= ' opacity-50 cursor-not-allowed';
    } else {
        $classes .= ' cursor-pointer';
    }
@endphp

<button {{ $attributes->merge(['type' => $type, 'class' => $classes]) }} @if($disabled) disabled @endif>
    {{ $slot }}
</button>
