@props([
    'id' => null,
    'label' => null,
    'error' => null,
    'disabled' => false,
    'size' => 'default', // small | default | large
])

@php
    $id = $id ?? md5($attributes->wire('model'));
    $model = $attributes->whereStartsWith('wire:model')->first();

    // Clases según el tamaño
    $sizeClasses = match($size) {
        'small' => 'p-2 text-sm',
        'large' => 'px-4 py-3 text-base',
        default => 'p-2.5 text-sm',
    };
@endphp

<x-group-field>
    @if ($label)
        <x-label for="{{ $model }}">{{ $label ?? ucfirst(str_replace('_', ' ', $model)) }}</x-label>
    @endif

    <select
        id="{{ $id }}"
        {{ $disabled ? 'disabled' : '' }}
        {!! $attributes->merge([
            'class' => "block w-full md:w-auto bg-gray-50 border border-gray-300 text-gray-900 rounded-lg 
                        focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 
                        dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 
                        dark:focus:border-blue-500 $sizeClasses"
        ]) !!}
    >
        {{ $slot }}
    </select>

    @if ($error && $model)
        <x-input-error for="{{ $model }}" />
    @endif
</x-group-field>
