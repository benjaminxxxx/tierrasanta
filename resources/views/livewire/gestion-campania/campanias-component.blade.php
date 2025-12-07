<div>
    <x-loading wire:loading />

    <x-flex>
        <x-h3>
            Campañas - Resumen General
        </x-h3>
        <x-button @click="$wire.dispatch('registroCampania')">
            <i class="fa fa-plus"></i> Registrar nueva campaña
        </x-button>
    </x-flex>
    <x-card2 class="mt-4">
        <x-flex class="justify-between">
            <x-flex>
                <x-select-campo label="Filtrar por Campo" wire:model.live="campoSeleccionado" />

                @if (is_array($campanias) && count($campanias) > 0)
                    <x-select wire:model.live="campaniaSeleccionada" label="Seleccionar Campaña">
                        <option value="">Elegir Campaña</option>
                        @foreach ($campanias as $campaniaId => $campaniaNombre)
                            <option value="{{ $campaniaId }}">{{ $campaniaNombre }}</option>
                        @endforeach
                    </x-select>

                @endif

            </x-flex>
            <x-flex>
                <x-button variant="success" wire:click="descargarReporteCampania">
                    <i class="fa fa-file-excel"></i> Descargar Reporte
                </x-button>
            </x-flex>
        </x-flex>
    </x-card2>
    <div class="">
        <table
            class="mt-3 p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <x-tr>

                    <x-th class="text-center" rowspan="2">
                        N°
                    </x-th>
                    <x-th class="text-center" rowspan="2">
                        Acciones
                    </x-th>
                    <x-th class="text-center" rowspan="2">
                        Campaña
                    </x-th>
                    <x-th class="text-center" rowspan="2">
                        Campo
                    </x-th>
                    <x-th class="text-center" rowspan="2">
                        Área
                    </x-th>
                    <x-th class="text-center" rowspan="2">
                        Siembra
                    </x-th>
                    <x-th class="text-center" rowspan="2">
                        Inicio de Campaña
                    </x-th>
                    <x-th class="text-center" rowspan="2">
                        Fin de Campaña
                    </x-th>
                    <!-- ------------------------------- -->
                    <!-- BLOQUE: POBLACIÓN PLANTAS -->
                    <!-- ------------------------------- -->
                    <x-th class="text-center bg-amber-600 text-white" colspan="4">
                        Población de Plantas
                    </x-th>

                    <!-- ------------------------------- -->
                    <!-- BLOQUE: BROTES POR PISO -->
                    <!-- ------------------------------- -->
                    <x-th class="text-center bg-green-700 text-white" colspan="7">
                        Brotes por Piso
                    </x-th>

                    <!-- ------------------------------- -->
                    <!-- BLOQUE: INFESTACION -->
                    <!-- ------------------------------- -->
                    <x-th class="text-center bg-blue-700 text-white" colspan="19">
                        Infestación
                    </x-th>
                </x-tr>
                <x-tr>
                    <x-th class="text-center bg-amber-600 text-white">
                        Fecha de evaluación día cero
                    </x-th>
                    <x-th class="text-center bg-amber-600 text-white">
                        Nª de pencas madre día cero
                    </x-th>
                    <x-th class="text-center bg-amber-600 text-white">
                        Fecha de evaluación resiembra
                    </x-th>
                    <x-th class="text-center bg-amber-600 text-white">
                        Nª de pencas madre después de resiembra
                    </x-th>
                    <!-- -------- SUBCOLUMNAS BROTES POR PISO -------- -->

                    <x-th class="text-center bg-green-700 text-white">
                        Fecha evaluación brotes por piso
                    </x-th>

                    <x-th class="text-center bg-green-700 text-white">
                        Actual brotes aptos 2° piso
                    </x-th>

                    <x-th class="text-center bg-green-700 text-white">
                        Brotes 2° piso después de N días
                    </x-th>

                    <x-th class="text-center bg-green-700 text-white">
                        Actual brotes aptos 3° piso
                    </x-th>

                    <x-th class="text-center bg-green-700 text-white">
                        Brotes 3° piso después de N días
                    </x-th>

                    <x-th class="text-center bg-green-700 text-white">
                        Total actual brotes 2° + 3° piso
                    </x-th>

                    <x-th class="text-center bg-green-700 text-white">
                        Total brotes 2° + 3° piso después de N días
                    </x-th>

                    <!-- -------- SUBCOLUMNAS INFESTACIÓN -------- -->

                    <x-th class="text-center bg-blue-700 text-white">
                        Fecha de infestación
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Tipo de infestador
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Nº de infestadores
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Kg de mamá
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Nº de pencas
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Nº infestadores x penca
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Grs de cochinilla mamá x infestador
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Tiempo de inicio a infestación
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Kg de nitrógeno de inicio a infestación
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Kg de fósforo de inicio a infestación
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Kg de potasio de inicio a infestación
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Kg de calcio de inicio a infestación
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Kg de magnesio de inicio a infestación
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Kg de manganeso de inicio a infestestación
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Kg de zinc de inicio a infestación
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Kg de fierro de inicio a infestación
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Lt de SalTrad de inicio a infestación
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        m³ de agua desde inicio a infestación/ha
                    </x-th>

                    <x-th class="text-center bg-blue-700 text-white">
                        Lt de agua por penca de inicio a infestación
                    </x-th>

                </x-tr>
            </thead>
            <tbody>
                @foreach ($campaniasGenerales as $indice => $campania)
                    <x-tr>
                        <x-td class="text-center">
                            {{$indice + 1}}
                        </x-td>
                        <x-td class="text-center">
                            <x-dropdown align="left">
                                <x-slot name="trigger">
                                    <span class="inline-flex rounded-md w-full lg:w-auto">
                                        <x-button type="button" class="flex items-center justify-center">
                                            Opciones
                                            <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                            </svg>
                                        </x-button>
                                    </span>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="w-full text-center">
                                        <x-dropdown-link class="text-center"
                                            @click="$wire.dispatch('editarCampania',{campaniaId:{{ $campania->id }}})">
                                            Editar Campaña
                                        </x-dropdown-link>
                                        <x-dropdown-link class="text-center !text-red-600"
                                            wire:confirm="¿Estás seguro de eliminar esta campaña?"
                                            wire:click="eliminarCampania({{ $campania->id }})">
                                            Eliminar Campaña
                                        </x-dropdown-link>
                                    </div>
                                </x-slot>
                            </x-dropdown>
                        </x-td>
                        <x-td class="text-center">
                            {{$campania->nombre_campania}}
                        </x-td>
                        <x-td class="text-center">
                            {{$campania->campo}}
                        </x-td>
                        
                        <x-td class="text-center">
                            {{$campania->area}}
                        </x-td>
                        <x-td class="text-center">
                            {{$campania->fecha_siembra}}
                        </x-td>
                        <x-td class="text-center">
                            {{$campania->fecha_inicio}}
                        </x-td>
                        <x-td class="text-center">
                            {{$campania->fecha_fin}}
                        </x-td>
                        <!--POBLACIÓN DE PLANTAS-->
                        <x-td class="text-center">
                            {{$campania->pp_dia_cero_fecha_evaluacion}}
                        </x-td>
                        <x-td class="text-center">
                            {{$campania->pp_dia_cero_numero_pencas_madre}}
                        </x-td>
                        <x-td class="text-center">
                            {{$campania->pp_resiembra_fecha_evaluacion}}
                        </x-td>
                        <x-td class="text-center">
                            {{$campania->pp_resiembra_numero_pencas_madre}}
                        </x-td>
                        <!--BROTES POR PISO-->
                        <x-td class="text-center">
                            {{$campania->brotexpiso_fecha_evaluacion}}
                        </x-td>

                        <x-td class="text-center">
                            {{$campania->brotexpiso_actual_brotes_2piso}}
                        </x-td>

                        <x-td class="text-center">
                            {{$campania->brotexpiso_brotes_2piso_n_dias}}
                        </x-td>

                        <x-td class="text-center">
                            {{$campania->brotexpiso_actual_brotes_3piso}}
                        </x-td>

                        <x-td class="text-center">
                            {{$campania->brotexpiso_brotes_3piso_n_dias}}
                        </x-td>

                        <x-td class="text-center">
                            {{$campania->brotexpiso_actual_total_brotes_2y3piso}}
                        </x-td>

                        <x-td class="text-center">
                            {{$campania->brotexpiso_total_brotes_2y3piso_n_dias}}
                        </x-td>
                        <!--INFESTACION-->
                        <x-td class="text-center">
                            {{ formatear_fecha($campania->infestacion_fecha) }}
                        </x-td>
                        <x-td class="text-center">
                            {{ $campania->tipo_infestador }}
                        </x-td>
                        <x-td class="text-center">
                            {{ $campania->numero_infestadores }}
                        </x-td>
                        <x-td class="text-center">
                            {{ $campania->infestacion_kg_mama }}
                        </x-td>
                        <x-td class="text-center">
                            {{ $campania->infestacion_numero_pencas }}
                        </x-td>
                        <x-td class="text-center">
                            {{ $campania->numero_infestadores_por_penca }}
                        </x-td>
                        <x-td class="text-center">
                            {{ $campania->gramos_cochinilla_mama_por_infestador }}
                        </x-td>
                        <x-td class="text-center">
                            {{ $campania->infestacion_duracion_desde_campania }}
                        </x-td>
                        
                    </x-tr>
                @endforeach
            </tbody>
        </table>
        <div class="my-4">
            {{$campaniasGenerales->links()}}
        </div>
    </div>
</div>