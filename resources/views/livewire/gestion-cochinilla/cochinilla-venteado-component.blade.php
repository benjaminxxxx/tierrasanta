<div>
    <!--MODULO COCHINILLA VENTEADO FORMULARIO PRINCIPAL-->


    <x-flex>
        <x-card>
            <x-flex>
                <x-title>
                    Venteado de Cochinilla
                </x-title>
                <x-button @click="$wire.dispatch('agregarVenteado')">
                    <i class="fa fa-plus"></i> Agregar venteado
                </x-button>
            </x-flex>

            @include('livewire.gestion-cochinilla.partials.cochinilla-filtros')
            
            <x-table class="mt-4">
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
                        <x-th colspan="4" class="text-center">
                            PROCESO DE VENTEADO
                        </x-th>
                        <x-th colspan="3" class="text-center">
                            % VENTEADO
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
                            Limpia
                        </x-th>
                        <x-th class="text-center">
                            Basurra
                        </x-th>
                        <x-th class="text-center">
                            Polvillo
                        </x-th>
                        <x-th class="text-center">
                            Total
                        </x-th>
                        <x-th class="text-center">
                            Limpia
                        </x-th>
                        <x-th class="text-center">
                            Basurra
                        </x-th>
                        <x-th class="text-center">
                            Polvillo
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($cochinillaIngresos as $indice => $cochinillaIngreso)
                        @if ($cochinillaIngreso->venteados->count() > 0)
                            @foreach ($cochinillaIngreso->venteados as $cochinillaVenteado)
                                <x-tr>
                                    <x-td class="text-center text-red-600">
                                        {{ $cochinillaIngreso->lote }}
                                    </x-td>
                                    <x-td class="text-center">

                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaVenteado->fecha_proceso }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaIngreso->campo }}
                                    </x-td>
                                    <x-td class="text-center">

                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaVenteado->kilos_ingresado }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaVenteado->limpia }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaVenteado->basura }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaVenteado->polvillo }}
                                    </x-td>
                                    <x-td class="text-center">

                                    </x-td>
                                    <x-td class="text-center !text-blue-600">
                                        {{ number_format($cochinillaVenteado->porcentaje_limpia, 2) }}%
                                    </x-td>
                                    <x-td class="text-center !text-blue-600">
                                        {{ number_format($cochinillaVenteado->porcentaje_basura, 2) }}%
                                    </x-td>
                                    <x-td class="text-center !text-blue-600">
                                        {{ number_format($cochinillaVenteado->porcentaje_polvillo, 2) }}%
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
                                {{ $cochinillaIngreso->fecha_proceso_venteado }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->campo }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->total_kilos }}
                            </x-th>
                            <x-th class="text-center text-primary">
                                {{ number_format($cochinillaIngreso->total_venteado_kilos_ingresados, 2) }}
                            </x-th>
                            <x-th class="text-center text-primary">
                                {{ number_format($cochinillaIngreso->total_venteado_limpia, 2) }}
                            </x-th>
                            <x-th class="text-center text-primary">
                                {{ number_format($cochinillaIngreso->total_venteado_basura, 2) }}
                            </x-th>
                            <x-th class="text-center text-primary">
                                {{ number_format($cochinillaIngreso->total_venteado_polvillo, 2) }}
                            </x-th>
                            <x-th class="text-center text-primary">
                                {{ number_format($cochinillaIngreso->total_venteado_total, 2) }}
                            </x-th>
                            <x-th class="text-center !text-blue-600">
                                {{ number_format($cochinillaIngreso->porcentaje_venteado_limpia, 2) }}%
                            </x-th>
                            <x-th class="text-center !text-blue-600">
                                {{ number_format($cochinillaIngreso->porcentaje_venteado_basura, 2) }}%
                            </x-th>
                            <x-th class="text-center !text-blue-600">
                                {{ number_format($cochinillaIngreso->porcentaje_venteado_polvillo, 2) }}%
                            </x-th>
                            <x-th class="text-center">
                                {{ number_format($cochinillaIngreso->diferencia, 2) }}
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
                                                        @click="$wire.dispatch('agregarVenteado',{ingresoId:{{ $cochinillaIngreso->id }}})">
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
        </x-card>


    </x-flex>
    <x-loading wire:loading />
</div>
