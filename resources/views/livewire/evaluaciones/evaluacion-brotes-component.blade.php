<div>
    <x-loading wire:loading />
    <x-flex>
        <x-h3>
            Evaluación de Brotes x Piso
        </x-h3>
        <x-button type="button" @click="$wire.dispatch('agregarEvaluacionBrote')">
            <i class="fa fa-plus"></i> Agregar Evaluación
        </x-button>
    </x-flex>
    <x-card2 class="mt-4">
        <x-flex class="justify-between">
            <x-flex>
                <x-input type="date" label="Fecha de evaluación" wire:model.live="fechaFiltro" />

                <x-select-campo wire:model.live="campoFiltrado" label="Lote" error="false" />

                @if ($campoFiltrado)
                    <x-select label="Campaña" wire:model.live="campaniaFiltrada">
                        <option value="">-- Todas las campañas --</option>
                        @foreach ($campaniasParaFiltro as $campaniaOption)
                            <option value="{{ $campaniaOption->id }}">
                                {{ $campaniaOption->nombre_campania }}
                            </option>
                        @endforeach
                    </x-select>
                @endif

                <x-input type="search" label="Evaluador" wire:model.live.debounce.600ms="evaluadorFiltro" />
            </x-flex>

            <div class="relative">
                <x-dropdown width="60">
                    <x-slot name="trigger">
                        <span class="inline-flex rounded-md">
                            <x-button class="flex items-center">
                                Reporte
                                <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m1 1 4 4 4-4" />
                                </svg>
                            </x-button>
                        </span>
                    </x-slot>

                    <x-slot name="content">
                        <div class="w-60">
                            <x-dropdown-link class="text-center" wire:click="exportarReporteBrotesXPiso">
                                Exportar Reporte
                            </x-dropdown-link>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>
        </x-flex>
    </x-card2>

    <x-card2 class="mt-4">
        <x-table>

            {{-- =================== THEAD =================== --}}
            <x-slot name="thead">
                <x-tr>
                    <x-th class="text-center">#</x-th>
                    <x-th class="text-center">Campo / Campaña</x-th>
                    <x-th class="text-center">Fecha</x-th>
                    <x-th class="text-center">Evaluador</x-th>
                    <x-th class="text-center">Mts Cama/Ha</x-th>

                    <x-th class="text-center">2° Piso<br>Actual</x-th>
                    <x-th class="text-center">2° Piso<br>+30 días</x-th>

                    <x-th class="text-center">3° Piso<br>Actual</x-th>
                    <x-th class="text-center">3° Piso<br>+30 días</x-th>

                    <x-th class="text-center">Total<br>Actual</x-th>
                    <x-th class="text-center">Total<br>+30 días</x-th>

                    <x-th class="text-center">Acciones</x-th>
                </x-tr>
            </x-slot>

            {{-- =================== TBODY =================== --}}
            <x-slot name="tbody">
                @foreach ($evaluacionesBrotes as $index => $e)
                    <tr class="border-b">
                        {{-- Número --}}
                        <x-td class="text-center">{{ $index + 1 }}</x-td>

                        {{-- Campo + campaña --}}
                        <x-td class="text-center">
                            {{ $e->campania->campo }} - {{ $e->campania->nombre_campania }}
                        </x-td>

                        {{-- Fecha --}}
                        <x-td class="text-center">{{ formatear_fecha($e->fecha) }}</x-td>

                        {{-- Evaluador --}}
                        <x-td class="text-center">{{ $e->evaluador }}</x-td>

                        {{-- Mts cama --}}
                        <x-td class="text-center">{{ $e->metros_cama }}</x-td>

                        {{-- Promedios 2° piso --}}
                        <x-td class="text-center">{{ number_format($e->promedio_actual_brotes_2piso, 0) }}</x-td>
                        <x-td class="text-center">{{ number_format($e->promedio_brotes_2piso_n_dias, 0) }}</x-td>

                        {{-- Promedios 3° piso --}}
                        <x-td class="text-center">{{ number_format($e->promedio_actual_brotes_3piso, 0) }}</x-td>
                        <x-td class="text-center">{{ number_format($e->promedio_brotes_3piso_n_dias, 0) }}</x-td>

                        {{-- Totales --}}
                        <x-td class="text-center">{{ number_format($e->promedio_actual_total_brotes_2y3piso, 0) }}</x-td>
                        <x-td class="text-center">{{ number_format($e->promedio_total_brotes_2y3piso_n_dias, 0) }}</x-td>

                        {{-- Acciones --}}
                        <x-td class="text-center">
                            <x-flex class="justify-center gap-2">
                                @if ($e->reporte_file)
                                    <x-secondary-button-a href="{{ Storage::disk('public')->url($e->reporte_file) }}">
                                        <i class="fa fa-file-excel"></i>
                                    </x-secondary-button-a>
                                @endif

                                {{-- Editar --}}
                                <x-button variant="secondary"
                                    @click="$wire.dispatch('editarEvaluacionBrotesPorPiso',{evaluacionBrotesXPisoId:{{ $e->id }}})">
                                    <i class="fa fa-edit"></i>
                                </x-button>

                                {{-- Eliminar --}}
                                <x-button variant="danger" wire:click="eliminarBrotesXPiso({{ $e->id }})">
                                    <i class="fa fa-trash"></i>
                                </x-button>
                            </x-flex>
                        </x-td>
                    </tr>

                    {{-- =================== SUBTABLA DETALLES =================== --}}
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <td colspan="12" class="p-0">
                            <table class="w-full text-xs border-t dark:border-gray-700">
                                <thead class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                    <tr>
                                        <th class="p-2 text-center">Cama</th>
                                        <th class="p-2 text-center">Longitud</th>

                                        <th class="p-2 text-center" colspan="2">N° ACTUAL 2° PISO</th>
                                        <th class="p-2 text-center" colspan="2">2° Piso +30 días</th>

                                        <th class="p-2 text-center" colspan="2">3° Piso Actual</th>
                                        <th class="p-2 text-center" colspan="2">3° Piso +30 días</th>
                                    </tr>
                                    <tr class="bg-gray-50 dark:bg-gray-800">
                                        <th></th>
                                        <th></th>

                                        <th class="p-1 text-center">Total</th>
                                        <th class="p-1 text-center">/ Metro</th>

                                        <th class="p-1 text-center">Total</th>
                                        <th class="p-1 text-center">/ Metro</th>

                                        <th class="p-1 text-center">Total</th>
                                        <th class="p-1 text-center">/ Metro</th>

                                        <th class="p-1 text-center">Total</th>
                                        <th class="p-1 text-center">/ Metro</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($e->detalles as $d)
                                        <tr class="border-b dark:border-gray-700">
                                            <td class="p-2 text-center">{{ $d->numero_cama }}</td>
                                            <td class="p-2 text-center">{{ $d->longitud_cama }}</td>

                                            {{-- 2° Piso Actual --}}
                                            <td class="p-2 text-center">{{ $d->brotes_aptos_2p_actual }}</td>
                                            <td class="p-2 text-center">{{ number_format($d->brotes_2p_actual_por_mt, 2) }}</td>

                                            {{-- 2° Piso +30 días --}}
                                            <td class="p-2 text-center">{{ $d->brotes_aptos_2p_despues_n_dias }}</td>
                                            <td class="p-2 text-center">{{ number_format($d->brotes_2p_despues_por_mt, 2) }}
                                            </td>

                                            {{-- 3° Piso Actual --}}
                                            <td class="p-2 text-center">{{ $d->brotes_aptos_3p_actual }}</td>
                                            <td class="p-2 text-center">{{ number_format($d->brotes_3p_actual_por_mt, 2) }}</td>

                                            {{-- 3° Piso +30 días --}}
                                            <td class="p-2 text-center">{{ $d->brotes_aptos_3p_despues_n_dias }}</td>
                                            <td class="p-2 text-center">{{ number_format($d->brotes_3p_despues_por_mt, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                        </td>
                    </tr>

                @endforeach
            </x-slot>

        </x-table>

        {{-- PAGINACIÓN --}}
        <div class="mt-5">
            {{ $evaluacionesBrotes->links() }}
        </div>
    </x-card2>

</div>