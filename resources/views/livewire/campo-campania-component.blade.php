<div>
    <x-loading wire:loading />

    <x-h3 class="mb-4">
        Camapañas por Campo
    </x-h3>
    <x-card>
        <x-spacing>
            <x-flex class="!items-end">
                <div>
                    <x-label class="mb-2">
                        Seleccionar el Campo
                    </x-label>
                    <x-select wire:model.live="campoSeleccionado">
                        <option value="">Seleccione un Campo</option>
                        @foreach ($campos as $campo)
                            <option value="{{ $campo->nombre }}">{{ $campo->nombre }}</option>
                        @endforeach
                    </x-select>
                </div>
                @if ($campoSeleccionado)
                    <x-button @click="$wire.dispatch('registroCampania',{campoNombre:'{{ $campoSeleccionado }}'})">
                        <i class="fa fa-plus"></i> Crear campaña
                    </x-button>
                @endif

            </x-flex>
        </x-spacing>
    </x-card>
    @if ($campanias)
        <x-card class="mt-4">
            <x-spacing>
                @if ($campanias->count() > 0)
                    <ol class="relative border-s border-gray-200 dark:border-gray-700">
                        @foreach ($campanias as $campania)
                            <li class="mb-10 ms-6">
                                <span
                                    class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -start-3 ring-8 ring-white dark:ring-gray-900 dark:bg-blue-900">
                                    <i class="fas fa-calendar-minus"></i>
                                </span>
                                <div class="md:flex justify-between">
                                    <div>
                                        <h3
                                            class="flex items-center mb-1 text-lg font-semibold text-gray-900 dark:text-white">
                                            Campaña {{ $campania->nombre_campania }}
                                        </h3>
                                        <time
                                            class="block mb-2 text-sm font-normal leading-none text-gray-400 dark:text-gray-500">Vigencia
                                            desde {{ $campania->fecha_vigencia }}</time>
                                        <ul class="text-base font-normal text-gray-500 dark:text-gray-400 my-3">
                                            <li>FECHA DE INICIO: {{ $campania->fecha_inicio }}</li>
                                            <li>FECHA DE FINALIZACIÓN: {{ $campania->fecha_fin }}</li>
                                        </ul>
                                        <div>
                                            <x-table>
                                                <x-slot name="thead">
                                                    <x-tr>
                                                        <x-th class="text-center">N°</x-th>
                                                        <x-th>Descripción</x-th>
                                                        <x-th class="text-center">Monto total</x-th>
                                                        <x-th class="text-center">Reporte Generado</x-th>
                                                    </x-tr>
                                                </x-slot>
                                                <x-slot name="tbody">
                                                    <x-tr>
                                                        <x-td class="text-center">1</x-td>
                                                        <x-td>GASTO PLANILLA</x-td>
                                                        <x-td class="text-right">{{ $campania->fecha_inicio }}</x-td>
                                                        <x-td class="text-center">-</x-td>
                                                    </x-tr>
                                                    <x-tr>
                                                        <x-td class="text-center">2</x-td>
                                                        <x-td>GASTO CUADRILLA</x-td>
                                                        <x-td class="text-right">{{ $campania->gasto_cuadrilla }}</x-td>
                                                        <x-td class="text-center">-</x-td>
                                                    </x-tr>
                                                    @foreach ($campania->listaConsumo as $indice => $consumo)
                                                        <x-tr>
                                                            <x-td class="text-center">{{ $indice + 3 }}</x-td>
                                                            <x-td>CONSUMOS {{ $consumo['categoria'] }}</x-td>
                                                            <x-td class="text-right">{{ $consumo['monto'] }}</x-td>
                                                            <x-td class="text-center">
                                                                @if ($consumo['reporte_file'])
                                                                    <x-button-a href="{{Storage::disk('public')->url($consumo['reporte_file'])}}">
                                                                        <i class="fa fa-file-excel"></i> Ver informe
                                                                    </x-button-a>
                                                                @else
                                                                    <p>-</p>
                                                                @endif
                                                            </x-td>
                                                        </x-tr>
                                                    @endforeach
                                                </x-slot>
                                            </x-table>
                                        </div>

                                        <x-button type="button"
                                            wire:click="actualizarGastosConsumo({{ $campania->id }})" class="mt-4">
                                            <i class="fa fa-refresh"></i> Actualizar Gastos y Consumos
                                        </x-button>
                                    </div>
                                    <div class="mt-3 md:mt-0">
                                        <x-danger-button wire:click="eliminarCampania({{ $campania->id }})">
                                            Eliminar Campaña
                                        </x-danger-button>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <x-warning>
                        <p>No hay ninguna campaña registrada para este campo.</p>
                    </x-warning>
                @endif
            </x-spacing>
        </x-card>
    @endif
</div>
