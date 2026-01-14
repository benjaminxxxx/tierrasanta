<div>
    <!--MODULO COCHINILLA COSECHA MAMA-->
    <x-card>
        <x-flex>
            <x-title>
                COSECHAS DE MAMA
            </x-title>
        </x-flex>
        <x-flex class="mt-4">
            <div>
                <x-select label="Filtrar por año" wire:model.live="anioSeleccionado">
                    <option value="">Todos los años</option>
                    @foreach ($aniosDisponibles as $anioDisponible)
                        <option value="{{ $anioDisponible }}">{{ $anioDisponible }}</option>
                    @endforeach
                </x-select>
            </div>
            <div>
                <x-select-campo label="Filtrar por Campo" wire:model.live="campoSeleccionado" />
            </div>
            <div>
                <x-select-campanias label="Filtrar por Campaña" wire:model.live="campaniaSeleccionado" />
            </div>
            <div>
                <x-select label="Filtrar por observación" wire:model.live="observacionSeleccionado">
                    <option value="">Todas las observaciones</option>
                    @foreach ($observaciones as $observacion)
                        <option value="{{ $observacion->codigo }}">{{ $observacion->descripcion }}</option>
                    @endforeach
                </x-select>
            </div>
        </x-flex>
        <x-table class="mt-4">
            <x-slot name="thead">
                <x-tr>
                    <x-th class="text-center">
                        Fecha
                    </x-th>
                    <x-th class="text-center">
                        Campo
                    </x-th>
                    <x-th class="text-center">
                        Área
                    </x-th>
                    <x-th class="text-center">
                        Campaña
                    </x-th>
                    <x-th class="text-center">
                        KG
                    </x-th>
                    <x-th class="text-center">
                        KgxHa
                    </x-th>
                    <x-th class="text-center">
                        Obs
                    </x-th>
                    <x-th class="text-center">

                    </x-th>
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                @foreach ($cosechasMama as $indice => $cosecha)
                    <x-tr>

                        <x-td class="text-center">{{ $cosecha->fecha }}</x-td>
                        <x-td class="text-center">{{ $cosecha->ingreso->campo }}</x-td>
                        <x-td class="text-center">{{ $cosecha->ingreso->area }}</x-td>
                        <x-td class="text-center">{{ $cosecha->ingreso->campoCampania?->nombre_campania }}</x-td>
                        <x-td class="text-center">{{ $cosecha->total_kilos }}</x-td>
                        <x-td class="text-center">{{ number_format($cosecha->kg_ha, 2) }}</x-td>
                        <x-td class="text-center">{{ $cosecha->observacion }}</x-td>
                        <x-td class="text-center">
                            <x-button @click="$wire.dispatch('verDistribucion',{cosechaId:{{ $cosecha->id }}})">
                                <i class="fas fa-th-large"></i> Distribución
                            </x-button>
                        </x-td>
                    </x-tr>
                @endforeach
            </x-slot>

        </x-table>
        <div class="my-4">
            {{ $cosechasMama->links() }}
        </div>
    </x-card>

    <livewire:cochinilla-cosecha-mamas-distribucion-component />

    <x-loading wire:loading />
</div>
