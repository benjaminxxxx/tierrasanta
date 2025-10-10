@php
use App\Models\PlanCargo;

$cargos = $cargos ?? PlanCargo::all();
@endphp

<x-select {{ $attributes }}>
    <option value="">TODOS</option>
    @foreach ($cargos as $cargo)
        <option value="{{ $cargo->codigo }}">
            {{ $cargo->nombre }}
        </option>
    @endforeach
</x-select>

