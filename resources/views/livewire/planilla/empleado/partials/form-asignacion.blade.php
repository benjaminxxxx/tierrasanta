{{-- Campos de una asignación (cargo y grupo). Variables: $conFecha, $cargos. Modelo: a.* --}}
<div class="grid grid-cols-2 gap-3">
    @if ($conFecha)
        <x-fecha label="Desde *" wire:model="a.desde" />
    @endif
    <x-select label="Cargo" wire:model="a.plan_cargo_id">
        <option value="">Sin cargo</option>
        @foreach ($cargos as $cargo)
            <option value="{{ $cargo->id }}">{{ $cargo->nombre }}{{ $cargo->cupo_maximo ? " (cupo {$cargo->cupo_maximo})" : '' }}</option>
        @endforeach
    </x-select>
    <x-select-planilla-grupos label="Grupo" textoTodos="Sin grupo" wire:model="a.grupo_codigo" />
    @if ($conFecha)
        <x-input label="Motivo" wire:model="a.motivo" placeholder="Ascenso, rotación…" />
    @endif
</div>
