<div>
    <x-flex>
        <x-h3>
            Población de Plantas
        </x-h3>
        <x-button type="button" @click="$wire.dispatch('agregarEvaluacion')">
            <i class="fa fa-plus"></i> Agregar Evaluación
        </x-button>
    </x-flex>
    <x-card2 class="my-4">
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


    </x-card2>

    <x-card2 class="mt-4">
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
                    <tr class="border-b">
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
                        <x-td class="text-center">
                            <div><b class="text-gray-700 dark:text-gray-200">Cama:</b> {{ round($p->promedio_dia_cero, 2) }}</div>
                            <div><b class="text-gray-700 dark:text-gray-200">Metro:</b> {{ round($p->promedio_plantas_metro_cero, 2) }}</div>
                            <div><b class="text-gray-700 dark:text-gray-200">Ha:</b> {{ round($p->promedio_plantas_ha_cero, 2) }}</div>
                        </x-td>

                        {{-- PROMEDIOS RESIEMBRA --}}
                        <x-td class="text-center">
                            <div><b class="text-gray-700 dark:text-gray-200">Cama:</b> {{ round($p->promedio_resiembra, 2) }}</div>
                            <div><b class="text-gray-700 dark:text-gray-200">Metro:</b> {{ round($p->promedio_plantas_metro_resiembra, 2) }}</div>
                            <div><b class="text-gray-700 dark:text-gray-200">Ha:</b> {{ round($p->promedio_plantas_ha_resiembra, 2) }}</div>
                        </x-td>

                        {{-- ACCIONES --}}
                        <x-td class="text-center">
                            <x-flex class="justify-center gap-2">
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

                    {{-- SUBTABLA DE DETALLES ---}}
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <td colspan="10" class="p-0">
                            <table class="w-full text-xs border-t dark:border-gray-700">
                                <thead class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                    <tr>
                                        <th class="p-2 text-center">Cama</th>
                                        <th class="p-2 text-center">Longitud</th>
                                        <th class="p-2 text-center">Cero: Plantas x Hilera</th>
                                        <th class="p-2 text-center">Cero: Plantas x Metro</th>
                                        <th class="p-2 text-center">Resiembra: Plantas x Hilera</th>
                                        <th class="p-2 text-center">Resiembra: Plantas x Metro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($p->detalles as $d)
                                        <tr class="border-b dark:border-gray-700">
                                            <td class="p-2 text-center">{{ $d->numero_cama }}</td>
                                            <td class="p-2 text-center">{{ $d->longitud_cama }}</td>
                                            <td class="p-2 text-center">{{ $d->eval_cero_plantas_x_hilera }}</td>
                                            <td class="p-2 text-center">{{ round($d->plantas_por_metro_cero, 2) }}</td>
                                            <td class="p-2 text-center">{{ $d->eval_resiembra_plantas_x_hilera }}</td>
                                            <td class="p-2 text-center">{{ round($d->plantas_por_metro_resiembra, 2) }}</td>
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
    </x-card2>


    <x-loading wire:loading />
</div>