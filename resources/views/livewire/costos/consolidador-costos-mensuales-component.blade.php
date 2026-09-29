<div>
    <x-dialog-modal wire:model="mostrarModal" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between pr-6">
                <span class="text-lg font-bold">Consolidación Anual de Costos y Reportes</span>
                <div class="flex items-center gap-2">
                    <x-label for="anio_select" value="Año:" class="font-bold" />
                    <x-input type="number" id="anio_select" wire:model.live="anio" class="w-28 text-center" min="2000"
                        max="2100" />
                </div>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="overflow-x-auto">
                <x-table class="w-full text-xs border-collapse">
                    <x-slot name="thead">
                        <x-tr>
                            <x-th class="p-2 border border-border font-bold text-left whitespace-nowrap">Concepto / Mes</x-th>
                            @foreach ($mesesNombres as $numMes => $nombreMes)
                                <x-th class="p-2 border border-border text-center uppercase text-xs whitespace-nowrap">
                                    {{ substr($nombreMes, 0, 3) }}
                                </x-th>
                            @endforeach
                        </x-tr>
                    </x-slot>

                    <x-slot name="tbody">
                        @php
                            $conceptos = [
                                'costo_planilla' => ['label' => 'Planilla', 'calc' => 'costo_planilla_calculado'],
                                'costo_bono_productividad' => ['label' => 'Bono Prod.', 'calc' => 'costo_bono_productividad_calculado'],
                                'costo_cuadrilla' => ['label' => 'Cuadrilla', 'calc' => 'costo_cuadrilla_calculado'],
                                'costo_cuadrilla_bono' => ['label' => 'Cuadrilla bono', 'calc' => 'costo_cuadrilla_bono_calculado'],
                                'costo_maquinaria' => ['label' => 'Maquinaria', 'calc' => 'costo_maquinaria_calculado'],
                                'costo_pesticida' => ['label' => 'Pesticidas', 'calc' => 'costo_pesticida_calculado'],
                                'costo_fertilizante' => ['label' => 'Fertilizantes', 'calc' => 'costo_fertilizante_calculado'],
                                'costo_servicio_campo' => ['label' => 'Servicios Campos', 'calc' => 'costo_servicio_campo_calculado'],
                                'costo_gastos_generales' => ['label' => 'Gastos Grales.', 'calc' => 'costo_gastos_generales_calculado'],
                            ];
                        @endphp

                        {{-- Filas de Conceptos de Costo --}}
                        @foreach ($conceptos as $keyField => $meta)
                            <x-tr>
                                <x-td class="p-2 border border-border font-medium whitespace-nowrap bg-muted text-foreground">
                                    {{ $meta['label'] }}
                                </x-td>
                                @foreach ($mesesNombres as $numMes => $nombreMes)
                                    @php
                                        $costo = $costosAnio->get($numMes);
                                        $pagado = $costo->{$keyField} ?? null;
                                        $calculado = $costo->{$meta['calc']} ?? null;
                                        $dif = ($pagado !== null && $calculado !== null) ? ($pagado - $calculado) : null;
                                    @endphp
                                    <x-td class="p-1.5 border border-border text-right font-mono text-xs">
                                        @if($costo)
                                            <div class="text-foreground"><span class="text-muted-foreground text-[10px]">P:</span>
                                                {{ fmt($pagado, 2) }}</div>
                                            <div class="text-blue-700 dark:text-blue-400"><span class="text-blue-400 dark:text-blue-500 text-[10px]">C:</span>
                                                {{ fmt($calculado, 2) }}</div>
                                            {{-- Diferencia: verde si cuadra, rojo si no --}}
                                            <div @class([
                                                'text-muted-foreground' => $dif === null,
                                                'text-green-600 dark:text-green-400' => $dif !== null && abs($dif) < 0.01,
                                                'text-red-600 dark:text-red-400 font-semibold' => $dif !== null && abs($dif) >= 0.01,
                                            ])><span class="opacity-70 text-[10px]">D:</span>
                                                {{ fmt($dif, 2) }}</div>
                                        @else
                                            <span class="text-muted-foreground">-</span>
                                        @endif
                                    </x-td>
                                @endforeach
                            </x-tr>
                            @if ($keyField === 'costo_planilla')
                                {{-- Parte del calculado de planilla que no tiene trabajo en campo (hoja CUADRE PLANILLA) --}}
                                <x-tr>
                                    <x-td class="p-2 pl-5 border border-border whitespace-nowrap bg-muted text-muted-foreground text-xs">
                                        ↳ Mano de obra indirecta
                                    </x-td>
                                    @foreach ($mesesNombres as $numMes => $nombreMes)
                                        @php $costo = $costosAnio->get($numMes); @endphp
                                        <x-td class="p-1.5 border border-border text-right font-mono text-xs text-muted-foreground">
                                            {{ $costo && $costo->costo_mano_obra_indirecta !== null ? fmt($costo->costo_mano_obra_indirecta, 2) : '-' }}
                                        </x-td>
                                    @endforeach
                                </x-tr>
                            @endif
                        @endforeach

                        {{-- Fila: Reportes Excel --}}
                        <x-tr class="bg-muted border-t-2 border-border">
                            <x-td class="p-2 border border-border font-medium whitespace-nowrap text-foreground">Reporte Excel</x-td>
                            @foreach ($mesesNombres as $numMes => $nombreMes)
                                @php
                                    $costo = $costosAnio->get($numMes);
                                    $fileUrl = ($costo && $costo->reporte_file && Storage::disk('public')->exists($costo->reporte_file))
                                        ? Storage::disk('public')->url($costo->reporte_file)
                                        : null;
                                @endphp
                                <x-td class="p-1 border border-border text-center">
                                    @if($fileUrl)
                                        <a href="{{ $fileUrl }}" target="_blank"
                                            class="inline-flex items-center px-1.5 py-0.5 text-[11px] font-medium text-blue-700 bg-blue-100 rounded hover:bg-blue-200 dark:text-blue-300 dark:bg-blue-900/40 dark:hover:bg-blue-900/70">
                                            <i class="fa fa-file-excel mr-1"></i> Ver
                                        </a>
                                    @else
                                        <span class="text-muted-foreground text-[10px]">-</span>
                                    @endif
                                </x-td>
                            @endforeach
                        </x-tr>

                        {{-- Fila: Acciones / Consolidar --}}
                        <x-tr class="bg-muted">
                            <x-td class="p-2 border border-border font-medium whitespace-nowrap text-foreground">Acción</x-td>
                            @foreach ($mesesNombres as $numMes => $nombreMes)
                                <x-td class="p-1 border border-border text-center">
                                    <div class="flex justify-center gap-1">
                                        <button wire:click="generarMes({{ $numMes }})" wire:loading.attr="disabled"
                                            title="Consolidar (la mano de obra ya está al día; solo rehace los días que cambiaron)"
                                            class="px-2 py-0.5 text-[11px] font-medium text-white bg-indigo-600 rounded hover:bg-indigo-700 disabled:opacity-50">
                                            <i class="fa fa-sync-alt"></i>
                                        </button>
                                        <button wire:click="generarMes({{ $numMes }}, true)" wire:loading.attr="disabled"
                                            wire:confirm="¿Reconstruir toda la mano de obra de {{ $nombreMes }}? Tarda más; úsalo solo si algo no cuadra."
                                            title="Reconstruir toda la mano de obra del mes"
                                            class="px-2 py-0.5 text-[11px] font-medium text-foreground bg-muted border border-border rounded hover:bg-accent disabled:opacity-50">
                                            <i class="fa fa-wrench"></i>
                                        </button>
                                    </div>
                                </x-td>
                            @endforeach
                        </x-tr>
                    </x-slot>
                </x-table>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button wire:click="cerrarModal" variant="secondary">
                Aceptar
            </x-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading/>
</div>