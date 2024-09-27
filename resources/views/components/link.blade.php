@props(['active'])

@php
    $classes =
        $active ?? false
            ? 'text-orange-600 hover:text-orange-700 text-md font-bold mt-3 text-left inline-block float-left'
            : 'text-orange-600 hover:text-orange-700 text-md font-bold mt-3 text-left inline-block float-left';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
