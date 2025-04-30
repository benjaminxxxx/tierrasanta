<div>
    <!--MODULO COCHINILLA COSECHA MAMA-->
    <x-loading wire:loading />

    <x-flex>
        <x-h3>
            COSECHAS DE MAMA
        </x-h3>
    </x-flex>
    <x-card class="mt-3">
        <x-spacing>
            <x-flex>
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
        </x-spacing>
    </x-card>
    <x-card class="mt-3">
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th rowspan="2" class="text-center">
                            Fecha
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Campo
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Área
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Campaña
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            KG
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            KgxHa
                        </x-th>
                        <x-th colspan="2" class="text-center">
                            Obs
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($cosechasMama as $indice => $cosecha)
                        <x-tr>
                            <x-td class="text-center">{{ $cosecha->fecha }}</x-td>
                            <x-td class="text-center">{{ $cosecha->campo }}</x-td>
                            <x-td class="text-center">{{ $cosecha->area }}</x-td>
                            <x-td class="text-center">{{ $cosecha->campania }}</x-td>
                            <x-td class="text-center">{{ $cosecha->kg }}</x-td>
                            <x-td class="text-center">{{ number_format($cosecha->kg_ha,2) }}</x-td>
                            <x-td class="text-center">{{ $cosecha->observacion }}</x-td>
                        </x-tr>
                    @endforeach
                </x-slot>

            </x-table>
            <div class="my-4">
                {{ $ingresosPaginados->links() }}
            </div>
        </x-spacing>
    </x-card>
</div>
