<div x-data="gestion_cuadrilla_pagos">
    <x-loading wire:loading />

    <x-flex class="w-full justify-between">
        <x-flex class="my-3">
            <div>
                <x-h3>
                    Reporte General de Cuadrilla
                </x-h3>
            </div>
        </x-flex>
        <x-button-a href="{{ route('cuadrilleros.gestion') }}">
            <i class="fa fa-arrow-left"></i> Volver a gestión de cuadrilleros
        </x-button-a>
    </x-flex>

    <x-card2>
        <x-flex class="justify-between">
            <form wire:submit="buscarRegistros">
                <x-flex class="w-full !items-end">
                    <x-input-date label="Fecha inicio" wire:model.live="fecha_inicio" error="fecha_inicio" />
                    <x-input-date label="Fecha fin" wire:model.live="fecha_fin" error="fecha_fin" />

                    <x-select label="Grupo" wire:model.live="grupoSeleccionado" error="grupoSeleccionado">
                        <option value="">TODOS LOS GRUPOS</option>
                        @foreach ($grupoCuadrillas as $grupoCuadrilla)
                            <option value="{{ $grupoCuadrilla->codigo }}">{{ $grupoCuadrilla->nombre }}</option>
                        @endforeach
                    </x-select>
                    <x-input type="search" label="Buscar por nombre" wire:model="nombre_cuadrillero"
                        error="nombre_cuadrillero" />
                    <x-button type="submit">
                        <i class="fa fa-filter"></i> Filtrar
                    </x-button>
                </x-flex>
            </form>
            <div class="relative">
                <x-dropdown width="60">
                    <x-slot name="trigger">
                        <span class="inline-flex rounded-md">
                            <x-button class="flex items-center">
                                Reporte <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m1 1 4 4 4-4" />
                                </svg>
                            </x-button>
                        </span>
                    </x-slot>

                    <x-slot name="content">
                        <div class="w-60">
                            <x-dropdown-link class="text-center" wire:click="generarInformeGeneralCuadrilla">
                                Descargar Reporte Excel
                            </x-dropdown-link>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>
        </x-flex>

    </x-card2>
    <x-card2 class="mt-4">
        <x-table>
            <x-slot name="thead">
                <x-tr>
                    <x-th class="text-center">Fecha</x-th>
                    <x-th>Grupo</x-th>
                    <x-th>Cuadrillero</x-th>
                    <x-th class="text-center">Costo Personalizado</x-th>
                    <x-th class="text-center">Horas Registradas</x-th>
                    <x-th class="text-center">Horas Detalladas</x-th>
                    <x-th class="text-center">Total Jornal</x-th>
                    <x-th class="text-center">Total Bono</x-th>
                    <x-th class="text-center">Costo Total</x-th>
                    <x-th class="text-center">¿Está Pagado?</x-th>
                    <x-th class="text-center">¿Bono Pagado?</x-th>
                    <x-th class="text-center">Campos</x-th>
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                @php
                    $tot_costo_personalizado = 0;
                    $tot_horas = 0;
                    $tot_horas_detalladas = 0;
                    $tot_costo_dia = 0;
                    $tot_bono = 0;
                    $tot_general = 0;
                @endphp
                @foreach ($registros as $registro)
                    @php
                        $tot_costo_personalizado += $registro['costo_personalizado_dia'];
                        $tot_horas += $registro['total_horas'];
                        $tot_horas_detalladas += $registro['horas_detalladas'];
                        $tot_costo_dia += $registro['costo_dia'];
                        $tot_bono += $registro['total_bono'];
                        $tot_general += $registro['costo_dia'] + $registro['total_bono'];

                    @endphp
                    <x-tr>
                        <x-td class="text-center">{{ $registro['fecha'] }}</x-td>
                        <x-td>{{ $registro['codigo_grupo'] }}</x-td>
                        <x-td>{{ $registro['nombres'] }}</x-td>
                        <x-td class="text-center">S/. {{ formatear_numero($registro['costo_personalizado_dia']) }}</x-td>
                        <x-td class="text-center">{{ formatear_numero($registro['total_horas']) }}</x-td>
                        <x-td class="text-center">
                            {{ formatear_numero($registro['horas_detalladas']) }}
                            @if ($registro['total_horas'] == $registro['horas_detalladas'])
                                <i class="fa-solid fa-check text-green-600"></i>
                            @else
                                <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
                            @endif
                        </x-td>
                        <x-td class="text-center">S/. {{ formatear_numero($registro['costo_dia']) }}</x-td>
                        <x-td class="text-center">S/. {{ formatear_numero($registro['total_bono']) }}</x-td>
                        <x-td class="text-center">S/.
                            {{ formatear_numero($registro['total_bono'] + $registro['costo_dia']) }}</x-td>
                        <x-td class="text-center">{{ $registro['esta_pagado'] ? 'Sí' : 'No' }}</x-td>
                        <x-td class="text-center">{{ $registro['bono_esta_pagado'] ? 'Sí' : 'No' }}</x-td>
                        <x-td>{{ $registro['detalle_campos'] }}</x-td>
                    </x-tr>
                @endforeach
            </x-slot>
            <x-slot name="tfoot">
                <x-tr class="font-bold">
                    <x-td></x-td> {{-- Fecha --}}
                    <x-td></x-td> {{-- Grupo --}}
                    <x-td class="text-right">TOTALES:</x-td>

                    {{-- Costo personalizado --}}
                    <x-td class="text-center">
                        S/. {{ formatear_numero($tot_costo_personalizado) }}
                    </x-td>

                    {{-- Total horas --}}
                    <x-td class="text-center">
                        {{ formatear_numero($tot_horas) }}
                    </x-td>

                    {{-- Horas detalladas --}}
                    <x-td class="text-center">
                        {{ formatear_numero($tot_horas_detalladas) }}
                    </x-td>

                    {{-- Costo día --}}
                    <x-td class="text-center">
                        S/. {{ formatear_numero($tot_costo_dia) }}
                    </x-td>

                    {{-- Total bono --}}
                    <x-td class="text-center">
                        S/. {{ formatear_numero($tot_bono) }}
                    </x-td>

                    {{-- Total general --}}
                    <x-td class="text-center">
                        S/. {{ formatear_numero($tot_general) }}
                    </x-td>

                    {{-- Pagado / bono pagado (sin total) --}}
                    <x-td></x-td>
                    <x-td></x-td>

                    {{-- Campos --}}
                    <x-td></x-td>
                </x-tr>
            </x-slot>
        </x-table>
    </x-card2>
</div>