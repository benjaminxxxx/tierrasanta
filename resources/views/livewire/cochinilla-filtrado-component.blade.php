<div>
    <!--MODULO COCHINILLA FILTRADO FORMULARIO PRINCIPAL-->
    <x-loading wire:loading />

    <!-- #region HEADER-->
    <x-flex>
        <x-h3>
            Filtrado de Cochinilla
        </x-h3>
        <x-button @click="$wire.dispatch('agregarFiltrado')">
            <i class="fa fa-plus"></i> Agregar filtrado
        </x-button>
    </x-flex>
    <!-- #endregion HEADER-->
    
    <!-- #region FILTRO-->
    <x-card class="mt-3">
        <x-spacing>
            <x-flex class="justify-between w-full">
                <x-flex>
                    <div>
                        <x-input-number label="Filtrar por lote" wire:model.live="lote" />
                    </div>
                    <div>
                        <x-select label="Filtrar por año" wire:model.live="anioSeleccionado">
                            <option value="">Todos los años</option>
                            @foreach ($aniosDisponibles as $anioDisponible)
                                <option value="{{ $anioDisponible }}">{{ $anioDisponible }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    @if (!$verLotesSinIngresos)
                        <div>
                            <x-select-campo label="Filtrar por Campo" wire:model.live="campoSeleccionado" />
                        </div>
                    @endif
                </x-flex>
                <div>
                    <x-toggle-switch :checked="$verLotesSinIngresos" label="Lotes sin ingresos"
                        wire:model.live="verLotesSinIngresos" />
                </div>
            </x-flex>
        </x-spacing>
    </x-card>
    <!-- #endregion FILTRO-->
    
    <x-card class="mt-3">
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th rowspan="2" class="text-center">
                            Lote
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Fecha de ingreso
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Fecha de proceso
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Campo
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Kilos totales
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Kilos ingresados
                        </x-th>
                        <x-th colspan="6" class="text-center">
                            PROCESO DE FILTRADO
                        </x-th>
                        <x-th colspan="5" class="text-center">
                            % FILTRADO
                        </x-th>

                        <x-th rowspan="2" class="text-center">
                            Diferencia
                        </x-th>
                        <x-th class="text-center" rowspan="2">
                            ACCIONES
                        </x-th>
                    </x-tr>
                    <x-tr>
                        <x-th class="text-center">
                            Primera
                        </x-th>
                        <x-th class="text-center">
                            Segunda
                        </x-th>
                        <x-th class="text-center">
                            Tercera
                        </x-th>
                        <x-th class="text-center">
                            Piedra
                        </x-th>
                        <x-th class="text-center">
                            Basura
                        </x-th>
                        <x-th class="text-center">
                            Total
                        </x-th>
                        <x-th class="text-center">
                            Primera
                        </x-th>
                        <x-th class="text-center">
                            Segunda
                        </x-th>
                        <x-th class="text-center">
                            Tercera
                        </x-th>
                        <x-th class="text-center">
                            Piedra
                        </x-th>
                        <x-th class="text-center">
                            Basura
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($cochinillaIngresos as $indice => $cochinillaIngreso)
                        @if ($cochinillaIngreso->filtrados->count() > 0)
                            @foreach ($cochinillaIngreso->filtrados as $cochinillaFiltrado)
                                <x-tr>
                                    <x-td class="text-center text-red-600">
                                        {{ $cochinillaIngreso->lote }}
                                    </x-td>
                                    <x-td class="text-center">

                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaFiltrado->fecha_proceso }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaIngreso->campo }}
                                    </x-td>
                                    <x-td class="text-center">

                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaFiltrado->kilos_ingresados }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaFiltrado->primera }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaFiltrado->segunda }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaFiltrado->tercera }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaFiltrado->piedra }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaFiltrado->basura }}
                                    </x-td>
                                    <x-td class="text-center">

                                    </x-td>
                                    <x-td class="text-center !text-blue-600">
                                        {{ number_format($cochinillaFiltrado->porcentaje_primera, 2) }}%
                                    </x-td>
                                    <x-td class="text-center !text-blue-600">
                                        {{ number_format($cochinillaFiltrado->porcentaje_segunda, 2) }}%
                                    </x-td>
                                    <x-td class="text-center !text-blue-600">
                                        {{ number_format($cochinillaFiltrado->porcentaje_tercera, 2) }}%
                                    </x-td>
                                    <x-td class="text-center !text-blue-600">
                                        {{ number_format($cochinillaFiltrado->porcentaje_piedra, 2) }}%
                                    </x-td>
                                    <x-td class="text-center !text-blue-600">
                                        {{ number_format($cochinillaFiltrado->porcentaje_basura, 2) }}%
                                    </x-td>
                                    <x-td class="text-center">

                                    </x-td>
                                    <x-td class="text-center">

                                    </x-td>
                                </x-tr>
                            @endforeach
                        @endif
                        <x-tr>
                            <x-th class="text-center text-red-600">
                                {{ $cochinillaIngreso->lote }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->fecha }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->fecha_proceso_filtrado }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->campo }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->total_kilos }}
                            </x-th>
                            <x-th class="text-center text-primary">
                                {{ number_format($cochinillaIngreso->total_filtrado_kilos_ingresados, 2) }}
                            </x-th>
                            <x-th class="text-center text-primary">
                                {{ number_format($cochinillaIngreso->total_filtrado_primera, 2) }}
                            </x-th>
                            <x-th class="text-center text-primary">
                                {{ number_format($cochinillaIngreso->total_filtrado_segunda, 2) }}
                            </x-th>
                            <x-th class="text-center text-primary">
                                {{ number_format($cochinillaIngreso->total_filtrado_tercera, 2) }}
                            </x-th>
                            <x-th class="text-center text-primary">
                                {{ number_format($cochinillaIngreso->total_filtrado_piedra, 2) }}
                            </x-th>
                            <x-th class="text-center text-primary">
                                {{ number_format($cochinillaIngreso->total_filtrado_basura, 2) }}
                            </x-th>
                            <x-th class="text-center text-primary">
                                {{ number_format($cochinillaIngreso->total_filtrado_total, 2) }}
                            </x-th>
                            <x-th class="text-center !text-blue-600">
                                {{ number_format($cochinillaIngreso->porcentaje_filtrado_primera, 2) }}%
                            </x-th>
                            <x-th class="text-center !text-blue-600">
                                {{ number_format($cochinillaIngreso->porcentaje_filtrado_segunda, 2) }}%
                            </x-th>
                            <x-th class="text-center !text-blue-600">
                                {{ number_format($cochinillaIngreso->porcentaje_filtrado_tercera, 2) }}%
                            </x-th>
                            <x-th class="text-center !text-blue-600">
                                {{ number_format($cochinillaIngreso->porcentaje_filtrado_piedra, 2) }}%
                            </x-th>
                            <x-th class="text-center !text-blue-600">
                                {{ number_format($cochinillaIngreso->porcentaje_filtrado_basura, 2) }}%
                            </x-th>
                            <x-th class="text-center">
                                {{ number_format($cochinillaIngreso->diferencia_filtrado, 2) }}
                            </x-th>
                            <x-th class="text-center">
                                <x-flex>
                                    <div class="ms-3 relative">
                                        <x-dropdown align="right" width="60">
                                            <x-slot name="trigger">
                                                <span class="inline-flex rounded-md">
                                                    <button type="button"
                                                        class="inline-flex items-center px-3 py-2 border border-transparent leading-4 font-medium rounded-md dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                                        Acciones

                                                        <svg class="ms-2 -me-0.5 h-4 w-4"
                                                            xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </x-slot>

                                            <x-slot name="content">
                                                <div class="w-60">
                                                    <x-dropdown-link class="text-center"
                                                        @click="$wire.dispatch('agregarFiltrado',{ingresoId:{{ $cochinillaIngreso->id }}})">
                                                        <i class="fa fa-edit"></i> Editar
                                                    </x-dropdown-link>
                                                </div>
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                </x-flex>
                            </x-th>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
            <div class="my-4">
                {{ $cochinillaIngresos->links() }}
            </div>
        </x-spacing>
    </x-card>
</div>
