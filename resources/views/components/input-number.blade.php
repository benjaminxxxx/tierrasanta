@props([
    'label' => null, // Si se pasa, lo usa. Si no, toma el nombre del wire:model
    'error' => true,
    'id' => null
])

@php
    $model = $attributes->whereStartsWith('wire:model')->first(); // Obtiene el valor de wire:model
@endphp

<x-group-field>
    @if ($label || $model)
        <x-label for="{{ $model }}">{{ $label ?? ucfirst(str_replace('_', ' ', $model)) }}</x-label>
    @endif

    <x-input :id="$id" type="number" {{ $attributes->merge(['class' => 'form-input']) }} />

    @if ($error && $model)
        <x-input-error for="{{ $model }}" />
    @endif
</x-group-field>
