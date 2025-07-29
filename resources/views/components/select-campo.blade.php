@props([
    'label' => null,
    'error' => null,
])

@php
    $model = $attributes->whereStartsWith('wire:model')->first(); // Obtiene el valor de wire:model
@endphp

<x-group-field>
    @if ($label)
        <x-label value="{{ $label }}" />
    @endif
    
    <x-searchable
        :options="$campos"
        :placeholder="$placeholder"
        {{ $attributes }}
    />
    @if ($error)
        <x-input-error :for="$model" />
    @endif
</x-group-field>
