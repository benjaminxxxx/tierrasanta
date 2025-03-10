@props([
    'label' => null,
    'error' => true,
])

@php
    $model = $attributes->whereStartsWith('wire:model')->first(); // Obtiene el valor de wire:model
@endphp

<x-group-field>
    
    <x-label>
        {{ $label }}
    </x-label>
    <x-select {{ $attributes->merge(['class' => 'form-select']) }}>
        <option value="">{{ $placeholder }}</option>
        @foreach ($campos as $campo)
            <option value="{{ $campo->nombre }}">{{ $campo->nombre }}</option>
        @endforeach
    </x-select>

    @if ($error && $model)
        <x-input-error for="{{ $model }}" />
    @endif
    
</x-group-field>
