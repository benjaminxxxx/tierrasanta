<div>
    <!--MODULO COCHINILLA FILTRADO FORMULARIO PRINCIPAL-->
    <x-card>
        <x-flex>
            <x-title>
                Filtrado de Cochinilla
            </x-title>
            <x-button @click="$wire.dispatch('agregarFiltrado')">
                <i class="fa fa-plus"></i> Agregar filtrado
            </x-button>
        </x-flex>
        <x-flex class="justify-between w-full mt-4">
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
                <x-toggle-switch :checked="$verLotesSinIngresos" label="Lotes sin ingresos" wire:model.live="verLotesSinIngresos" />
            </div>
        </x-flex>
        <div class="w-full mt-5">

            <!-- Table -->
            <x-table>
                <!-- Table header -->
                <x-slot name="thead">
                    <x-tr>
                        <x-th rowspan="2" class="text-center">
                            LOTE
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            FECHA DE PROCESO
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            KILOS INGRESADOS
                        </x-th>
                        <x-th colspan="6" class="text-center">
                            PROCESO DE FILTRADO
                        </x-th>
                        <x-th colspan="5" class="text-center">
                            % FILTRADO
                        </x-th>
                        <x-th class="text-center" rowspan="2">
                            ACCIONES
                        </x-th>
                    </x-tr>
                    <tr>
                        <x-th class="text-center">
                            PRIMERA
                        </x-th>
                        <x-th class="text-center">
                            SEGUNDA
                        </x-th>
                        <x-th class="text-center">
                            TERCERA
                        </x-th>
                        <x-th class="text-center">
                            PIEDRA
                        </x-th>
                        <x-th class="text-center">
                            BASURA
                        </x-th>
                        <x-th class="text-center">
                            TOTAL
                        </x-th>

                        <x-th class="text-center">
                            PRIMERA
                        </x-th>
                        <x-th class="text-center">
                            SEGUNDA
                        </x-th>
                        <x-th class="text-center">
                            TERCERA
                        </x-th>
                        <x-th class="text-center">
                            PIEDRA
                        </x-th>
                        <x-th class="text-center">
                            BASURA
                        </x-th>
                    </tr>
                </x-slot>
                <!-- Table body -->
                <x-slot name="tbody">
                    @foreach ($cochinillaFiltrados as $indice => $cochinillaFiltrado)
                        <x-tr>
                            <x-th class="text-center text-red-600">
                                {{ $cochinillaFiltrado->lote }}
                            </x-th>
                            <x-td class="text-center">
                                {{ $cochinillaFiltrado->fecha_proceso }}
                            </x-td>
                             <x-td class="text-center">
                                {{ $cochinillaFiltrado->kilos_ingresados }}
                            </x-td>
                            <x-td class="text-center text-primary">
                                {{ number_format($cochinillaFiltrado->primera, 2) }}
                            </x-td>
                            <x-td class="text-center text-primary">
                                {{ number_format($cochinillaFiltrado->segunda, 2) }}
                            </x-td>
                            <x-td class="text-center text-primary">
                                {{ number_format($cochinillaFiltrado->tercera, 2) }}
                            </x-td>
                            <x-td class="text-center text-primary">
                                {{ number_format($cochinillaFiltrado->piedra, 2) }}
                            </x-td>
                            <x-td class="text-center text-primary">
                                {{ number_format($cochinillaFiltrado->basura, 2) }}
                            </x-td>
                            <x-td class="text-center text-primary">
                                {{ number_format($cochinillaFiltrado->total, 2) }}
                            </x-td>
                            <x-td class="text-center text-primary">
                                {{ number_format($cochinillaFiltrado->porcentaje_primera, 2) }}%
                            </x-td>
                            <x-td class="text-center text-primary">
                                {{ number_format($cochinillaFiltrado->porcentaje_segunda, 2) }}%
                            </x-td>
                            <x-td class="text-center text-primary">
                                {{ number_format($cochinillaFiltrado->porcentaje_tercera, 2) }}%
                            </x-td>
                            <x-td class="text-center text-primary">
                                {{ number_format($cochinillaFiltrado->porcentaje_piedra, 2) }}%
                            </x-td>
                            <x-td class="text-center text-primary">
                                {{ number_format($cochinillaFiltrado->porcentaje_basura, 2) }}%
                            </x-td>
                            <x-td >
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
                                                    <x-dropdown-link class="text-center" wire:click="eliminarFiltrado({{ $cochinillaFiltrado->id }})">
                                                        <i class="fa fa-trash"></i> Eliminar filtrado
                                                    </x-dropdown-link>
                                                </div>
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                </x-flex>
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
        </div>

        <div class="my-4">
            {{ $cochinillaFiltrados->links() }}
        </div>
    </x-card>

    <x-loading wire:loading />
</div>
