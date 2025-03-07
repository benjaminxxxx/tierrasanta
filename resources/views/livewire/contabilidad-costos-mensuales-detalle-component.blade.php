<div>
    <x-loading wire:loading />
    <div class="md:flex gap-5">
        <div class="w-full md:w-[26rem]">
            <x-h3 class="my-5">
                Campos
            </x-h3>
            <x-card>
                <x-spacing>
                    <x-success-button type="button" wire:click="importarMesAnterior" class="mb-5 w-full">
                        Importar Mes Anterior
                    </x-success-button>
                    <x-table>

                        <x-slot name="thead">
                            <x-tr>
                                <x-th class="text-center">Lotes en<br /> Producción</x-th>
                                <x-th class="text-center">Lote</x-th>
                                <x-th class="text-center">Área</x-th>
                                <x-th class="text-center">Campaña</x-th>
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @foreach ($campos as $campo)
                                <x-tr>
                                    <x-td class="text-center">
                                        <input type="checkbox"
                                            wire:model.live="campoSeleccionado.{{ $campo['nombre'] }}"
                                            class="form-checkbox text-primary">
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campo['nombre'] }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campo['area'] }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campo['campania'] }}
                                    </x-td>
                                </x-tr>
                            @endforeach
                        </x-slot>
                    </x-table>



                    <x-success-button type="button" wire:click="importarMesAnterior" class="mt-5 w-full">
                        Importar Mes Anterior
                    </x-success-button>
                </x-spacing>
            </x-card>
        </div>

        <div class="flex-1">
            <x-h3 class="my-5">
                Costo Fijo
            </x-h3>
            @php
                $costos_fijos = [
                    ['codigo' => 'fijo_administrativo', 'nombre' => 'Costo Administrativo'],
                    ['codigo' => 'fijo_financiero', 'nombre' => 'Costo Financiero'],
                    ['codigo' => 'fijo_gastos_oficina', 'nombre' => 'Gastos de Oficina'],
                    ['codigo' => 'fijo_depreciaciones', 'nombre' => 'Depreciaciones'],
                    ['codigo' => 'fijo_terreno', 'nombre' => 'Costo Terreno'],
                ];

                $costos_operativos = [
                    ['codigo' => 'operativo_servicios_fundo', 'nombre' => 'Servicios Fundo'],
                    ['codigo' => 'operativo_mano_obra_indirecta', 'nombre' => 'Mano de Obra Indirecta'],
                ];
            @endphp

            <x-card>
                <x-spacing>
                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th>N°</x-th>
                                <x-th>Descripción</x-th>
                                <x-th class="text-right">Costo Blanco</x-th>
                                <x-th class="text-right">Costo Negro</x-th>
                                <x-th class="text-right">Costo Blanco+Negro</x-th>
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @foreach ($costos_fijos as $indice => $costo)
                                <x-tr>
                                    <x-td>{{ $indice + 1 }}</x-td>
                                    <x-td>{{ $costo['nombre'] }}</x-td>
                                    <x-td class="text-right">
                                        @if ($modoEdicion)
                                            <x-input class="!w-32 text-right" type="number"
                                                wire:model="{{ $costo['codigo'] }}_costo_blanco"
                                                @focus="$el.select()" />
                                        @else
                                            S/
                                            {{ number_format($costos_mensuales[$costo['codigo']]['costo_blanco'] ?? 0, 2) }}
                                        @endif
                                    </x-td>
                                    <x-td class="text-right">
                                        @if ($modoEdicion)
                                            <x-input class="!w-32 text-right" type="number"
                                                wire:model="{{ $costo['codigo'] }}_costo_negro" @focus="$el.select()" />
                                        @else
                                            S/
                                            {{ number_format($costos_mensuales[$costo['codigo']]['costo_negro'] ?? 0, 2) }}
                                        @endif
                                    </x-td>
                                    <x-td class="text-right font-bold">
                                        S/
                                        {{ number_format(($costos_mensuales[$costo['codigo']]['costo_blanco'] ?? 0) + ($costos_mensuales[$costo['codigo']]['costo_negro'] ?? 0), 2) }}
                                    </x-td>
                                </x-tr>
                            @endforeach
                        </x-slot>
                    </x-table>
                </x-spacing>
            </x-card>
            <x-h3 class="my-5">
                Costo Operativo
            </x-h3>
            <x-card>
                <x-spacing>
                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th>N°</x-th>
                                <x-th>Descripción</x-th>
                                <x-th class="text-right">Costo Blanco</x-th>
                                <x-th class="text-right">Costo Negro</x-th>
                                <x-th class="text-right">Costo Blanco+Negro</x-th>
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @foreach ($costos_operativos as $indice => $costo)
                                <x-tr>
                                    <x-td>{{ $indice + 1 }}</x-td>
                                    <x-td>{{ $costo['nombre'] }}</x-td>
                                    <x-td class="text-right">
                                        @if ($modoEdicion)
                                            <x-input class="!w-32 text-right" type="number"
                                                wire:model="{{ $costo['codigo'] }}_costo_blanco"
                                                @focus="$el.select()" />
                                        @else
                                            S/
                                            {{ number_format($costos_mensuales[$costo['codigo']]['costo_blanco'] ?? 0, 2) }}
                                        @endif
                                    </x-td>
                                    <x-td class="text-right">
                                        @if ($modoEdicion)
                                            <x-input class="!w-32 text-right" type="number"
                                                wire:model="{{ $costo['codigo'] }}_costo_negro"
                                                @focus="$el.select()" />
                                        @else
                                            S/
                                            {{ number_format($costos_mensuales[$costo['codigo']]['costo_negro'] ?? 0, 2) }}
                                        @endif
                                    </x-td>
                                    <x-td class="text-right font-bold">
                                        S/
                                        {{ number_format(($costos_mensuales[$costo['codigo']]['costo_blanco'] ?? 0) + ($costos_mensuales[$costo['codigo']]['costo_negro'] ?? 0), 2) }}
                                    </x-td>
                                </x-tr>
                            @endforeach
                        </x-slot>
                    </x-table>
                </x-spacing>
            </x-card>
            <x-h3 class="my-5">
                Información Mensual
            </x-h3>
            <x-card>
                <x-spacing>
                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th>Descripción</x-th>
                                <x-th class="text-right">Valor</x-th>
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            <x-tr>
                                <x-td>Número de Campos Activos</x-td>
                                <x-td class="text-right">{{ $campos->where('activo', true)->count() }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td>Área Total de Producción</x-td>
                                <x-td class="text-right">{{ number_format($areaProduccion, 3) }}</x-td>
                            </x-tr>
                        </x-slot>
                    </x-table>
                </x-spacing>
            </x-card>

            <x-card class="my-5">
                <x-spacing>
                    <x-flex class="w-full justify-end gap-3">
                        @if ($modoEdicion)
                            <x-secondary-button type="button" wire:click="cancelarEdicion">
                                Cancelar
                            </x-secondary-button>
                            <x-button type="button" wire:click="guardarCambios">
                                <i class="fa fa-save"></i> Guardar Costos
                            </x-button>
                        @else
                            <x-button type="button" wire:click="editarCostos">
                                <i class="fa fa-edit"></i> Editar Costos
                            </x-button>
                        @endif

                    </x-flex>
                    <x-flex class="w-full justify-end gap-3 mt-3">
                        @if (!$modoEdicion)
                            <x-input type="date" wire:model="fechaDistribucion" class="!w-auto !bg-gray-200 cursor-not-allowed" disabled />
                            <x-secondary-button type="button" wire:click="distribuirCostoFijo">
                                <i class="fa fa-list"></i> Distribuir Costo Fijo
                            </x-secondary-button>
                            <x-secondary-button type="button" wire:click="distribuirCostoOperativo">
                                <i class="fa fa-list"></i> Distribuir Costo Operativo
                            </x-secondary-button>
                        @endif

                    </x-flex>
                </x-spacing>
            </x-card>
        </div>
    </div>
</div>
