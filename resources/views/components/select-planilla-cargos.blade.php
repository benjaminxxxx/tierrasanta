@php
use App\Models\PlanCargo;

$cargos = $cargos ?? PlanCargo::where('activo',true)->get();
@endphp

<x-select {{ $attributes }}>
    <option value="">TODOS</option>
    @foreach ($cargos as $cargo)
        <option value="{{ $cargo->id }}">
            {{ $cargo->nombre }}
        </option>
    @endforeach
</x-select>

