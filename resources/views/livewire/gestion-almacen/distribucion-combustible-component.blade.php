{{-- Un solo x-data cubre toda la vista incluyendo el modal --}}
<div x-data="distribucionCombustible()" x-init="init()" class="space-y-4">
    <x-flex class="justify-between">
        <x-title>Distribución de Combustible</x-title>
        <x-button variant="success" @click="$wire.dispatch('descargarReporteDistribuciones')">
            <i class="fas fa-download"></i> Descargar Reporte
        </x-button>
    </x-flex>

    {{-- Filtros --}}
    <x-card>
        <x-flex class="justify-between w-full">
            @include('comun.selector-mes-base')
            <x-flex>
                <div class="mt-4">
                    <x-label value="Maquinaria" />
                    <x-select-dropdown wire:model="filtroMaquinariaId" source="getMaquinarias"
                        placeholder="Filtrar por maquinaria" />
                </div>
            </x-flex>
        </x-flex>
    </x-card>

    {{-- Tabla principal --}}
    <x-card class="overflow-x-auto">
        <table class="w-full text-sm border-separate border-spacing-0">
            <thead>
                <tr class="text-left text-xs uppercase text-muted-foreground">
                    <th class="py-2 px-3 border-b border-border">Fecha</th>
                    <th class="py-2 px-3 border-b border-border">Maquinaria / Campo</th>
                    <th class="py-2 px-3 border-b border-border text-right">Inicio</th>
                    <th class="py-2 px-3 border-b border-border text-right">Fin</th>
                    <th class="py-2 px-3 border-b border-border text-right">Horas</th>
                    <th class="py-2 px-3 border-b border-border text-right">Cant. Comb.</th>
                    <th class="py-2 px-3 border-b border-border text-right">Costo Comb.</th>
                    <th class="py-2 px-3 border-b border-border text-right">Ingreso</th>
                    <th class="py-2 px-3 border-b border-border">Labor</th>
                    <th class="py-2 px-3 border-b border-border text-right">Precio</th>
                    <th class="py-2 px-3 border-b border-border text-right">Ratio</th>
                    <th class="py-2 px-3 border-b border-border text-right">Costo</th>
                    <th class="py-2 px-3 border-b border-border"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($filas as $fila)
                    @if($fila['es_salida'])
                        {{-- ── FILA SALIDA (cabecera de grupo) ─────────── --}}
                        <tr
                            class="bg-blue-50 dark:bg-blue-900/30 font-semibold
                                                           text-blue-800 dark:text-blue-200 border-t-2 border-blue-300 dark:border-blue-700">
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 whitespace-nowrap">
                                <span class="inline-block w-2 h-2 rounded-full bg-blue-500 mr-1 align-middle"></span>
                                {{ \Carbon\Carbon::parse($fila['fecha'])->format('d/m/Y') }}
                            </td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800">
                                {{ $fila['maquinaria_nombre'] }}
                                <span class="ml-2 text-xs font-normal text-blue-400">
                                    {{ $fila['n_distribuciones'] }} dist.
                                    · {{ number_format($fila['horas_total'], 1) }}h
                                </span>
                            </td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right text-blue-400">—</td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right text-blue-400">—</td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right text-blue-400">—</td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right text-blue-400">—</td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right text-blue-400">—</td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right font-mono">
                                {{ number_format($fila['ingreso_salida'], 2) }}
                            </td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-blue-400">—</td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right font-mono">
                                S/ {{ number_format($fila['precio'], 4) }}
                            </td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right text-blue-400">—</td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right font-mono">
                                S/ {{ number_format($fila['costo'], 2) }}
                            </td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right">
                                @can(\App\Constants\Permisos::INSUMO_DISTRIBUCION_GESTIONAR)
                                    <x-button @click="$wire.dispatch('abrirModalDistribucion',{salidaId:{{ $fila['salida_id'] }}})"
                                        size="xs">
                                        <i class="fa fa-sliders"></i> Gestionar
                                    </x-button>
                                @endcan

                            </td>
                        </tr>
                    @else
                        {{-- ── FILA DISTRIBUCIÓN (hija) ─────────────────── --}}
                        <tr class="hover:bg-muted/30 text-card-foreground">
                            <td class="py-1.5 px-3 border-b border-border pl-8 text-xs text-muted-foreground whitespace-nowrap">
                                <span class="text-muted-foreground mr-1">↳</span>
                                {{ \Carbon\Carbon::parse($fila['fecha'])->format('d/m/Y') }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs">
                                {{ $fila['campo_nombre'] ?? '—' }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right font-mono">
                                {{ $fila['hora_inicio'] ?? '—' }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right font-mono">
                                {{ $fila['hora_fin'] ?? '—' }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right font-mono">
                                {{ $fila['n_horas'] !== null ? number_format($fila['n_horas'], 2) : '—' }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right font-mono">
                                {{ $fila['cant_combustible'] !== null ? number_format($fila['cant_combustible'], 3) : '—' }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right font-mono">
                                {{ $fila['costo_combustible'] !== null ? 'S/ ' . number_format($fila['costo_combustible'], 4) : '—' }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right text-muted-foreground">—</td>
                            <td class="py-1.5 px-3 border-b border-border text-xs max-w-[180px] truncate">
                                {{ $fila['labor_diaria'] ?? '—' }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right font-mono">
                                S/ {{ number_format($fila['precio'], 4) }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right font-mono">
                                {{ $fila['ratio'] !== null ? number_format($fila['ratio'] * 100, 2) . '%' : '—' }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right font-mono">
                                S/ {{ number_format($fila['costo'], 4) }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border">
                                @can(\App\Constants\Permisos::INSUMO_DISTRIBUCION_GESTIONAR)
                                    <x-button wire:click="eliminarDistribucion({{ $fila['id'] }})" size="xs" variant="danger">
                                        <i class="fa fa-remove"></i>
                                    </x-button>
                                @endcan
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="13" class="py-10 text-center text-sm text-muted-foreground">
                            No hay salidas de combustible para el período seleccionado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-card>

    <x-loading wire:loading />
</div>

@script
<script>
    Alpine.data('distribucionCombustible', () => ({
        init() {

        }
    }));
</script>
@endscript