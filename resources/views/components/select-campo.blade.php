@props([
    'label' => 'Seleccionar campo',
    'error' => true,
])

@php
    $model = $attributes->whereStartsWith('wire:model')->first(); // Obtiene el valor de wire:model
@endphp

<x-group-field>
    <x-label value="{{ $label }}" />
    <x-searchable-select
        :options="$campos"
        :placeholder="$placeholder"
        {{ $attributes }}
    />
    @if ($error)
        <x-input-error :for="$model" />
    @endif
</x-group-field>
