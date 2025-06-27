@props([
    'label' => 'Mes',
    'error' => null,
    'disabled' => false,
])

@php
    $model = $attributes->whereStartsWith('wire:model')->first();

    $meses = [
        '01' => 'Enero',
        '02' => 'Febrero',
        '03' => 'Marzo',
        '04' => 'Abril',
        '05' => 'Mayo',
        '06' => 'Junio',
        '07' => 'Julio',
        '08' => 'Agosto',
        '09' => 'Septiembre',
        '10' => 'Octubre',
        '11' => 'Noviembre',
        '12' => 'Diciembre',
    ];
@endphp

<x-select :label="$label" :error="$error" :disabled="$disabled" {{ $attributes }}>
    <option value="">Seleccionar mes</option>
    @foreach ($meses as $numero => $nombre)
        <option value="{{ $numero }}">{{ $nombre }}</option>
    @endforeach
</x-select>
