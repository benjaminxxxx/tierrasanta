@props([
    'descuentos' => null,
    'textoTodos' => 'TODOS', // ← Texto personalizable o null para ocultarlo
])

@php
use App\Models\PlanDescuentoSp;

$descuentos = $descuentos ?? PlanDescuentoSp::orderBy('orden')->get();
@endphp

<x-select {{ $attributes }}>
    @if (!empty($textoTodos))
        <option value="">{{ strtoupper($textoTodos) }}</option>
    @endif
    @foreach ($descuentos as $descuento)
        <option value="{{ $descuento->codigo }}">
            {{ mb_strtoupper($descuento->descripcion) }}
        </option>
    @endforeach
</x-select>
