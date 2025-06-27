<div>
    <x-loading wire:loading />
    <x-flex class="my-3">
        <x-h3>
            Registro de Entrega de venta
        </x-h3>
        <x-button @click="$wire.dispatch('crearRegistroVentaCochinilla')">
            <i class="fa fa-plus"></i> Registrar Entrega de Venta
        </x-button>
    </x-flex>
    <x-card>
        <x-spacing>
            <x-flex class="mb-4">
                <x-select-meses wire:model.live="mes" />
                <x-select-anios wire:model.live="anio" max="current" />
            </x-flex>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">Campo</x-th>
                        <x-th class="text-center">Fecha filtrado</x-th>
                        <x-th class="text-center">Cantidad seca</x-th>
                        <x-th class="text-center">Condición</x-th>
                        <x-th class="text-center">Cliente</x-th>
                        <x-th class="text-center">Item</x-th>
                        <x-th class="text-center">Fecha de venta</x-th>
                        <x-th class="text-center">Total de venta</x-th>
                        <x-th class="text-center">Observaciones</x-th>
                        <x-th class="text-center">Acciones</x-th>
                    </x-tr>
                </x-slot>

                <x-slot name="tbody">
                    @forelse ($registroEntregas as $venta)
                        <x-tr>
                            <x-td class="text-center">{{ $venta->campo }}</x-td>
                            <x-td class="text-center">{{ $venta->fecha_filtrado }}</x-td>
                            <x-td class="text-center">{{ number_format($venta->cantidad_seca, 2) }}</x-td>
                            <x-td class="text-center">{{ ucfirst($venta->condicion) }}</x-td>
                            <x-td class="text-center">{{ $venta->cliente }}</x-td>
                            <x-td class="text-center">{{ $venta->item }}</x-td>
                            <x-td class="text-center">{{ $venta->fecha_venta }}</x-td>
                            <x-td class="text-center">
                                <b>
                                    {{ $venta->total_venta ? 'S/ ' . number_format($venta->total_venta, 2) : '-' }}
                                </b>
                            </x-td>
                            <x-td>{{ $venta->observaciones ?? '-' }}</x-td>
                            <x-td class="text-center">
                                <x-flex>
                                    @if($venta->total_venta && !$venta->aprobado_facturacion)

                                        <x-button
                                            @click="$wire.dispatch('editarRegistroEntrega',{grupoVenta:'{{ $venta->grupo_venta }}'})"><i
                                                class="fa fa-edit"></i> Editar</x-button>

                                    @endif
                                    @if($venta->total_venta && $venta->aprobado_facturacion)

                                        <x-secondary-button class="whitespace-nowrap" 
                                            @click="$wire.dispatch('editarRegistroEntrega',{grupoVenta:'{{ $venta->grupo_venta }}','editable':false})"><i
                                                class="fa fa-edit"></i> Ver detalle</x-secondary-button>

                                    @endif
                                </x-flex>
                            </x-td>
                        </x-tr>
                    @empty
                        <x-tr>
                            <x-td colspan="100%" class="text-center text-gray-500">
                                No hay registros de ventas.
                            </x-td>
                        </x-tr>
                    @endforelse
                </x-slot>
            </x-table>
            <x-flex class="justify-end my-3 w-full">
                <x-h3>
                    Total Venta: {{ $totalVenta }}
                </x-h3>
            </x-flex>
            <div class="my-5">
                {{ $registroEntregas->links() }}
            </div>
        </x-spacing>
    </x-card>
    <livewire:cochinilla_ventas.cochinilla-venta-registro-entrega-form-component />
</div>