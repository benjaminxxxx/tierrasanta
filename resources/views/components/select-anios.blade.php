@props([
    'label' => null,
    'error' => null,
    'disabled' => false,
    'max' => 'current',
])

@php
    $model = $attributes->whereStartsWith('wire:model')->first();

    $anioMin = \App\Services\Sistema\Vigencia\SistemaVigenciaReglas::anioMinimo(2015); // desde la vigencia del sistema (SISTEMA_VIGENCIA_DESDE)
    $anioMax = is_numeric($max) ? intval($max) : now()->year;
@endphp

<x-select :label="$label" :error="$error" :disabled="$disabled" {{ $attributes }}>
    <option value="">Seleccionar año</option>
    @for ($anio = $anioMax; $anio >= $anioMin; $anio--)
        <option value="{{ $anio }}">{{ $anio }}</option>
    @endfor
</x-select>
