<x-flex class="my-4">
    <x-input id="buscar" label="Buscar por nombre o DNI" type="text" wire:model.live.debounce.300ms="filtros.buscar"
        placeholder="Nombre, apellidos o documento..." />
    {{-- Estado del Contrato --}}
    <x-select label="Estado" wire:model.live="filtros.estado" class="w-auto">
        <option value="">TODOS</option>
        <option value="activo">ACTIVO</option>
        <option value="finalizado">FINALIZADO</option>
    </x-select>

    {{-- Tipo de Planilla --}}
    <x-select label="Tipo de Planilla" wire:model.live="filtros.tipo_planilla" class="w-auto">
        <option value="">TODAS</option>
        <option value="agraria">AGRARIA</option>
        <option value="oficina">OFICINA</option>
        <option value="general">GENERAL</option>
        <option value="mype">MYPE</option>
        <option value="construccion">CONSTRUCCIÓN</option>
    </x-select>

    {{-- Cargo (Reutilizando tu componente) --}}
    <x-select-planilla-cargos label="Cargo" wire:model.live="filtros.cargo_codigo" class="w-auto" />

    {{-- Grupo (Reutilizando tu componente) --}}
    <x-select-planilla-grupos label="Grupo" textoTodos="TODOS" wire:model.live="filtros.grupo_codigo" class="w-auto" />

    <x-input label="Desde (Fecha Inicio)" type="date" wire:model.live.debounce.500ms="filtros.fecha_desde"  class="w-auto"/>
    <x-input label="Hasta (Fecha Inicio)" type="date" wire:model.live.debounce.500ms="filtros.fecha_hasta"  class="w-auto"/>


    <button wire:click="limpiarFiltros" class="text-xs text-red-600 hover:underline">Limpiar filtros</button>

</x-flex>
