<x-card class="mt-4">
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

        <x-select label="Contrato" wire:model.live="estadoContrato" size="small" class="w-auto">
            <option value="">TODOS</option>
            <option value="con">CON CONTRATO</option>
            <option value="sin">SIN CONTRATO</option>
        </x-select>
        @if ($estadoContrato !== 'sin')
            <x-select-planilla-cargos label="Cargo" wire:model.live="planCargoId" size="small" class=" w-auto" />

            <x-select-planilla-descuentos label="SPP o SNP" wire:model.live="planDescuentoSpCodigo" size="small" class=" w-auto" />

            <x-select-planilla-grupos label="Grupo" wire:model.live="planGrupoCodigo" size="small" class=" w-auto" />

            <x-select label="Tipo de planilla" wire:model.live="planTipoPlanilla" size="small" class=" w-auto">
                <option value="">TODOS</option>
                <option value="agraria">AGRARIA</option>
                <option value="oficina">OFICINA</option>
            </x-select>
        @endif


        <x-select label="Género" class="uppercase w-auto" wire:model.live="planGenero" size="small" >
            <option value="">TODOS</option>
            <option value="F">MUJERES</option>
            <option value="M">HOMBRES</option>
        </x-select>


        <x-select label="Estado" class="uppercase w-auto" wire:model.live="planEliminados" size="small">
            <option value="">ACTIVOS</option>
            <option value="eliminados">Eliminados</option>
        </x-select>

    </x-flex>
</x-card>
