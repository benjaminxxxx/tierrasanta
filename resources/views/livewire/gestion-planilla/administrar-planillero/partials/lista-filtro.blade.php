<x-card2 class="mt-4">
    <x-flex>
        <x-group-field>
            <x-label for="cargo_id">Nombre o DNI</x-label>
            <div class="relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-primary">
                    <i class="fa fa-search"></i>
                </div>
                <x-input type="search" wire:model.live.debounce.200="filtro" id="default-search" class="w-full !pl-10"
                    autocomplete="off" placeholder="Busca por Nombres, Apellidos o Documento" size="xs" />
            </div>
        </x-group-field>

        <x-select-planilla-cargos label="Cargo" wire:model.live="planCargoId" size="small" />
        <x-select-planilla-descuentos label="SPP o SNP" wire:model.live="planDescuentoSpCodigo" size="small" />
        <x-select-planilla-grupos label="Grupo" wire:model.live="planGrupoCodigo" size="small" />

        <x-select label="Género" class="uppercase" wire:model.live="planGenero" size="small">
            <option value="">TODOS</option>
            <option value="F">MUJERES</option>
            <option value="M">HOMBRES</option>
        </x-select>

        <x-select label="Tipo de planilla" class="uppercase" wire:model.live="planTipoPlanilla" size="small">
            <option value="">TODOS</option>
            <option value="agraria">AGRARIA</option>
            <option value="oficina">OFICINA</option>
        </x-select>

        <x-select label="Estado" class="uppercase" wire:model.live="planEliminados" size="small">
            <option value="">ACTIVOS</option>
            <option value="eliminados">Eliminados</option>
        </x-select>

    </x-flex>
</x-card2>