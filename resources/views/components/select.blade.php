@props([
    'id' => null,
    'label' => null,
    'error' => null,
    'disabled' => false,
])

@php
    $id = $id ?? md5($attributes->wire('model'));   
    $model = $attributes->whereStartsWith('wire:model')->first(); // Obtiene el valor de wire:model
@endphp

<x-group-field>
    @if ($label)
        <x-label for="{{ $model }}">{{ $label ?? ucfirst(str_replace('_', ' ', $model)) }}</x-label>
    @endif

    <select id="{{ $id }}" {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
        'class' =>
            'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500',
    ]) !!}>
        {{ $slot }}
    </select>

    @if ($error && $model)
        <x-input-error for="{{ $model }}" />
    @endif
</x-group-field>
