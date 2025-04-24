@props([
    'label' => null,
    'error' => true,
])

@php
    $model = $attributes->whereStartsWith('wire:model')->first(); // Obtiene el valor de wire:model
@endphp

<x-group-field>
    
    <x-select label="{{$label}}" error="{{$model}}" {{ $attributes->merge(['class' => 'form-select']) }}>
        <option value="">{{ $placeholder }}</option>
        @foreach ($campos as $campo)
            <option value="{{ $campo->nombre }}">{{ $campo->nombre }}</option>
        @endforeach
    </x-select>
    
</x-group-field>
