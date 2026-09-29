<div>
    @php
        $reporte = $insumoKardexReporte;
        $generado = $reporte->file && $reporte->generado_at;
        $urlExcel = $generado ? \Illuminate\Support\Facades\Storage::disk('public')->url($reporte->file) : null;
        $total = [
            'productos' => array_sum(array_column($totalesGrupo, 'productos')),
            'entradas_importe' => array_sum(array_column($totalesGrupo, 'entradas_importe')),
            'salidas_importe' => array_sum(array_column($totalesGrupo, 'salidas_importe')),
            'saldo_unidades' => array_sum(array_column($totalesGrupo, 'saldo_unidades')),
            'saldo_importe' => array_sum(array_column($totalesGrupo, 'saldo_importe')),
        ];
    @endphp

    <x-card>
        {{-- Encabezado --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-title>
                    <a href="{{ route('almacen.kardex.reportes') }}"
                        class="underline text-blue-600 dark:text-blue-300">REPORTES DE KARDEX</a> /
                    {{ mb_strtoupper($reporte->nombre) }}
                </x-title>
                <div class="mt-2 flex flex-wrap items-center gap-1 uppercase">
                    <x-badge :color="$reporte->tipo_kardex === 'negro' ? 'gray' : 'blue'">KARDEX {{ $reporte->tipo_kardex }}</x-badge>
                    <x-badge color="gray">{{ $reporte->anio }}</x-badge>
                    @foreach ($reporte->grupos_ordenados as $grupo)
                        <span class="inline-flex items-center rounded-sm px-2.5 py-0.5 text-sm font-medium text-gray-800 border border-black/10"
                            style="background-color: #{{ $reporte->colorGrupo($grupo) }}">{{ $grupo }}</span>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($urlExcel)
                    <x-button variant="success" href="{{ $urlExcel }}" download>
                        <i class="fa fa-file-excel"></i> Descargar Excel
                    </x-button>
                @endif
                @can(\App\Constants\Permisos::INSUMO_KARDEX_REPORTE_GENERAR_RESUMEN)
                    <x-button wire:click="procesarKardexConsolidado">
                        <i class="fa fa-sync-alt"></i> Generar resumen
                    </x-button>
                @else
                    <x-danger>No tiene autorización para generar el resumen.</x-danger>
                @endcan
            </div>
        </div>

        {{-- Estado del Excel --}}
        <div class="mt-4">
            @if ($motivosDesactualizado)
                <div x-data="{ ver: false }"
                    class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span>
                            <i class="fa fa-exclamation-triangle mr-1"></i>
                            {{ $generado ? 'El Excel está desactualizado respecto a los kardex.' : 'Este reporte aún no se ha generado.' }}
                            Usa <b>Generar resumen</b>: actualiza los kardex, el índice y el Excel.
                        </span>
                        @if ($generado)
                            <button type="button" class="text-xs underline" @click="ver = !ver"
                                x-text="ver ? 'Ocultar motivos' : 'Ver motivos ({{ count($motivosDesactualizado) }})'"></button>
                        @endif
                    </div>
                    <ul x-show="ver" x-cloak class="mt-2 list-disc pl-6 text-xs space-y-0.5 max-h-48 overflow-y-auto">
                        @foreach ($motivosDesactualizado as $motivo)
                            <li>{{ $motivo }}</li>
                        @endforeach
                    </ul>
                </div>
            @elseif ($generado)
                <div class="rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-900 dark:border-green-800 dark:bg-green-950/40 dark:text-green-200">
                    <i class="fa fa-check-circle mr-1"></i>
                    Excel al día, generado el {{ $reporte->generado_at->format('d/m/Y H:i') }}
                    ({{ $total['productos'] }} hojas de producto + índice).
                </div>
            @endif

            @if ($advertencias)
                <div class="mt-2 rounded-lg border border-amber-300 bg-amber-50 p-3 text-xs text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-200">
                    <b>Advertencias de la generación:</b>
                    <ul class="mt-1 list-disc pl-6 space-y-0.5">
                        @foreach ($advertencias as $a)
                            <li>{{ $a }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Totalizado por grupo operativo --}}
        @if ($reporte->detalles->isNotEmpty())
            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($totalesGrupo as $grupo => $t)
                    <div class="overflow-hidden rounded-xl border border-border bg-background">
                        <div class="flex items-center justify-between px-4 py-2 text-gray-800"
                            style="background-color: #{{ $reporte->colorGrupo($grupo) }}">
                            <span class="text-sm font-bold uppercase">{{ \App\Models\InsKardexReporte::etiquetaGrupo($grupo) }}</span>
                            <span class="text-xs">{{ $t['productos'] }} productos</span>
                        </div>
                        @include('livewire.almacen.kardex.partials.insumo-kardex-reporte-totales', ['t' => $t, 'etiquetaCosto' => 'Costo'])
                    </div>
                @endforeach

                <div class="overflow-hidden rounded-xl border border-border bg-muted">
                    <div class="flex items-center justify-between px-4 py-2 bg-[#31869B] text-white">
                        <span class="text-sm font-bold">TOTAL</span>
                        <span class="text-xs">{{ $total['productos'] }} productos</span>
                    </div>
                    @include('livewire.almacen.kardex.partials.insumo-kardex-reporte-totales', ['t' => $total, 'etiquetaCosto' => 'Total costo'])
                </div>
            </div>
        @endif

        {{-- Índice --}}
        <x-table class="my-4">
            <x-slot name="thead">
                <x-tr>
                    <x-th compact colspan="2" class="!text-white font-bold !bg-[#31869B]">
                        ÍNDICE DE {{ implode(' Y ', array_map(fn($g) => \App\Models\InsKardexReporte::etiquetaGrupo($g), $reporte->grupos_ordenados)) }}
                    </x-th>
                    <x-th compact class="text-center">ESTADO KARDEX</x-th>
                    <x-th compact class="text-center">UNIDAD DE MEDIDA (TABLA 6)</x-th>
                    <x-th compact class="text-center">TOTAL ENTRADAS UNIDADES</x-th>
                    <x-th compact class="text-center">TOTAL ENTRADAS IMPORTE</x-th>
                    <x-th compact class="text-center">TOTAL SALIDAS UNIDADES</x-th>
                    <x-th compact class="text-center">TOTAL SALIDAS IMPORTE</x-th>
                    <x-th compact class="text-center">SALDO UNIDADES</x-th>
                    <x-th compact class="text-center">SALDO IMPORTE</x-th>
                </x-tr>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($reporte->detalles as $detalle)
                    @php $color = '#' . $reporte->colorGrupo($detalle->grupo_operativo); @endphp
                    <x-tr compact wire:key="detalle-{{ $detalle->id }}">
                        <x-th compact class="text-gray-800 whitespace-nowrap" style="background-color: {{ $color }}">
                            {{ $detalle->codigo_existencia }}
                            @if ($detalle->ins_kardex_id && $detalle->condicion !== 'cerrado')
                                @can(\App\Constants\Permisos::INSUMO_KARDEX_CREAR)
                                    <button type="button" title="Editar kardex (código, saldo inicial...)"
                                        class="ml-1 text-gray-500 hover:text-blue-700"
                                        @click="$wire.dispatch('editarInsumoKardex', { kardexId: {{ $detalle->ins_kardex_id }} })">
                                        <i class="fa fa-pencil text-xs"></i>
                                    </button>
                                @endcan
                            @endif
                        </x-th>
                        <x-th compact style="background-color: {{ $color }}">
                            @if ($detalle->ins_kardex_id)
                                <a class="underline text-blue-700 hover:text-red-700"
                                    href="{{ route('almacen.kardex.detalle', $detalle->ins_kardex_id) }}" target="_blank">
                                    {{ $detalle->nombre_producto }}
                                </a>
                            @else
                                <span class="text-gray-800">{{ $detalle->nombre_producto }}</span>
                            @endif
                        </x-th>
                        <x-td compact class="text-center capitalize">{{ $detalle->condicion ?? '-' }}</x-td>
                        <x-th compact class="text-center">{{ $detalle->unidad_medida }}</x-th>
                        <x-td compact class="text-right">{{ formatear_numero($detalle->total_entradas_unidades) }}</x-td>
                        <x-td compact class="text-right">{{ formatear_numero($detalle->total_entradas_importe) }}</x-td>
                        <x-td compact class="text-right">{{ formatear_numero($detalle->total_salidas_unidades) }}</x-td>
                        <x-td compact class="text-right">{{ formatear_numero($detalle->total_salidas_importe) }}</x-td>
                        <x-th compact class="text-right bg-[#DAEEF3] dark:bg-[#1e293b] dark:text-gray-100">
                            {{ formatear_numero($detalle->saldo_unidades) }}
                        </x-th>
                        <x-th compact class="text-right bg-[#DAEEF3] dark:bg-[#1e293b] dark:text-gray-100">
                            {{ formatear_numero($detalle->saldo_importe) }}
                        </x-th>
                    </x-tr>
                @empty
                    <x-tr>
                        <x-td colspan="10" class="text-center text-muted-foreground py-6">
                            Sin datos. Usa <b>Generar resumen</b> para armar el índice y el Excel.
                        </x-td>
                    </x-tr>
                @endforelse

                @if ($reporte->detalles->isNotEmpty())
                    <x-tr>
                        <x-th colspan="4" class="text-right">TOTAL</x-th>
                        <x-th class="text-right">{{ formatear_numero($reporte->detalles->sum('total_entradas_unidades')) }}</x-th>
                        <x-th class="text-right">{{ formatear_numero($total['entradas_importe']) }}</x-th>
                        <x-th class="text-right">{{ formatear_numero($reporte->detalles->sum('total_salidas_unidades')) }}</x-th>
                        <x-th class="text-right">{{ formatear_numero($total['salidas_importe']) }}</x-th>
                        <x-th class="text-right bg-[#DAEEF3] dark:bg-[#1e293b] dark:text-gray-100">
                            {{ formatear_numero($total['saldo_unidades']) }}
                        </x-th>
                        <x-th class="text-right bg-[#DAEEF3] dark:bg-[#1e293b] dark:text-gray-100">
                            {{ formatear_numero($total['saldo_importe']) }}
                        </x-th>
                    </x-tr>
                @endif
            </x-slot>
        </x-table>
    </x-card>
    <x-loading wire:loading wire:target="procesarKardexConsolidado" />
    {{-- Mismo modal de edición que el detalle y el listado de kardex --}}
    <livewire:almacen.kardex.insumo-kardex-form-component />
</div>
