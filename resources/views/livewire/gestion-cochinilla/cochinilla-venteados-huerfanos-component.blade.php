<div>
    <!--MODULO COCHINILLA VENTEADO FORMULARIO PRINCIPAL-->
    <x-card>
        <x-flex>
            <x-title>
                Venteado de Cochinilla
            </x-title>
            <x-button @click="$wire.dispatch('agregarVenteado')">
                <i class="fa fa-plus"></i> Agregar Venteado
            </x-button>
        </x-flex>
        
        @include('livewire.gestion-cochinilla.partials.cochinilla-filtros')
        
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
                        <x-th colspan="4" class="text-center">
                            PROCESO DE FILTRADO
                        </x-th>
                        <x-th class="text-center" rowspan="2">
                            ACCIONES
                        </x-th>
                    </x-tr>
                    <tr>
                        <x-th class="text-center">
                            LIMPIA
                        </x-th>
                        <x-th class="text-center">
                            BASURA
                        </x-th>
                        <x-th class="text-center">
                            POLVILLO
                        </x-th>
                        <x-th class="text-center">
                            TOTAL
                        </x-th>
                    </tr>
                </x-slot>
                <!-- Table body -->
                <x-slot name="tbody">
                    @foreach ($cochinillaVenteados as $indice => $cochinillaVenteado)
                        <x-tr>
                            <x-th class="text-center text-red-600">
                                {{ $cochinillaVenteado->lote }}
                            </x-th>
                            <x-td class="text-center">
                                {{ $cochinillaVenteado->fecha_proceso }}
                            </x-td>
                             <x-td class="text-center">
                                {{ $cochinillaVenteado->kilos_ingresado }}
                            </x-td>
                            <x-td class="text-center text-blue-400 dark:text-indigo-500">
                                {{ number_format($cochinillaVenteado->limpia, 2) }}
                            </x-td>
                            <x-td class="text-center text-blue-400 dark:text-indigo-500">
                                {{ number_format($cochinillaVenteado->basura, 2) }}
                            </x-td>
                            <x-td class="text-center text-blue-400 dark:text-indigo-500">
                                {{ number_format($cochinillaVenteado->polvillo, 2) }}
                            </x-td>
                            <x-td class="text-center text-blue-400 dark:text-indigo-500">
                                {{ number_format($cochinillaVenteado->total, 2) }}
                            </x-td>
                            <x-td class="text-center">
                                <x-flex class="justify-center">
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
                                                    <x-dropdown-link class="text-center" wire:click="eliminarVenteado({{ $cochinillaVenteado->id }})">
                                                        <i class="fa fa-trash"></i> Eliminar Venteado
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
            {{ $cochinillaVenteados->links() }}
        </div>
    </x-card>

    <x-loading wire:loading />
</div>
