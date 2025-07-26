@props(['type' => 'submit', 'disabled' => false])

@php
    $classes = 'inline-block rounded-lg py-2 px-4 font-medium text-white transition border border-primary dark:border-meta-4 bg-primary dark:bg-primaryDark hover:bg-opacity-90';

    if ($disabled) {
        $classes .= ' opacity-50 cursor-not-allowed';
    } else {
        $classes .= ' cursor-pointer';
    }
@endphp

<button {{ $attributes->merge(['type' => $type, 'class' => $classes]) }} @if($disabled) disabled @endif>
    {{ $slot }}
</button>
