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
                <x-table>
                    <x-slot name="thead">
                        <x-tr>
                            <x-th>Mes</x-th>
                            <x-th>Planilla (Pagado / Campo)</x-th>
                            <x-th>Cuadrilla (Pagado / Campo)</x-th>
                            <x-th>Maquinaria (Pagado / Campo)</x-th>
                            <x-th>Pesticidas (Pagado / Campo)</x-th>
                            <x-th>Fertilizantes (Pagado / Campo)</x-th>
                            <x-th>Gastos Grales (Pagado / Campo)</x-th>
                            <x-th>Reporte Excel</x-th>
                            <x-th>Acción</x-th>
                        </x-tr>
                    </x-slot>

                    <x-slot name="tbody">
                        @foreach ($mesesNombres as $numMes => $nombreMes)
                            @php
                                $costo = $costosAnio->get($numMes);
                                $fileUrl = ($costo && $costo->reporte_file && Storage::disk('public')->exists($costo->reporte_file))
                                    ? Storage::disk('public')->url($costo->reporte_file)
                                    : null;
                            @endphp
                            <x-tr>
                                {{-- Mes --}}
                                <x-td>
                                    {{ $nombreMes }}
                                </x-td>

                                {{-- Columna: Planilla --}}
                                <x-td>
                                    @if($costo)
                                        <div><span>P:</span> {{ fmt($costo->costo_planilla, 2) }}</div>
                                        <div><span>C:</span> {{ fmt($costo->costo_planilla_calculado, 2) }}</div>
                                        @php $dif = ($costo->costo_planilla ?? 0) - ($costo->costo_planilla_calculado ?? 0); @endphp
                                        <div>
                                            <span>D:</span> {{ fmt($dif, 2) }}
                                        </div>
                                    @else
                                        <span>-</span>
                                    @endif
                                </x-td>

                                {{-- Columna: Cuadrilla --}}
                                <x-td>
                                    @if($costo)
                                        <div><span>P:</span> {{ fmt($costo->costo_cuadrilla, 2) }}</div>
                                        <div><span>C:</span> {{ fmt($costo->costo_cuadrilla_calculado, 2) }}</div>
                                        @php $dif = ($costo->costo_cuadrilla ?? 0) - ($costo->costo_cuadrilla_calculado ?? 0); @endphp
                                        <div>
                                            <span>D:</span> {{ fmt($dif, 2) }}
                                        </div>
                                    @else
                                        <span>-</span>
                                    @endif
                                </x-td>

                                {{-- Columna: Maquinaria --}}
                                <x-td>
                                    @if($costo)
                                        <div><span>P:</span> {{ fmt($costo->costo_maquinaria, 2) }}</div>
                                        <div><span>C:</span> {{ fmt($costo->costo_maquinaria_calculado, 2) }}</div>
                                        @php $dif = ($costo->costo_maquinaria ?? 0) - ($costo->costo_maquinaria_calculado ?? 0); @endphp
                                        <div>
                                            <span>D:</span> {{ fmt($dif, 2) }}
                                        </div>
                                    @else
                                        <span>-</span>
                                    @endif
                                </x-td>

                                {{-- Columna: Pesticidas --}}
                                <x-td>
                                    @if($costo)
                                        <div><span>P:</span> {{ fmt($costo->costo_pesticida, 2) }}</div>
                                        <div><span>C:</span> {{ fmt($costo->costo_pesticida_calculado, 2) }}</div>
                                        @php $dif = ($costo->costo_pesticida ?? 0) - ($costo->costo_pesticida_calculado ?? 0); @endphp
                                        <div>
                                            <span>D:</span> {{ fmt($dif, 2) }}
                                        </div>
                                    @else
                                        <span>-</span>
                                    @endif
                                </x-td>

                                {{-- Columna: Fertilizantes --}}
                                <x-td>
                                    @if($costo)
                                        <div><span>P:</span> {{ fmt($costo->costo_fertilizante, 2) }}</div>
                                        <div><span>C:</span> {{ fmt($costo->costo_fertilizante_calculado, 2) }}</div>
                                        @php $dif = ($costo->costo_fertilizante ?? 0) - ($costo->costo_fertilizante_calculado ?? 0); @endphp
                                        <div>
                                            <span>D:</span> {{ fmt($dif, 2) }}
                                        </div>
                                    @else
                                        <span>-</span>
                                    @endif
                                </x-td>

                                {{-- Columna: Gastos Generales --}}
                                <x-td>
                                    @if($costo)
                                        <div><span>P:</span> {{ fmt($costo->costo_gastos_generales, 2) }}</div>
                                        <div><span>C:</span> {{ fmt($costo->costo_gastos_generales_calculado, 2) }}</div>
                                        @php $dif = ($costo->costo_gastos_generales ?? 0) - ($costo->costo_gastos_generales_calculado ?? 0); @endphp
                                        <div>
                                            <span>D:</span> {{ fmt($dif, 2) }}
                                        </div>
                                    @else
                                        <span>-</span>
                                    @endif
                                </x-td>

                                {{-- Columna: Reporte File --}}
                                <x-td>
                                    @if($fileUrl)
                                        <x-button href="{{ $fileUrl }}" variant="info" size="xs">
                                            <i class="fa fa-file-excel"></i> Descargar
                                        </x-button>
                                    @else
                                        <span>No generado</span>
                                    @endif
                                </x-td>

                                {{-- Columna: Botón Consolidar --}}
                                <x-td>
                                    <x-button wire:click="generarMes({{ $numMes }})" wire:loading.attr="disabled"  size="xs">
                                        <i class="fa fa-sync-alt"></i> Consolidar
                                    </x-button>
                                </x-td>
                            </x-tr>
                        @endforeach
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
</div>