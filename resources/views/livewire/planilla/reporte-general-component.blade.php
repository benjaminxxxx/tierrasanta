<div>
    <x-flex class="w-full justify-between">
        <x-title>
            Reporte General de Planilla
        </x-title>
    </x-flex>


    @can(\App\Constants\Permisos::PLANILLA_RESUMEN_GENERAL_VER)
        <x-card>
            <x-flex class="justify-between">
                <form wire:submit="buscarRegistros">
                    <x-flex class="w-full !items-end">
                        <x-selector-dia label="Fecha inicio" wire:model.live="fechaInicio" error="fechaInicio"
                            class="w-auto" />
                        <x-selector-dia label="Fecha fin" wire:model.live="fechaFin" error="fechaFin" class="w-auto" />

                        <x-select label="Grupo" wire:model.live="grupoSeleccionado" error="grupoSeleccionado"
                            class="w-auto">
                            <option value="">TODOS LOS GRUPOS</option>
                            <option value="SG">SIN GRUPOS</option>
                            @foreach ($grupos as $grupo)
                                <option value="{{ $grupo->codigo }}">{{ $grupo->descripcion }}</option>
                            @endforeach
                        </x-select>
                        <x-input type="search" label="Buscar por nombre" wire:model="filtroNombres" error="filtroNombres"
                            class="w-auto" />
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
                                <x-dropdown-link class="text-center" wire:click="generarInformeGeneralPlanilla">
                                    Descargar Reporte Excel
                                </x-dropdown-link>
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>
            </x-flex>

        </x-card>
        <x-card class="mt-4">
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">Fecha</x-th>
                        <x-th>Grupo</x-th>
                        <x-th>Planillero</x-th>
                        <x-th class="text-center">Asistencia</x-th>
                        <x-th class="text-center">Costo x Hora</x-th>
                        <x-th class="text-center">Horas Detalladas</x-th>
                        <x-th class="text-center">Total Jornal</x-th>
                        <x-th class="text-center">Total Bono</x-th>
                        <x-th class="text-center">Costo Total</x-th>
                        <x-th class="text-center">Campos</x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @php
                        $tot_horas = 0;
                        $tot_jornal = 0;
                        $tot_bono = 0;
                        $tot_general = 0;
                    @endphp
                    @foreach ($registros as $registro)
                        @php
                            // Cálculo del subtotal (total jornal)
                            $subTotal = $registro['total_horas'] * $registro['costo_x_hora'];

                            // Acumular totales
                            $tot_horas += $registro['total_horas'];
                            $tot_jornal += $subTotal;
                            $tot_bono += $registro['total_bono'];
                            $tot_general += $subTotal + $registro['total_bono'];
                        @endphp
                        <x-tr>
                            <x-td class="text-center">{{ $registro['fecha'] }}</x-td>
                            <x-td>{{ $registro['codigo_grupo'] }}</x-td>
                            <x-td>{{ $registro['nombres'] }}</x-td>
                            <x-td class="text-center">{{ $registro['asistencia'] }}</x-td>
                            <x-td class="text-center">S/. {{ formatear_numero($registro['costo_x_hora']) }}</x-td>
                            <x-td class="text-center">{{ formatear_numero($registro['total_horas']) }}</x-td>
                            <x-td class="text-center">S/. {{ formatear_numero($subTotal) }}</x-td>
                            <x-td class="text-center">S/. {{ formatear_numero($registro['total_bono']) }}</x-td>
                            <x-td class="text-center">S/. {{ formatear_numero($subTotal + $registro['total_bono']) }}</x-td>
                            <x-td>{{ $registro['detalle_campos'] }}</x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
                <x-slot name="tfoot">
                    <x-tr class="font-bold">
                        <x-td></x-td>
                        <x-td></x-td>

                        {{-- Etiqueta --}}
                        <x-td class="text-right">TOTALES:</x-td>

                        {{-- Costo x hora no se suma --}}
                        <x-td class="text-center">-</x-td>
                        <x-td class="text-center">-</x-td>

                        {{-- Total horas --}}
                        <x-td class="text-center">
                            {{ formatear_numero($tot_horas) }}
                        </x-td>

                        {{-- Total jornal --}}
                        <x-td class="text-center">
                            S/. {{ formatear_numero($tot_jornal) }}
                        </x-td>

                        {{-- Total bono --}}
                        <x-td class="text-center">
                            S/. {{ formatear_numero($tot_bono) }}
                        </x-td>

                        {{-- Total general --}}
                        <x-td class="text-center">
                            S/. {{ formatear_numero($tot_general) }}
                        </x-td>

                        {{-- Campos --}}
                        <x-td></x-td>
                    </x-tr>
                </x-slot>
            </x-table>
        </x-card>
    @else
        <x-danger class="mt-4">
            No tienes permiso para ver el reporte general de planilla.
        </x-danger>
    @endcan

    <x-loading wire:loading />
</div>