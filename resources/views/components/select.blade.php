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
            'w-full pr-8 rounded-lg border border-slate-400 bg-transparent py-2 pl-5 outline-none  focus:border-primary focus-visible:shadow-none dark:border-0 dark:text-primaryTextDark dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary focus:ring-0',
    ]) !!}>
        {{ $slot }}
    </select>

    @if ($error && $model)
        <x-input-error for="{{ $model }}" />
    @endif
</x-group-field>
