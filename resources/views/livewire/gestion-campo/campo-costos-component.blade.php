<div class="space-y-4">
    <div>
        <x-title>Reporte General de Costos</x-title>
        <x-subtitle>Resumen de costos por campo, fecha y campaña</x-subtitle>
    </div>

    <x-card class="space-y-4">
        <x-flex class="justify-between">
            <x-flex>
                <x-select-campo wire:model.live="filtroCampo" label="Filtrar por campo" class="w-auto" />

                @if ($filtroCampo)
                    <x-select label="Temporada" wire:model.live="campaniaId" class="w-auto"
                        wire:key="select_campania_{{ $filtroCampo }}">
                        <option value="">-- Todas las temporadas --</option>
                        @foreach ($campaniasDelCampo as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre_campania }}</option>
                        @endforeach
                    </x-select>

                    <x-input type="date" label="Fecha Inicio" wire:model.live="fechaInicio" class="w-auto" :disabled="(bool) $campaniaId" />

                    <x-input type="date" label="Fecha Fin" wire:model.live="fechaFin" class="w-auto" :disabled="(bool) $campaniaId" />
                @endif

                <div x-data="{ mostrarBoton: @js(filled($filtro)) }" class="flex items-end gap-2">
                    <x-input type="search" label="Trabajador, nombre o código de labor" wire:model="filtro"
                        class="w-auto" x-on:input="mostrarBoton = $event.target.value.trim().length > 0"
                        x-on:keydown.enter.prevent="if (mostrarBoton) $wire.aplicarFiltro()" />

                    <x-button x-show="mostrarBoton" x-cloak size="sm" wire:click="aplicarFiltro"
                        wire:loading.attr="disabled" wire:target="aplicarFiltro">
                        <i class="fa fa-search"></i> Buscar
                    </x-button>
                </div>
            </x-flex>

            <div>
                <x-button variant="primary" wire:click="consolidar" wire:loading.attr="disabled"
                    wire:target="consolidar">
                    <i class="fa fa-sync"></i> Consolidar
                </x-button>
                @if ($reporteFileCampania)
                    <x-button variant="secondary" href="{{ Storage::disk('public')->url($reporteFileCampania) }}"
                        wire:loading.attr="disabled" wire:target="descargarReporte">
                        <i class="fa fa-download"></i> Descargar Reporte
                    </x-button>
                @endif
            </div>
        </x-flex>

        <div>
            <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 block mb-1">
                Mostrar tipos
            </span>
            <div class="flex flex-wrap gap-3">
                @foreach ($tiposDisponibles as $tipo)
                    <label class="flex items-center gap-1 text-sm cursor-pointer">
                        <input type="checkbox" value="{{ $tipo }}" wire:model.live="tiposSeleccionados" />
                        <span class="capitalize">{{ str_replace('_', ' ', $tipo) }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </x-card>

    <x-card>
        <x-table>
            <x-slot name="thead">
                <x-tr>
                    <x-th>Fecha</x-th>
                    <x-th>Tipo</x-th>
                    <x-th>Campaña</x-th>
                    <x-th>Campo</x-th>
                    <x-th>Labor</x-th>
                    <x-th>Trabajador</x-th>
                    <x-th>Horas</x-th>
                    <x-th>Jornales</x-th>
                    <x-th>Costo</x-th>
                    <x-th>Observación</x-th>
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                @foreach ($resumenes as $fila)
                                <x-tr class="{{ match ($fila->origen_tipo) {
                        'planilla' => 'bg-blue-50 dark:bg-blue-900/20',
                        'riego' => 'bg-cyan-50 dark:bg-cyan-900/20',
                        'cuadrilla' => 'bg-green-50 dark:bg-green-900/20',
                        default => '',
                    } }}">
                                    <x-td>{{ \Carbon\Carbon::parse($fila->fecha)->format('d/m/Y') }}</x-td>
                                    <x-td class="uppercase text-xs font-semibold">{{ $fila->origen_tipo }}</x-td>
                                    <x-td>{{ $fila->campania }}</x-td>
                                    <x-td>{{ $fila->campo }}</x-td>
                                    <x-td>{{ $fila->labor_nombre ?? '-' }}</x-td>
                                    <x-td class="!text-left">{{ $fila->trabajador ?? '-' }}</x-td>
                                    <x-td>{{ $fila->horas }}</x-td>
                                    <x-td>{{ $fila->cantidad_jornales }}</x-td>
                                    <x-td>{{ number_format($fila->costo_total, 2) }}</x-td>
                                    <x-td>
                                        @if ($fila->observacion)
                                            <span class="text-amber-600 dark:text-amber-400 text-xs" title="{{ $fila->observacion }}">
                                                <i class="fa fa-exclamation-triangle"></i> {{ $fila->observacion }}
                                            </span>
                                        @endif
                                    </x-td>
                                </x-tr>
                @endforeach
            </x-slot>
        </x-table>

        <div class="mt-4">
            {{ $resumenes->links() }}
        </div>
    </x-card>
    <x-loading wire:loading />
</div>