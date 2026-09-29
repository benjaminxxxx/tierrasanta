<div class="my-5">
    <x-table>
        <x-slot name="thead">
            <x-tr>
                <x-th>Reporte</x-th>
                <x-th class="text-center">Año</x-th>
                <x-th class="text-center">Tipo</x-th>
                <x-th class="text-center">Grupos operativos</x-th>
                <x-th class="text-center">Productos</x-th>
                <x-th class="text-center">Excel</x-th>
                <x-th class="text-center">Acciones</x-th>
            </x-tr>
        </x-slot>
        <x-slot name="tbody">
            @forelse($insumoKardexReportes as $reporte)
                <x-tr wire:key="reporte-{{ $reporte->id }}">
                    <x-td>
                        <div class="font-medium">{{ $reporte->nombre }}</div>
                        <div class="text-xs text-muted-foreground">Creado {{ $reporte->created_at->format('d/m/Y') }}</div>
                    </x-td>
                    <x-td class="text-center">{{ $reporte->anio }}</x-td>
                    <x-td class="text-center">
                        <x-badge :color="$reporte->tipo_kardex === 'negro' ? 'gray' : 'blue'" class="uppercase">
                            {{ $reporte->tipo_kardex }}
                        </x-badge>
                    </x-td>
                    <x-td class="text-center">
                        <div class="flex flex-wrap justify-center gap-1">
                            @forelse ($reporte->grupos_ordenados as $grupo)
                                <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium uppercase text-gray-800 border border-black/10"
                                    style="background-color: #{{ $reporte->colorGrupo($grupo) }}">
                                    {{ $grupo }}
                                </span>
                            @empty
                                <span class="text-xs text-muted-foreground">Sin grupos</span>
                            @endforelse
                        </div>
                    </x-td>
                    <x-td class="text-center">{{ $reporte->detalles_count ?: '-' }}</x-td>
                    <x-td class="text-center">
                        @if ($reporte->file && $reporte->generado_at)
                            <x-button size="sm" variant="success"
                                href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($reporte->file) }}" download>
                                <i class="fa fa-file-excel"></i> Descargar
                            </x-button>
                            <div class="mt-1 text-[11px] text-muted-foreground">{{ $reporte->generado_at->format('d/m/Y H:i') }}</div>
                        @else
                            <span class="text-xs text-muted-foreground">Sin generar</span>
                        @endif
                    </x-td>
                    <x-td class="text-center">
                        <div class="flex flex-wrap justify-center gap-2">
                            @can(\App\Constants\Permisos::INSUMO_KARDEX_REPORTE_VER)
                                <x-button size="sm" href="{{ route('almacen.kardex.reporte', $reporte->id) }}">
                                    <i class="fa fa-eye"></i> Ver
                                </x-button>
                            @endcan
                            @can(\App\Constants\Permisos::INSUMO_KARDEX_REPORTE_CREAR)
                                <x-button size="sm" variant="outline"
                                    @click="$wire.dispatch('editarInsumoKardexReporte', { reporteId: {{ $reporte->id }} })">
                                    <i class="fa fa-edit"></i> Editar
                                </x-button>
                            @endcan
                            @can(\App\Constants\Permisos::INSUMO_KARDEX_REPORTE_ELIMINAR)
                                <x-button size="sm" variant="danger"
                                    wire:click="eliminarInsumoKardexReporte({{ $reporte->id }})"
                                    wire:confirm="¿Eliminar el reporte {{ $reporte->nombre }}? Los kardex no se modifican.">
                                    <i class="fa fa-trash"></i>
                                </x-button>
                            @endcan
                        </div>
                    </x-td>
                </x-tr>
            @empty
                <x-tr>
                    <x-td colspan="7" class="text-center text-muted-foreground">
                        No hay reportes de kardex con estos filtros.
                    </x-td>
                </x-tr>
            @endforelse
        </x-slot>
    </x-table>

    <div class="mt-4">
        {{ $insumoKardexReportes->links() }}
    </div>
</div>
