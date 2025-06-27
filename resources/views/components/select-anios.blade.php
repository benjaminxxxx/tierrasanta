@props([
    'label' => 'Año',
    'error' => null,
    'disabled' => false,
    'max' => 'current',
])

@php
    $model = $attributes->whereStartsWith('wire:model')->first();

    $anioMin = 2015;
    $anioMax = is_numeric($max) ? intval($max) : now()->year;
@endphp

<x-select :label="$label" :error="$error" :disabled="$disabled" {{ $attributes }}>
    <option value="">Seleccionar año</option>
    @for ($anio = $anioMax; $anio >= $anioMin; $anio--)
        <option value="{{ $anio }}">{{ $anio }}</option>
    @endfor
</x-select>
