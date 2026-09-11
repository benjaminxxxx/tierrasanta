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
                        <x-tr class="bg-gray-100">
                            <x-th class="p-2 border font-bold text-left whitespace-nowrap">Concepto / Mes</x-th>
                            @foreach ($mesesNombres as $numMes => $nombreMes)
                                <x-th class="p-2 border text-center uppercase text-xs whitespace-nowrap">
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
                                'costo_maquinaria' => ['label' => 'Maquinaria', 'calc' => 'costo_maquinaria_calculado'],
                                'costo_pesticida' => ['label' => 'Pesticidas', 'calc' => 'costo_pesticida_calculado'],
                                'costo_fertilizante' => ['label' => 'Fertilizantes', 'calc' => 'costo_fertilizante_calculado'],
                                'costo_gastos_generales' => ['label' => 'Gastos Grales.', 'calc' => 'costo_gastos_generales_calculado'],
                            ];
                        @endphp

                        {{-- Filas de Conceptos de Costo --}}
                        @foreach ($conceptos as $keyField => $meta)
                            <x-tr class="hover:bg-gray-50">
                                <x-td class="p-2 border font-medium whitespace-nowrap bg-gray-50">
                                    {{ $meta['label'] }}
                                </x-td>
                                @foreach ($mesesNombres as $numMes => $nombreMes)
                                    @php
                                        $costo = $costosAnio->get($numMes);
                                        $pagado = $costo->{$keyField} ?? null;
                                        $calculado = $costo->{$meta['calc']} ?? null;
                                        $dif = ($pagado !== null && $calculado !== null) ? ($pagado - $calculado) : null;
                                    @endphp
                                    <x-td class="p-1.5 border text-right font-mono text-xs">
                                        @if($costo)
                                            <div class="text-gray-900"><span class="text-gray-400 text-[10px]">P:</span>
                                                {{ fmt($pagado, 2) }}</div>
                                            <div class="text-blue-700"><span class="text-blue-400 text-[10px]">C:</span>
                                                {{ fmt($calculado, 2) }}</div>
                                            <div class="text-gray-500"><span class="text-gray-400 text-[10px]">D:</span>
                                                {{ fmt($dif, 2) }}</div>
                                        @else
                                            <span class="text-gray-300">-</span>
                                        @endif
                                    </x-td>
                                @endforeach
                            </x-tr>
                        @endforeach

                        {{-- Fila: Reportes Excel --}}
                        <x-tr class="bg-gray-50 border-t-2">
                            <x-td class="p-2 border font-medium whitespace-nowrap">Reporte Excel</x-td>
                            @foreach ($mesesNombres as $numMes => $nombreMes)
                                @php
                                    $costo = $costosAnio->get($numMes);
                                    $fileUrl = ($costo && $costo->reporte_file && Storage::disk('public')->exists($costo->reporte_file))
                                        ? Storage::disk('public')->url($costo->reporte_file)
                                        : null;
                                @endphp
                                <x-td class="p-1 border text-center">
                                    @if($fileUrl)
                                        <a href="{{ $fileUrl }}" target="_blank"
                                            class="inline-flex items-center px-1.5 py-0.5 text-[11px] font-medium text-blue-700 bg-blue-100 rounded hover:bg-blue-200">
                                            <i class="fa fa-file-excel mr-1"></i> Ver
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-[10px]">-</span>
                                    @endif
                                </x-td>
                            @endforeach
                        </x-tr>

                        {{-- Fila: Acciones / Consolidar --}}
                        <x-tr class="bg-gray-50">
                            <x-td class="p-2 border font-medium whitespace-nowrap">Acción</x-td>
                            @foreach ($mesesNombres as $numMes => $nombreMes)
                                <x-td class="p-1 border text-center">
                                    <button wire:click="generarMes({{ $numMes }})" wire:loading.attr="disabled"
                                        class="px-2 py-0.5 text-[11px] font-medium text-white bg-indigo-600 rounded hover:bg-indigo-700 disabled:opacity-50">
                                        <i class="fa fa-sync-alt"></i>
                                    </button>
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