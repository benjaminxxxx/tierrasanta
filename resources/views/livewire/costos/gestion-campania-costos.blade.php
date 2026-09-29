<div class="space-y-4">

    {{-- ── HEADER ────────────────────────────────────────────────────────── --}}
    <x-flex class="justify-between flex-wrap gap-3">
        <div>
            <x-title>Costos por Campaña</x-title>
            <x-subtitle>Distribución acumulada de costos durante la campaña</x-subtitle>
        </div>
        <x-flex class="flex-wrap gap-2">
            <x-select-campo label="Seleccionar campo" wire:model.live="campoSeleccionado" class="w-auto" />
            <x-select wire:model.live="campaniaSeleccionada" label="Seleccionar campaña" class="w-auto">
                <option value="">Seleccione una campaña</option>
                @if ($campoSeleccionado && count($campanias) > 0)
                    @foreach ($campanias as $campania)
                        <option value="{{ $campania['id'] }}">{{ $campania['nombre_campania'] }}</option>
                    @endforeach
                @endif
            </x-select>
        </x-flex>
    </x-flex>

    <x-card>
        @if (!$campoSeleccionado || !$campaniaSeleccionada)
            <x-label>Seleccione un campo y luego una campaña para ver el detalle de costos.</x-label>

        @else
            @can(\App\Constants\Permisos::CAMPAÑA_COSTOS_VER)

                {{-- ── INFO + ACCIONES ──────────────────────────────────────── --}}
                @if ($campaniaActual)
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">

                        {{-- Meta de la campaña --}}
                        <div class="flex flex-wrap gap-5">
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider">Campo</p>
                                <p class="font-semibold text-card-foreground">{{ $campaniaActual['campo'] }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider">Campaña</p>
                                <p class="font-semibold text-card-foreground">{{ $campaniaActual['nombre_campania'] }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider">Inicio</p>
                                <p class="font-semibold text-card-foreground">
                                    {{ \Carbon\Carbon::parse($campaniaActual['fecha_inicio'])->format('d/m/Y') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider">Cierre</p>
                                @if ($campaniaActual['fecha_fin'])
                                    <p class="font-semibold text-card-foreground">
                                        {{ \Carbon\Carbon::parse($campaniaActual['fecha_fin'])->format('d/m/Y') }}
                                    </p>
                                @else
                                    <p class="inline-flex items-center gap-1 text-yellow-500 font-medium text-sm">
                                        <i class="fa fa-clock text-xs"></i> Sin fecha de cierre
                                    </p>
                                @endif
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider">Duración</p>
                                <p class="font-semibold text-card-foreground">{{ $duracionMeses }} meses</p>
                            </div>
                        </div>

                        {{-- Botones de acción --}}
                        <x-flex class="gap-2">
                            @if (!$campaniaActual['fecha_fin'])
                                <x-button variant="warning" wire:click="mostrarCerrarCampania" type="button">
                                    <i class="fa fa-lock mr-1"></i> Cerrar Campaña
                                </x-button>
                            @endif
                        </x-flex>
                    </div>

                    {{-- ── CARDS DE RESUMEN ──────────────────────────────────── --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                        <div class="rounded-lg bg-muted/40 p-3">
                            <p class="text-xs text-gray-400 uppercase tracking-wider">Total distribuido</p>
                            <p class="text-lg font-bold text-card-foreground font-mono">
                                S/ {{ number_format($totales['total_distribuido'] ?? 0, 2) }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3">
                            <p class="text-xs text-gray-400 uppercase tracking-wider">Meses distribuidos</p>
                            <p class="text-lg font-bold text-card-foreground">
                                {{ $totales['meses_con_distribucion'] ?? 0 }}
                                <span class="text-sm font-normal text-gray-400">/ {{ $totales['total_meses'] ?? 0 }}</span>
                            </p>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3">
                            <p class="text-xs text-gray-400 uppercase tracking-wider">Sin costos registrados</p>
                            <p class="text-lg font-bold {{ ($totales['meses_sin_costo'] ?? 0) > 0 ? 'text-yellow-400' : 'text-card-foreground' }}">
                                {{ $totales['meses_sin_costo'] ?? 0 }} meses
                            </p>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3">
                            <p class="text-xs text-gray-400 uppercase tracking-wider">Estado</p>
                            <p class="text-sm font-semibold mt-0.5">
                                @if ($campaniaActual['fecha_fin'])
                                    <span class="text-gray-400"><i class="fa fa-circle-check mr-1 text-green-500"></i>Cerrada</span>
                                @else
                                    <span class="text-yellow-400"><i class="fa fa-circle-dot mr-1"></i>En curso</span>
                                @endif
                            </p>
                        </div>
                    </div>
                @endif

                {{-- ── TABLA PRINCIPAL con subtablas colapsables ─────────────── --}}
                @if (count($filasMeses) > 0)
                    <div x-data="{ abiertos: {} }">
                        <x-table>
                            <x-slot name="thead">
                                <x-tr>
                                    <x-th class="w-8"></x-th>
                                    <x-th class="text-center w-6">#</x-th>
                                    <x-th>Período</x-th>
                                    <x-th class="text-center">Días activos</x-th>
                                    <x-th class="text-center">% Participación</x-th>
                                    <x-th class="text-right">Total del mes</x-th>
                                    <x-th class="text-right">Dist. Blanco</x-th>
                                    <x-th class="text-right">Dist. Negro</x-th>
                                    <x-th class="text-right">Dist. Total</x-th>
                                    <x-th class="text-center">Estado</x-th>
                                </x-tr>
                            </x-slot>
                            <x-slot name="tbody">
                                @foreach ($filasMeses as $idx => $fila)
                                    {{-- ── Fila principal del mes ──────────────── --}}
                                    <x-tr class="{{ $fila['tiene_distribucion'] ? '' : 'opacity-60' }}">

                                        {{-- Toggle subtabla --}}
                                        <x-td class="text-center">
                                            @if ($fila['tiene_distribucion'])
                                                <x-button variant="ghost" type="button"
                                                    @click="abiertos['{{ $fila['key'] }}'] = !abiertos['{{ $fila['key'] }}']">
                                                    <i class="fa fa-chevron-down text-xs transition-transform duration-200"
                                                        :class="abiertos['{{ $fila['key'] }}'] ? 'rotate-180' : ''"></i>
                                                </x-button>
                                            @endif
                                        </x-td>

                                        <x-td class="text-center text-gray-400 text-sm">{{ $idx + 1 }}</x-td>

                                        <x-td>
                                            <div class="font-medium text-card-foreground">
                                                {{ $fila['nombre_mes'] }} {{ $fila['anio'] }}
                                            </div>
                                            <div class="text-xs text-gray-400 mt-0.5">
                                                {{ $fila['dias_mes'] }} días en el mes
                                                @if ($fila['es_primer_mes'] || $fila['es_ultimo_mes'])
                                                    ·
                                                    <span class="text-yellow-500">
                                                        @if ($fila['es_primer_mes'] && $fila['es_ultimo_mes']) Mes único
                                                        @elseif ($fila['es_primer_mes']) Inicio de campaña
                                                        @else Cierre de campaña
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                        </x-td>

                                        <x-td class="text-center">
                                            @if ($fila['tiene_distribucion'])
                                                <span class="font-mono text-sm text-card-foreground">
                                                    {{ $fila['dias_activos'] }} / {{ $fila['dias_mes'] }}
                                                </span>
                                            @else
                                                <span class="text-gray-500">—</span>
                                            @endif
                                        </x-td>

                                        <x-td class="text-center">
                                            @if ($fila['tiene_distribucion'])
                                                <div class="flex flex-col items-center gap-1">
                                                    <span class="font-mono font-semibold text-sm text-card-foreground">
                                                        {{ number_format($fila['porcentaje'] * 100, 2) }}%
                                                    </span>
                                                    <div class="w-16 h-1.5 bg-muted rounded-full overflow-hidden">
                                                        <div class="h-full rounded-full bg-primary"
                                                            style="width: {{ min($fila['porcentaje'] * 100, 100) }}%">
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-gray-500">—</span>
                                            @endif
                                        </x-td>

                                        <x-td class="text-right font-mono text-sm">
                                            @if ($fila['tiene_costo'])
                                                S/ {{ number_format($fila['total_mes'], 2) }}
                                            @else
                                                <span class="text-gray-500">—</span>
                                            @endif
                                        </x-td>

                                        <x-td class="text-right font-mono text-sm">
                                            @if ($fila['tiene_distribucion'])
                                                S/ {{ number_format($fila['dist_blanco'], 2) }}
                                            @else
                                                <span class="text-gray-500">—</span>
                                            @endif
                                        </x-td>

                                        <x-td class="text-right font-mono text-sm">
                                            @if ($fila['tiene_distribucion'])
                                                S/ {{ number_format($fila['dist_negro'], 2) }}
                                            @else
                                                <span class="text-gray-500">—</span>
                                            @endif
                                        </x-td>

                                        <x-td class="text-right font-mono font-semibold text-sm">
                                            @if ($fila['tiene_distribucion'])
                                                S/ {{ number_format($fila['dist_total'], 2) }}
                                            @else
                                                <span class="text-gray-500">—</span>
                                            @endif
                                        </x-td>

                                        <x-td class="text-center">
                                            @if (!$fila['tiene_costo'])
                                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-gray-800 text-gray-400 border border-gray-700">
                                                    <i class="fa fa-circle-minus text-xs"></i> Sin costos
                                                </span>
                                            @elseif (!$fila['tiene_distribucion'])
                                                <div class="flex flex-col items-center gap-1">
                                                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-yellow-900/40 text-yellow-400 border border-yellow-800">
                                                        <i class="fa fa-triangle-exclamation text-xs"></i> Sin distribuir
                                                    </span>
                                                    @can(\App\Constants\Permisos::CAMPAÑA_COSTOS_GESTIONAR)
                                                        @if ($fila['costo_mensual_id'])
                                                            <x-button variant="ghost" type="button" class="text-xs !py-0.5 !px-2"
                                                                wire:click="recalcularMes({{ $fila['costo_mensual_id'] }})">
                                                                <i class="fa fa-rotate text-xs mr-1"></i> Distribuir
                                                            </x-button>
                                                        @endif
                                                    @endcan
                                                </div>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-green-900/40 text-green-400 border border-green-800">
                                                    <i class="fa fa-circle-check text-xs"></i> Distribuido
                                                </span>
                                            @endif
                                        </x-td>
                                    </x-tr>

                                    {{-- ── Subtabla colapsable: detalle por tipo de costo ── --}}
                                    @if ($fila['tiene_distribucion'] && count($fila['detalle_costos']) > 0)
                                        <tr x-show="abiertos['{{ $fila['key'] }}']"
                                            x-transition:enter="transition-all duration-200 ease-out"
                                            x-transition:enter-start="opacity-0"
                                            x-transition:enter-end="opacity-100"
                                            x-transition:leave="transition-all duration-150 ease-in"
                                            x-transition:leave-start="opacity-100"
                                            x-transition:leave-end="opacity-0"
                                            style="display: none;">
                                            <td colspan="10" class="p-0 bg-muted/30">
                                                <div class="px-6 py-3">

                                                    {{-- Encabezado de la subtabla --}}
                                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                                                        <i class="fa fa-table-cells-large mr-1"></i>
                                                        Detalle de distribución — {{ $fila['nombre_mes'] }} {{ $fila['anio'] }}
                                                        ({{ number_format($fila['porcentaje'] * 100, 2) }}% — {{ $fila['dias_activos'] }}/{{ $fila['dias_mes'] }} días)
                                                    </p>

                                                    <table class="w-full text-xs border border-border rounded-lg overflow-hidden">
                                                        <thead class="bg-card">
                                                            <tr>
                                                                <th class="p-2 text-left border-b border-border text-gray-400 font-medium">Tipo de Costo</th>
                                                                <th class="p-2 text-right border-b border-border text-gray-400 font-medium">Total Blanco (mes)</th>
                                                                <th class="p-2 text-right border-b border-border text-gray-400 font-medium">Total Negro (mes)</th>
                                                                <th class="p-2 text-right border-b border-border text-gray-400 font-medium">Total Mes</th>
                                                                <th class="p-2 text-right border-b border-border text-blue-400 font-medium">Dist. Blanco</th>
                                                                <th class="p-2 text-right border-b border-border text-orange-400 font-medium">Dist. Negro</th>
                                                                <th class="p-2 text-right border-b border-border text-green-400 font-medium">Dist. Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($fila['detalle_costos'] as $detalle)
                                                                <tr class="border-b border-border hover:bg-muted">
                                                                    <td class="p-2 font-medium text-card-foreground">
                                                                        {{ $detalle['etiqueta'] }}
                                                                    </td>
                                                                    <td class="p-2 text-right font-mono text-gray-300">
                                                                        S/ {{ number_format($detalle['total_blanco'], 2) }}
                                                                    </td>
                                                                    <td class="p-2 text-right font-mono text-gray-300">
                                                                        S/ {{ number_format($detalle['total_negro'], 2) }}
                                                                    </td>
                                                                    <td class="p-2 text-right font-mono text-gray-200">
                                                                        S/ {{ number_format($detalle['total_mes'], 2) }}
                                                                    </td>
                                                                    <td class="p-2 text-right font-mono text-blue-300">
                                                                        S/ {{ number_format($detalle['dist_blanco'], 2) }}
                                                                    </td>
                                                                    <td class="p-2 text-right font-mono text-orange-300">
                                                                        S/ {{ number_format($detalle['dist_negro'], 2) }}
                                                                    </td>
                                                                    <td class="p-2 text-right font-mono font-semibold text-green-300">
                                                                        S/ {{ number_format($detalle['dist_total'], 2) }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach

                                                            {{-- Totales de la subtabla --}}
                                                            <tr class="bg-card font-semibold border-t-2 border-border">
                                                                <td class="p-2 text-card-foreground">TOTAL</td>
                                                                <td class="p-2 text-right font-mono text-gray-200">
                                                                    S/ {{ number_format($fila['total_mes_blanco'], 2) }}
                                                                </td>
                                                                <td class="p-2 text-right font-mono text-gray-200">
                                                                    S/ {{ number_format($fila['total_mes_negro'], 2) }}
                                                                </td>
                                                                <td class="p-2 text-right font-mono text-card-foreground">
                                                                    S/ {{ number_format($fila['total_mes'], 2) }}
                                                                </td>
                                                                <td class="p-2 text-right font-mono text-blue-300">
                                                                    S/ {{ number_format($fila['dist_blanco'], 2) }}
                                                                </td>
                                                                <td class="p-2 text-right font-mono text-orange-300">
                                                                    S/ {{ number_format($fila['dist_negro'], 2) }}
                                                                </td>
                                                                <td class="p-2 text-right font-mono text-green-300">
                                                                    S/ {{ number_format($fila['dist_total'], 2) }}
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif

                                @endforeach

                                {{-- ── Fila de totales generales ──────────────────── --}}
                                <x-tr class="border-t-2 border-primary/30 bg-muted/20 font-bold">
                                    <x-td colspan="8" class="text-right text-card-foreground text-sm">
                                        TOTAL CAMPAÑA
                                    </x-td>
                                    <x-td class="text-right font-mono text-card-foreground">
                                        S/ {{ number_format($totales['total_distribuido'] ?? 0, 2) }}
                                    </x-td>
                                    <x-td></x-td>
                                </x-tr>

                            </x-slot>
                        </x-table>
                    </div>
                @endif

            @else
                <x-danger>No tiene permisos para ver la siguiente información</x-danger>
            @endcan
        @endif
    </x-card>

    <x-loading wire:loading />

    {{-- ══════════════════════════════════════════════════════════════════════
         MODAL: CERRAR CAMPAÑA
    ══════════════════════════════════════════════════════════════════════ --}}
    <x-dialog-modal wire:model.live="mostrandoCierreCampania">
        <x-slot name="title">
            <span class="flex items-center gap-2 text-yellow-400">
                <i class="fa fa-lock"></i> Cerrar Campaña
            </span>
        </x-slot>

        <x-slot name="content">
            <div class="mb-4 p-3 rounded-lg bg-yellow-950/30 border border-yellow-800 text-yellow-400 text-sm flex gap-2">
                <i class="fa fa-triangle-exclamation mt-0.5 shrink-0"></i>
                <span>Al cerrar la campaña se establecerá una fecha de cierre definitiva. Esto afecta el
                    prorrateo de los meses posteriores.</span>
            </div>
            <div class="space-y-3">
                <div>
                    <x-label for="fechaCierreCampania">Fecha de cierre <span class="text-red-400">*</span></x-label>
                    <x-input id="fechaCierreCampania" type="date" wire:model="fechaCierreCampania" class="w-full mt-1" />
                    @error('fechaCierreCampania')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <x-label for="motivoCierre">Observación (opcional)</x-label>
                    <textarea id="motivoCierre" wire:model="motivoCierre" rows="2"
                        class="w-full mt-1 rounded-md border border-input bg-background px-3 py-2 text-sm text-card-foreground placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary"
                        placeholder="Resultado de la cosecha, motivo de cierre..."></textarea>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-flex class="gap-2 justify-end">
                <x-button variant="secondary" wire:click="$set('mostrandoCierreCampania', false)" type="button">
                    Cancelar
                </x-button>
                <x-button variant="warning" wire:click="confirmarCierreCampania" type="button"
                    wire:loading.attr="disabled">
                    <i class="fa fa-lock mr-1"></i> Confirmar Cierre
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>

</div>