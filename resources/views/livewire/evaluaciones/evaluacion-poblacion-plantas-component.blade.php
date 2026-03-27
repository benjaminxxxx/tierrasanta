<div class="space-y-4">
    <x-flex>
        <x-title>
            Población de Plantas
        </x-title>
        <x-button type="button" @click="$wire.dispatch('agregarEvaluacion')">
            <i class="fa fa-plus"></i> Agregar Evaluación
        </x-button>
    </x-flex>
    <x-card class="my-4">
        <x-flex class="justify-between">
            <x-flex>
                <x-selector-dia label="Fecha de evaluación" wire:model.live="fechaFiltro" class="w-auto" />
                <x-select-campo wire:model.live="campoFiltrado" label="Lote" error="false" class="w-auto" />
                @if ($campoFiltrado)
                    <x-select label="Campaña" wire:model.live="campaniaFiltrada" class="w-auto">
                        <option value="">-- Todas las campañas --</option>
                        @foreach ($campaniasParaFiltro as $campaniaOption)
                            <option value="{{ $campaniaOption->id }}">
                                {{ $campaniaOption->nombre_campania }}
                            </option>
                        @endforeach
                    </x-select>
                @endif
                <x-input type="search" label="Evaluador" wire:model.live.debounce.600ms="evaluadorFiltro"
                    class="w-auto" />
            </x-flex>
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
                            <x-dropdown-link class="text-center" wire:click="exportarReporte">
                                Exportar Reporte
                            </x-dropdown-link>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>
        </x-flex>


    </x-card>

    <x-card class="mt-4" x-data="{ abiertos: {} }">
        <x-table>
            <x-slot name="thead">
                <x-tr>
                    <x-th class="text-center">#</x-th>
                    <x-th class="text-center">Campo / Campaña</x-th>
                    <x-th class="text-center">Área</x-th>
                    <x-th class="text-center">Siembra</x-th>
                    <x-th class="text-center">Evaluador</x-th>
                    <x-th class="text-center">Mts Cama/Ha</x-th>
                    <x-th class="text-center">Evaluaciones</x-th>
                    <x-th class="text-center">Día Cero<br>Promedios</x-th>
                    <x-th class="text-center">Resiembra<br>Promedios</x-th>
                    <x-th class="text-center">Acciones</x-th>
                </x-tr>
            </x-slot>

            <x-slot name="tbody">
                @foreach ($poblacionPlantas as $indice => $p)
                    <tr class="border-b border-border">
                        <x-td class="text-center">
                            {{ $indice + 1 }}
                        </x-td>

                        {{-- COL 2: Campo + Campaña --}}
                        <x-td class="text-center">
                            {{ $p->campania->campo }} - {{ $p->campania->nombre_campania }}
                        </x-td>

                        <x-td class="text-center">{{ $p->area_lote }}</x-td>
                        <x-td class="text-center">{{ formatear_fecha($p->campania->fecha_siembra) }}</x-td>
                        <x-td class="text-center">{{ $p->evaluador }}</x-td>
                        <x-td class="text-center">{{ $p->metros_cama_ha }}</x-td>

                        {{-- FECHAS DE EVALUACIÓN --}}
                        <x-td class="text-left">
                            <div>
                                <span class="font-semibold text-gray-700 dark:text-gray-200">Cero:</span>
                                {{ formatear_fecha($p->fecha_eval_cero) }}
                            </div>
                            <div>
                                <span class="font-semibold text-gray-700 dark:text-gray-200">Resiembra:</span>
                                {{ formatear_fecha($p->fecha_eval_resiembra) }}
                            </div>
                        </x-td>

                        {{-- PROMEDIOS DÍA CERO --}}
                        <x-td class="text-center text-xs">
                            <div class="grid grid-cols-3 gap-x-2 text-muted-foreground">
                                <span>Plt</span><span>B2°</span><span>B3°</span>
                            </div>
                            <div class="grid grid-cols-3 gap-x-2 font-bold">
                                <span>{{ round($p->promedio_plantas_metro_cero, 0) }}</span>
                                <span>{{ round($p->promedio_brazos2_metro_cero, 0) }}</span>
                                <span>{{ round($p->promedio_brazos3_metro_cero, 0) }}</span>
                            </div>
                            <div class="border-t border-border mt-1 pt-1 grid grid-cols-3 gap-x-2 text-muted-foreground">
                                <span>{{ round($p->promedio_plantas_ha_cero, 0) }}</span>
                                <span>{{ round($p->total_brazos2_ha_cero, 0) }}</span>
                                <span>{{ round($p->total_brazos3_ha_cero, 0) }}</span>
                            </div>
                        </x-td>

                        {{-- PROMEDIOS RESIEMBRA --}}
                        <x-td class="text-center text-xs">
                            <div class="text-muted-foreground">Plt</div>
                            <div class="font-bold">{{ round($p->promedio_plantas_metro_resiembra, 0) }}</div>
                            <div class="border-t border-border mt-1 pt-1 text-muted-foreground">
                                {{ round($p->promedio_plantas_ha_resiembra, 0) }}
                            </div>
                        </x-td>

                        {{-- ACCIONES --}}
                        <x-td class="text-center">
                            <x-flex class="justify-center gap-2">
                                {{-- Toggle detalles --}}
                                <x-button variant="ghost" @click="abiertos[{{ $p->id }}] = !abiertos[{{ $p->id }}]">
                                    <i class="fa fa-chevron-down transition-transform duration-200"
                                        :class="abiertos[{{ $p->id }}] ? 'rotate-180' : ''"></i>
                                </x-button>

                                <x-button variant="secondary"
                                    @click="$wire.dispatch('editarPoblacionPlanta', { poblacionId: {{ $p->id }} })">
                                    <i class="fa fa-edit"></i>
                                </x-button>

                                <x-button variant="danger" wire:click="eliminarPoblacionPlanta({{ $p->id }})">
                                    <i class="fa fa-trash"></i>
                                </x-button>
                            </x-flex>
                        </x-td>
                    </tr>

                    {{-- SUBTABLA DETALLES (colapsable) --}}
                    <tr class="bg-muted" x-show="abiertos[{{ $p->id }}]"
                        x-transition:enter="transition-all duration-200 ease-out" x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100" x-transition:leave="transition-all duration-150 ease-in"
                        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display: none;">
                        <td colspan="10" class="p-0">
                            <table class="w-full text-xs border-t border-border">

                                <thead class="bg-card">
                                    <tr>
                                        {{-- Info cama --}}
                                        <th class="p-2 text-center border-b border-border" rowspan="2">Cama</th>
                                        <th class="p-2 text-center border-b border-border" rowspan="2">Longitud</th>

                                        {{-- Grupo Día Cero --}}
                                        <th class="p-2 text-center border-b border-l border-border bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300"
                                            colspan="6">
                                            Día Cero
                                        </th>

                                        {{-- Grupo Resiembra --}}
                                        <th class="p-2 text-center border-b border-l border-border bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300"
                                            colspan="2">
                                            Resiembra
                                        </th>
                                    </tr>
                                    <tr>
                                        {{-- Subheaders Día Cero --}}
                                        <th
                                            class="p-2 text-center border-b border-l border-border bg-green-50 dark:bg-green-900/20">
                                            Plantas x Hilera</th>
                                        <th class="p-2 text-center border-b border-border bg-green-50 dark:bg-green-900/20">
                                            Plantas x Metro</th>
                                        <th
                                            class="p-2 text-center border-b border-l border-border bg-green-50 dark:bg-green-900/20">
                                            Brazos 2° x Hilera</th>
                                        <th class="p-2 text-center border-b border-border bg-green-50 dark:bg-green-900/20">
                                            Brazos 2° x Metro</th>
                                        <th
                                            class="p-2 text-center border-b border-l border-border bg-green-50 dark:bg-green-900/20">
                                            Brazos 3° x Hilera</th>
                                        <th class="p-2 text-center border-b border-border bg-green-50 dark:bg-green-900/20">
                                            Brazos 3° x Metro</th>

                                        {{-- Subheaders Resiembra --}}
                                        <th
                                            class="p-2 text-center border-b border-l border-border bg-blue-50 dark:bg-blue-900/20">
                                            Plantas x Hilera</th>
                                        <th class="p-2 text-center border-b border-border bg-blue-50 dark:bg-blue-900/20">
                                            Plantas x Metro</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($p->detalles as $d)
                                        <tr class="border-b border-border hover:bg-card/60 transition-colors">
                                            <td class="p-2 text-center">{{ $d->numero_cama }}</td>
                                            <td class="p-2 text-center">{{ $d->longitud_cama }}</td>

                                            {{-- Día Cero --}}
                                            <td class="p-2 text-center border-l border-border">
                                                {{ $d->eval_cero_plantas_x_hilera }}
                                            </td>
                                            <td class="p-2 text-center">
                                                {{ $d->plantas_por_metro_cero !== null ? round($d->plantas_por_metro_cero, 0) : '-' }}
                                            </td>
                                            <td class="p-2 text-center border-l border-border">
                                                {{ $d->brazos2_piso_x_hilera_cero }}
                                            </td>
                                            <td class="p-2 text-center">
                                                {{ $d->brazos2_piso_x_metro_cero !== null ? round($d->brazos2_piso_x_metro_cero, 0) : '-' }}
                                            </td>
                                            <td class="p-2 text-center border-l border-border">
                                                {{ $d->brazos3_piso_x_hilera_cero }}
                                            </td>
                                            <td class="p-2 text-center">
                                                {{ $d->brazos3_piso_x_metro_cero !== null ? round($d->brazos3_piso_x_metro_cero, 0) : '-' }}
                                            </td>

                                            {{-- Resiembra --}}
                                            <td class="p-2 text-center border-l border-border">
                                                {{ $d->eval_resiembra_plantas_x_hilera }}
                                            </td>
                                            <td class="p-2 text-center">
                                                {{ $d->plantas_por_metro_resiembra !== null ? round($d->plantas_por_metro_resiembra, 0) : '-' }}
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

        <div class="mt-5">
            {{ $poblacionPlantas->links() }}
        </div>
    </x-card>


    <x-loading wire:loading />
</div>