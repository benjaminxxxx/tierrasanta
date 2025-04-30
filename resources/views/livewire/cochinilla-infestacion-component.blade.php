<div>


    <!-- #region HEADER-->

    <!--MODULO COCHINILLA INFESTACIONES FORMULARIO PRINCIPAL-->
    <x-loading wire:loading />

    <x-flex>
        <x-h3>
            Infestación de Cochinilla
        </x-h3>
        <x-button @click="$wire.dispatch('agregarInfestacion')">
            <i class="fa fa-plus"></i> Agregar registro
        </x-button>
    </x-flex>
    <!-- #endregion HEADER-->

    <!-- #region FILTRO-->
    <x-card class="mt-3">
        <x-spacing>
            <x-flex class="justify-between w-full">
                <x-flex>
                    <div>
                        <x-select label="Filtrar por tipo" wire:model.live="tipoSeleccionado">
                            <option value="">Todos los tipos</option>
                            <option value="infestacion">Infestación</option>
                            <option value="reinfestacion">Reinfestación</option>
                        </x-select>
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
                        <x-select-campo label="Filtrar por campo de destino" wire:model.live="campoSeleccionado" />
                    </div>
                    <div>
                        <x-select-campo label="Filtrar por campo de origen" wire:model.live="campoSeleccionadoOrigen" />
                    </div>
                </x-flex>
                <x-flex>
                    <x-label for="filtrarCarton">
                        <x-checkbox id="filtrarCarton" wire:model.live="filtrarCarton" /> Ver cartón
                    </x-label>
                    <x-label for="filtrarTubo">
                        <x-checkbox id="filtrarTubo" wire:model.live="filtrarTubo" /> Ver tubo
                    </x-label>
                    <x-label for="filtrarMalla">
                        <x-checkbox id="filtrarMalla" wire:model.live="filtrarMalla" /> Ver malla
                    </x-label>
                </x-flex>
            </x-flex>
        </x-spacing>
    </x-card>
    <!-- #endregion FILTRO-->

    <!-- #region TABLA INFESTACION -->
    <x-card class="mt-3">
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th rowspan="2" class="text-center">
                            Tipo de Infestación
                        </x-th>
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
                            KG Madres
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Madres / HA.
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Del campo
                        </x-th>
                        @if ($filtrarCarton)
                            <x-th colspan="5" class="text-center">
                                INFESTADORES CARTONES
                            </x-th>
                        @endif
                        @if ($filtrarTubo)
                            <x-th colspan="5" class="text-center">
                                INFESTADORES TUBOS
                            </x-th>
                        @endif
                        @if ($filtrarMalla)
                            <x-th colspan="5" class="text-center">
                                INFESTADORES MALLA
                            </x-th>
                        @endif
                        <x-th class="text-center" rowspan="2">
                            ACCIONES
                        </x-th>
                    </x-tr>
                    <x-tr>
                        @if ($filtrarCarton)
                            <x-th class="text-center">
                                Capacidad x caja
                            </x-th>
                            <x-th class="text-center">
                                N° de cajas
                            </x-th>
                            <x-th class="text-center">
                                Infestadores
                            </x-th>
                            <x-th class="text-center">
                                Madres / Infes.
                            </x-th>
                            <x-th class="text-center">
                                Infes. / Ha.
                            </x-th>
                        @endif
                        @if ($filtrarTubo)
                            <x-th class="text-center">
                                Capacidad x caja
                            </x-th>
                            <x-th class="text-center">
                                N° de cajas
                            </x-th>
                            <x-th class="text-center">
                                Tubos
                            </x-th>
                            <x-th class="text-center">
                                Madres / tubos.
                            </x-th>
                            <x-th class="text-center">
                                Tubos. / Ha.
                            </x-th>
                        @endif
                        @if ($filtrarMalla)
                            <x-th class="text-center">
                                Capacidad x bolsa
                            </x-th>
                            <x-th class="text-center">
                                N° de bolsas
                            </x-th>
                            <x-th class="text-center">
                                Mallas
                            </x-th>
                            <x-th class="text-center">
                                Mallas / Infes.
                            </x-th>
                            <x-th class="text-center">
                                Mallas. / Ha.
                            </x-th>
                        @endif
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($cochinillaInfestaciones as $indice => $cochinillaInfestacion)
                        <x-tr class="{{$nuevoRegistro==$cochinillaInfestacion->id?'!bg-blue-200':''}}">
                            <x-th class="text-center text-red-600">
                                {{ $cochinillaInfestacion->tipo_infestacion }}
                            </x-th>
                            <x-td class="text-center">
                                {{ $cochinillaInfestacion->fecha }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $cochinillaInfestacion->campo_nombre }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $cochinillaInfestacion->area }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $cochinillaInfestacion->campoCampania?->nombre_campania }}
                            </x-td>
                            <x-td class="text-center text-primary">
                                {{ $cochinillaInfestacion->kg_madres }}
                            </x-td>
                            <x-td class="text-center text-primary">
                                {{ number_format($cochinillaInfestacion->kg_madres_por_ha, 2) }}
                            </x-td>
                            <x-td class="text-center text-primary">
                                {{ $cochinillaInfestacion->campo_origen_nombre }}
                            </x-td>
                            {{-- CARTON --}}
                            @if ($filtrarCarton)
                                <x-td class="text-center bg-yellow-100">
                                    {{ $cochinillaInfestacion->carton_capacidad_envase }}
                                </x-td>
                                <x-td class="text-center bg-yellow-100">
                                    {{ $cochinillaInfestacion->carton_numero_envases }}
                                </x-td>
                                <x-td class="text-center bg-yellow-100">
                                    {{ $cochinillaInfestacion->carton_infestadores }}
                                </x-td>
                                <x-td class="text-center bg-yellow-100">
                                    {{ number_format($cochinillaInfestacion->carton_madres_por_infestador, 2) }}
                                </x-td>
                                <x-td class="text-center bg-yellow-100">
                                    {{ number_format($cochinillaInfestacion->carton_infestadores_por_ha, 2) }}
                                </x-td>
                            @endif


                            {{-- TUBO --}}
                            @if ($filtrarTubo)
                                <x-td class="text-center bg-purple-100">
                                    {{ $cochinillaInfestacion->tubo_capacidad_envase }}
                                </x-td>
                                <x-td class="text-center bg-purple-100">
                                    {{ $cochinillaInfestacion->tubo_numero_envases }}
                                </x-td>
                                <x-td class="text-center bg-purple-100">
                                    {{ $cochinillaInfestacion->tubo_infestadores }}
                                </x-td>
                                <x-td class="text-center bg-purple-100">
                                    {{ number_format($cochinillaInfestacion->tubo_madres_por_infestador, 2) }}
                                </x-td>
                                <x-td class="text-center bg-purple-100">
                                    {{ number_format($cochinillaInfestacion->tubo_infestadores_por_ha, 2) }}
                                </x-td>
                            @endif


                            {{-- MALLA --}}
                            @if ($filtrarMalla)
                                <x-td class="text-center bg-blue-100">
                                    {{ $cochinillaInfestacion->malla_capacidad_envase }}
                                </x-td>
                                <x-td class="text-center bg-blue-100">
                                    {{ $cochinillaInfestacion->malla_numero_envases }}
                                </x-td>
                                <x-td class="text-center bg-blue-100">
                                    {{ $cochinillaInfestacion->malla_infestadores }}
                                </x-td>
                                <x-td class="text-center bg-blue-100">
                                    {{ number_format($cochinillaInfestacion->malla_madres_por_infestador, 2) }}
                                </x-td>
                                <x-td class="text-center bg-blue-100">
                                    {{ number_format($cochinillaInfestacion->malla_infestadores_por_ha, 2) }}
                                </x-td>
                            @endif


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
                                                        @click="$wire.dispatch('editarInfestacion',{cochinillaInfestacionId:{{ $cochinillaInfestacion->id }}})">
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
                {{ $cochinillaInfestaciones->links() }}
            </div>
        </x-spacing>
    </x-card>
    <!-- #endregion -->

</div>
