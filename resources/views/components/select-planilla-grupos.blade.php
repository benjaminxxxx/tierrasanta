@props([
    'grupos' => null,
    'textoTodos' => 'TODOS', // ← Texto personalizable o null para ocultarlo
])

@php
use App\Models\PlanGrupo;

$grupos = $grupos ?? PlanGrupo::all();
@endphp

<x-select {{ $attributes }}>
    @if (!empty($textoTodos))
        <option value="">{{ strtoupper($textoTodos) }}</option>
    @endif

    @foreach ($grupos as $grupo)
        <option value="{{ $grupo->codigo }}">
            {{ $grupo->descripcion }}
        </option>
    @endforeach
</x-select>
