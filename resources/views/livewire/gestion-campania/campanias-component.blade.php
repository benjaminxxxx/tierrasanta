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
        @php
            $columnBlocks = [

                [
                    'title' => 'Población de Plantas',
                    'color' => 'bg-red-600 text-white',
                    'columns' => [
                        'Fecha de evaluación día cero',
                        'Nª de pencas madre día cero',
                        'Fecha de evaluación resiembra',
                        'Nª de pencas madre después de resiembra',
                    ],
                ],

                [
                    'title' => 'Brotes por Piso',
                    'color' => 'bg-amber-600 text-white',
                    'columns' => [
                        'Fecha evaluación brotes por piso',
                        'Actual brotes aptos 2° piso',
                        'Brotes 2° piso después de N días',
                        'Actual brotes aptos 3° piso',
                        'Brotes 3° piso después de N días',
                        'Total actual brotes 2° + 3° piso',
                        'Total brotes 2° + 3° piso después de N días',
                    ],
                ],

                [
                    'title' => 'Infestación',
                    'color' => 'bg-lime-600 text-white',
                    'columns' => [
                        'Fecha de infestación',
                        'Tipo de infestador',
                        'Nº de infestadores',
                        'Kg de mamá',
                        'Nº de pencas',
                        'Nº infestadores x penca',
                        'Grs de cochinilla mamá x infestador',
                        'Tiempo de inicio a infestación',
                        'Kg de nitrógeno de inicio a infestación',
                        'Kg de fósforo de inicio a infestación',
                        'Kg de potasio de inicio a infestación',
                        'Kg de calcio de inicio a infestación',
                        'Kg de magnesio de inicio a infestación',
                        'Kg de manganeso de inicio a infestestación',
                        'Kg de zinc de inicio a infestación',
                        'Kg de fierro de inicio a infestación',
                        'Lt de SalTrad de inicio a infestación',
                        'm³ de agua desde inicio a infestación/ha',
                        'Lt de agua por penca de inicio a infestación',
                    ],
                ],
                [
                    'title' => 'Re-infestación',
                    'color' => 'bg-emerald-600 text-white',
                    'columns' => [
                        'Fecha de re-infestación', //
                        'Tipo de infestador',//
                        'Nº de infestadores',//
                        'Kg de mamá', //
                        'Nº de pencas', //
                        'Nº infestadores x penca', //
                        'Grs de cochinilla mamá x infestador',//
                        'Tiempo de infestación a re-infestación',//
                        'Kg de nitrógeno de infestación a re-infestación',
                        'Kg de fósforo de infestación a re-infestación',
                        'Kg de potasio de infestación a re-infestación',
                        'Kg de calcio de infestación a re-infestación',
                        'Kg de magnesio de infestación a re-infestación',
                        'Kg de manganeso de infestación a re-infestación',
                        'Kg de zinc de infestación a re-infestación',
                        'Kg de fierro de infestación a re-infestación',
                        'Lt de SalTrad de infestación a re-infestación',
                        'm³ de agua desde infestación a re-infestación/ha',
                        'Lt de agua por penca de infestación a re-infestación',
                    ],
                ],
                [
                    'title' => 'Cosecha',
                    'color' => 'bg-cyan-600 text-white',
                    'columns' => [
                        'Fecha de cosecha', 
                        'Tiempo de infestación a cosecha',
                        'Tiempo de re-infestación a cosecha',
                        'Tiempo de inicio a cosecha',
                        'Kg de nitrógeno de inicio a cosecha',
                        'Kg de fósforo de inicio a cosecha',
                        'Kg de potasio de inicio a cosecha',
                        'Kg de calcio de inicio a cosecha',
                        'Kg de magnesio de inicio a cosecha',
                        'Kg de manganeso de inicio a cosecha',
                        'Kg de zinc de inicio a cosecha',
                        'Kg de fierro de inicio a cosecha',
                        'Lt de SalTrad de inicio a cosecha',
                        'm³ de agua desde inicio a cosecha/ha',
                        'Lt de agua por penca de inicio a cosecha',
                        'Cosecha (kg)',
                        'Rendimiento total (kg)',
                        'Rendimiento por infestador (gr)',
                        'Rendimiento por penca (gr)',
                        
                        'PROYECCION COSECHA CONTEO',
                        'PROYECCION COSECHA PODA',
                        'DIFERENCIA PROYECCION CONTEO',
                        'DIFERENCIA PROYECCION PODA'                     			

                    ],
                ],
                [
                    'title' => 'ANALISIS FINANCIERO',
                    'color' => 'bg-blue-600 text-white',
                    'columns' => [
                        'COSTO $', 
                        'PRECIO VENTA $',
                        'VENTA TOTAL $',
                        'UTILIDAD O PERDIDA $',
                        'COSTO X KG',
                        '% UTILIDAD',
                    ],
                ]
            ];
        @endphp

        <table
            class="mt-3 p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">

                <!-- PRIMERA FILA: títulos y colspans -->
                <x-tr>
                    <x-th class="text-center" rowspan="2">N°</x-th>
                    <x-th class="text-center" rowspan="2">Acciones</x-th>
                    <x-th class="text-center" rowspan="2">Campaña</x-th>
                    <x-th class="text-center" rowspan="2">Campo</x-th>
                    <x-th class="text-center" rowspan="2">Área</x-th>
                    <x-th class="text-center" rowspan="2">Siembra</x-th>
                    <x-th class="text-center" rowspan="2">Inicio de Campaña</x-th>
                    <x-th class="text-center" rowspan="2">Fin de Campaña</x-th>

                    @foreach ($columnBlocks as $block)
                        <x-th class="text-center {{ $block['color'] }}" colspan="{{ count($block['columns']) }}">
                            {{ $block['title'] }}
                        </x-th>
                    @endforeach
                </x-tr>

                <!-- SEGUNDA FILA: subcolumnas -->
                <x-tr>
                    @foreach ($columnBlocks as $block)
                        @foreach ($block['columns'] as $col)
                            <x-th class="text-center {{ $block['color'] }}">
                                {{ $col }}
                            </x-th>
                        @endforeach
                    @endforeach
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

                        @include('livewire.gestion-campania.partials.campanias-poblacion-plantas')
                        @include('livewire.gestion-campania.partials.campanias-brotes-x-piso')
                        @include('livewire.gestion-campania.partials.campanias-infestacion')
                        @include('livewire.gestion-campania.partials.campanias-reinfestacion')
                        @include('livewire.gestion-campania.partials.campanias-cosecha')
                    </x-tr>
                @endforeach
            </tbody>
        </table>
        <div class="my-4">
            {{$campaniasGenerales->links()}}
        </div>
    </div>
</div>