{{-- Un solo x-data cubre toda la vista incluyendo el modal --}}
<div x-data="distribucionCombustibleReporte()" class="space-y-4">

    {{-- Modal gestión distribuciones --}}
    <x-dialog-modal wire:model.live="modalDistribucionReporte" maxWidth="full">
        <x-slot name="title">
            Gestión de distribuciones de combustible - Reporte
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <x-flex class="items-end gap-4">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <x-label value="Año" />
                            <x-select-anios wire:model.live="anioSeleccionado" class="w-full" />
                        </div>
                        <div>
                            <x-label value="Mes (Opcional)" />
                            <x-select-meses wire:model.live="mesSeleccionado" class="w-full" />
                        </div>
                        <div>
                            <x-label value="Combustible" />
                            <x-select wire:model.live="productoSeleccionado" class="w-full">
                                <option value="">Todos los combustibles</option>
                                @foreach ($productos as $id => $nombre)
                                    <option value="{{ $id }}">{{ $nombre }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div>
                            <x-label value="Maquinaria" />
                            <x-select wire:model.live="maquinariaSeleccionada" class="w-full">
                                <option value="">Todas las maquinarias</option>
                                @foreach ($maquinarias as $id => $nombre)
                                    <option value="{{ $id }}">{{ $nombre }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                    <x-button wire:click="exportarExcel" variant="success">
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </x-button>
                </x-flex>

                <div class="h-[350px] overflow-auto border border-border rounded-lg shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-800 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Fecha</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Maquinaria</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Actividad / Labor</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Campo</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Cant. (Gln/Kg)</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Costo Unit.</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Subtotal</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Horas</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($filas as $fila)
                                @php
                                    $rowClass = $fila['es_salida']
                                        ? 'bg-blue-50 dark:bg-blue-900/30 font-semibold text-blue-900 dark:text-blue-200'
                                        : 'hover:bg-gray-50 dark:hover:bg-slate-800 text-gray-700 dark:text-gray-300';
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @if(!$fila['es_salida']) <span class="text-gray-400 mr-1">↳</span> @endif
                                        {{ \Carbon\Carbon::parse($fila['fecha'])->format('d/m/Y') }}
                                    </td>
                                    <td class="px-3 py-2">{{ $fila['maquinaria'] }}</td>
                                    <td class="px-3 py-2 text-xs">{{ $fila['actividad'] }}</td>
                                    <td class="px-3 py-2">{{ $fila['campo'] }}</td>
                                    <td class="px-3 py-2 text-right font-mono">{{ number_format($fila['cantidad'], 2) }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-500">
                                        {{ number_format($fila['costo_unitario'], 3) }}</td>
                                    <td
                                        class="px-3 py-2 text-right font-semibold {{ $fila['es_salida'] ? '' : 'text-blue-600' }}">
                                        S/ {{ number_format($fila['total_costo'], 2) }}
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        {{ number_format($fila['horas_total'], 2) }}h
                                        @if($fila['ratio'])
                                            <span
                                                class="text-[10px] block text-gray-400">({{ number_format($fila['ratio'] * 100, 1) }}%)</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Información Estadística --}}
                @if(count($filas) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-100 rounded-lg">
                        <div class="flex flex-col">
                            <span class="text-xs text-gray-500 uppercase">Total Cantidad Combustible</span>
                            <span class="text-lg font-bold text-gray-800">
                                {{ number_format(collect($filas)->where('es_salida', true)->sum('cantidad'), 2) }}
                            </span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs text-gray-500 uppercase">Inversión Total</span>
                            <span class="text-lg font-bold text-green-700">
                                S/ {{ number_format(collect($filas)->where('es_salida', true)->sum('total_costo'), 2) }}
                            </span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs text-gray-500 uppercase">Total Horas Registradas</span>
                            <span class="text-lg font-bold text-blue-700">
                                {{ number_format(collect($filas)->where('es_salida', false)->sum('horas_total'), 2) }} h
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalDistribucionReporte', false)">
                Cerrar
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>

@script
<script>
    Alpine.data('distribucionCombustibleReporte', () => ({


        init() {

        }
    }));
</script>
@endscript