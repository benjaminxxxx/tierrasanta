<div class="my-5">
    <x-table>
        <x-slot name="thead">
            <x-th>Número de Reporte</x-th>
            <x-th class="text-center">Año</x-th>
            <x-th class="text-center">Fecha de Creación</x-th>
            <x-th class="text-center">Tipo de Reporte</x-th>
            <x-th class="text-center">Categorías Incluidas</x-th>
            <x-th class="text-center">Acciones</x-th>
        </x-slot>
        <x-slot name="tbody">
            @forelse($insumoKardexReportes as $reporte)
                <x-tr>
                    <x-td>{{ $reporte->nombre }}</x-td>
                    <x-td class="text-center">{{ $reporte->anio }}</x-td>
                    <x-td class="text-center">{{ $reporte->created_at->format('d/m/Y') }}</x-td>
                    <x-td class="text-center">{{ $reporte->tipo_kardex }}</x-td>
                    <x-td class="text-center">
                        @if($reporte->categorias->isEmpty())
                            N/A
                        @else
                            {{ $reporte->categorias->pluck('categoria_codigo')->join(', ') }}
                        @endif
                    </x-td>
                    <x-td class="text-center">
                        <x-button href="{{ route('gestion_insumos.kardex.reporte',$reporte->id) }}">
                            <i class="fa fa-link"></i> Ver Reporte
                        </x-button>
                        <x-button variant="danger" wire:click="eliminarInsumoKardexReporte({{ $reporte->id }})">
                            <i class="fa fa-remove"></i> Eliminar
                        </x-button>
                    </x-td>
                </x-tr>
            @empty
                <x-tr>
                    <x-td colspan="6" class="text-center">
                        No hay reportes de kardex disponibles.
                    </x-td>
                </x-tr>
            @endforelse
        </x-slot>
    </x-table>

    <div class="mt-4">
        {{ $insumoKardexReportes->links() }}
    </div>
</div>