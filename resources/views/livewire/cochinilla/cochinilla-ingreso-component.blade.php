<div class="space-y-4">
    <!--MODULO COCHINILLA INGRESO PRINCIPAL-->
    <x-flex class="justify-between">
        <div>
            <x-title>Ingreso de Cochinilla</x-title>
            <x-subtitle>
                Ingrese los datos de los ingresos de cochinilla, agregue sublotes, venteados y filtrados.
            </x-subtitle>
        </div>
        <x-flex>
            <x-button @click="$wire.dispatch('agregarIngreso')">
                <i class="fa fa-plus"></i> Agregar nuevo lote
            </x-button>
            <x-button variant="success" wire:click="exportarExcel">
                <i class="fa fa-file-excel"></i> Exportar a Excel
            </x-button>
        </x-flex>
    </x-flex>

    <x-card x-data="{ abiertos: {} }">
        <x-flex>
            <div>
                <x-input type="number" label="Filtrar por lote" wire:model.live.debounce.500ms="lote" />
            </div>
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
            <div>
                <x-select label="Filtrar por venteado" wire:model.live="filtroVenteado">
                    <option value="">Todos</option>
                    <option value="conventeado">Con venteado</option>
                    <option value="sinventeado">Sin venteado</option>
                </x-select>
            </div>
            <div>
                <x-select label="Filtrar por filtrado" wire:model.live="filtroFiltrado">
                    <option value="">Todos</option>
                    <option value="confiltrado">Con Filtrado</option>
                    <option value="sinfiltrado">Sin Filtrado</option>
                </x-select>
            </div>
        </x-flex>

        <x-table class="mt-4">
            <x-slot name="thead">
                <x-tr>
                    <x-th class="text-center"></x-th>
                    <x-th class="text-center">Lote</x-th>
                    <x-th class="text-center">Fecha</x-th>
                    <x-th class="text-center">Campo</x-th>
                    <x-th class="text-center">Área</x-th>
                    <x-th class="text-center">Campaña</x-th>
                    <x-th class="text-center">Cultivo</x-th>
                    <x-th class="text-center">Fecha Siembra</x-th>
                    <x-th colspan="2" class="text-center">PROVEEDOR</x-th>
                    <x-th class="text-center">TOTAL KILOS</x-th>
                    <x-th class="text-center">OBS</x-th>
                    <x-th colspan="2" class="text-center">KILOS FINALES</x-th>
                    <x-th class="text-center">ACCIONES</x-th>
                </x-tr>
                <x-tr>
                    <x-th></x-th><x-th></x-th><x-th></x-th><x-th></x-th><x-th></x-th>
                    <x-th></x-th><x-th></x-th><x-th></x-th>
                    <x-th class="text-center">KG Expor.</x-th>
                    <x-th class="text-center">KG / HA</x-th>
                    <x-th></x-th><x-th></x-th>
                    <x-th class="text-center">Diferencia</x-th>
                    <x-th class="text-center">%</x-th>
                    <x-th></x-th>
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                @foreach ($cochinillaIngresos as $cochinillaIngreso)
                    <x-tr>
                        <x-td class="text-center">
                            <x-toggle-detalles-button :id="$cochinillaIngreso->id" />
                        </x-td>
                        <x-th class="text-center">{{ $cochinillaIngreso->lote }}</x-th>
                        <x-th class="text-center">{{ $cochinillaIngreso->fecha }}</x-th>
                        <x-th class="text-center">{{ $cochinillaIngreso->campo }}</x-th>
                        <x-th class="text-center">{{ $cochinillaIngreso->area }}</x-th>
                        <x-th class="text-center">{{ $cochinillaIngreso->campoCampania?->nombre_campania }}</x-th>
                        <x-th class="text-center">{{ $cochinillaIngreso->campoCampania?->variedad_tuna }}</x-th>
                        <x-th class="text-center">{{ formatear_fecha($cochinillaIngreso->fecha_siembra) }}</x-th>
                        <x-th class="text-center">{{ $cochinillaIngreso->filtrado123 }}</x-th>
                        <x-th class="text-center">{{ $cochinillaIngreso->filtrado123_x_ha }}</x-th>
                        <x-th class="text-center">{{ $cochinillaIngreso->total_kilos }}</x-th>
                        <x-th class="text-center">{{ $cochinillaIngreso->observacionRelacionada->descripcion }}</x-th>
                        <x-th class="text-center">{{ $cochinillaIngreso->diferencia_filtrado }}</x-th>
                        <x-th
                            class="text-center">{{ number_format($cochinillaIngreso->porcentaje_diferencia_filtrado, 2) }}%</x-th>
                        <x-th class="text-center">
                            <x-flex class="justify-center items-center">
                                <div class="gap-1 flex items-center">
                                    <x-badge-contador :cantidad="$cochinillaIngreso->venteados->count()" sufijo="v"
                                        color-class="bg-rose-200" />
                                    <x-badge-contador :cantidad="$cochinillaIngreso->filtrados->count()" sufijo="f"
                                        color-class="bg-lime-200" />
                                    <div class="">
                                        <x-cochinilla-acciones-dropdown :ingreso="$cochinillaIngreso" />
                                    </div>
                                </div>

                            </x-flex>
                        </x-th>
                    </x-tr>

                    {{-- SUBTABLA DE SUBLOTES (colapsada por defecto) --}}
                    <tr class="bg-muted" x-show="abiertos[{{ $cochinillaIngreso->id }}]"
                        x-transition:enter="transition-all duration-200 ease-out" x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100" x-transition:leave="transition-all duration-150 ease-in"
                        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display: none;">
                        <td colspan="15" class="p-0">
                            <table class="w-full text-xs border-t border-border">
                                <thead class="bg-card">
                                    <tr>
                                        <th class="p-2 text-center border-b border-border">Sub Lote</th>
                                        <th class="p-2 text-center border-b border-border">Fecha</th>
                                        <th class="p-2 text-center border-b border-border">Total Kilos</th>
                                        <th class="p-2 text-center border-b border-border">Observación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($cochinillaIngreso->detalles as $detalle)
                                        <tr class="border-b border-border hover:bg-card/60 transition-colors">
                                            <td class="p-2 text-center">{{ $detalle->sublote_codigo }}</td>
                                            <td class="p-2 text-center">{{ $detalle->fecha }}</td>
                                            <td class="p-2 text-center">{{ $detalle->total_kilos }}</td>
                                            <td class="p-2 text-center">{{ $detalle->observacionRelacionada->descripcion }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="p-2 text-center text-muted-foreground">
                                                Sin sublotes registrados
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </td>
                    </tr>
                @endforeach
            </x-slot>
        </x-table>
        <div class="my-4">
            {{ $cochinillaIngresos->links() }}
        </div>
    </x-card>
    <x-loading wire:loading />
</div>