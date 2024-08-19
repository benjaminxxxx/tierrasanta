@props(['active'])

@php
    $classes =
        $active ?? false
            ? 'group relative flex items-center gap-2.5 rounded-md px-4 font-medium duration-300 ease-in-out hover:text-white !text-white'
            : 'group relative flex items-center gap-2.5 rounded-md px-4 font-medium text-gray duration-300 ease-in-out hover:text-white';
@endphp

<li>
    <a {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
</li>
