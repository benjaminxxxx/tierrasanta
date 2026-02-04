@props([
    'label' => '',
    'error' => null,
    'disabled' => false,
])

@php
    $model = $attributes->whereStartsWith('wire:model')->first();

    $meses = [
        '1' => 'Enero',
        '2' => 'Febrero',
        '3' => 'Marzo',
        '4' => 'Abril',
        '5' => 'Mayo',
        '6' => 'Junio',
        '7' => 'Julio',
        '8' => 'Agosto',
        '9' => 'Septiembre',
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
