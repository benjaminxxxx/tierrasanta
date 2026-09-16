<div class="space-y-6">

    <!-- Breadcrumbs & Encabezado -->
    <x-flex class="justify-between items-center">
        <div>
            <x-title>
                Compras
            </x-title>
            <x-subtitle>
                Registro y control de compras a proveedores.
            </x-subtitle>
        </div>
        <x-flex class="gap-3">
            @can(App\Constants\Permisos::INSUMO_COMPRA_GESTIONAR)
                <x-button variant="primary" @click="$wire.dispatch('abrirFormularioNuevaCompra')">
                    <i class="fa fa-plus"></i> Nueva compra
                </x-button>
            @endcan
            <x-button variant="secondary" wire:click="exportToExcel">
                <i class="fa fa-file-excel"></i> Exportar Excel
            </x-button>
        </x-flex>
    </x-flex>

    <!-- Filtros de Búsqueda -->
    <x-card class="space-y-4">
        <x-flex class="justify-between">
            <x-flex>
                <div>
                    <x-input type="text" wire:model.live.debounce.300ms="search" placeholder="Serie o número..."
                        label="Buscar" class="w-auto" />
                </div>

                <div>
                    <x-select wire:model.live="tipoComprobante" label="Comprobante" class="w-auto">
                        <option value="">Todos los tipos</option>
                        <option value="01">Factura (01)</option>
                        <option value="03">Boleta (03)</option>
                        <option value="00">Nota de venta (00)</option>
                    </x-select>
                </div>

                <div>
                    <x-selector-dia wire:model.live="fechaDesde" label="Desde" class="w-auto" />
                </div>

                <div>
                    <x-selector-dia wire:model.live="fechaHasta" label="Hasta" class="w-auto" />
                </div>

            </x-flex>
            <x-flex>
                <div>
                    @if ($search || $tipoComprobante || $fechaDesde || $fechaHasta || $proveedorId)
                        <button wire:click="clearFilters"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium underline">
                            Limpiar todos los filtros
                        </button>
                    @endif
                </div>

                <div>
                    <x-toggle-switch :checked="$verEliminadas" label="Ver eliminadas" wire:model.live="verEliminadas" />
                </div>
            </x-flex>
        </x-flex>


    </x-card>

    @if ($verEliminadas)
        <div class="p-3 bg-amber-50 border-l-4 border-amber-400 text-amber-800 text-xs rounded-md">
            Mostrando compras dadas de baja. Solo puedes restaurarlas desde esta sección.
        </div>
    @endif

    <!-- Tabla de Resultados -->
    <x-card>
        <x-table>
            <x-slot name="thead">
                <x-tr>
                    <x-th sortable="fecha_emision">
                        Fecha
                    </x-th>
                    <x-th>Proveedor</x-th>
                    <x-th>Comprobante</x-th>
                    <x-th>Almacén</x-th>
                    <x-th>Total</x-th>
                    <x-th>
                        {{ $verEliminadas ? 'Fecha Eliminación' : 'Forma de Pago' }}
                    </x-th>
                    <x-th>Acciones</x-th>
                </x-tr>
            </x-slot>

            <x-slot name="tbody">
                @forelse($compras as $compra)
                    <x-tr>
                        <x-td class="font-medium whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($compra->fecha_emision)->format('d/m/Y') }}
                        </x-td>
                        <x-td>
                            {{ $compra->proveedor->persona->razon_social ?? ($compra->proveedor->persona->nombres ?? 'S/N') }}
                        </x-td>
                        <x-td>
                            <div class="flex flex-col">
                                <span class="font-medium">
                                    {{ $compra->tipo_comprobante_codigo === '01' ? 'Factura' : ($compra->tipo_comprobante_codigo === '03' ? 'Boleta' : 'Nota de Venta') }}
                                </span>
                                <span class="text-xs">
                                    {{ $compra->serie ? $compra->serie . '-' : '' }}{{ $compra->numero }}
                                </span>
                            </div>
                        </x-td>
                        <x-td class="">
                            {{ $compra->almacen->nombre ?? '-' }}
                        </x-td>
                        <x-td class="font-semibold">
                            {{ $compra->moneda }} {{ number_format($compra->total, 2) }}
                        </x-td>
                        <x-td class="text-center">
                            @if ($verEliminadas)
                                <span class="text-xs">
                                    {{ $compra->deleted_at?->format('d/m/Y H:i') }}
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $compra->forma_pago === 'contado' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ ucfirst($compra->forma_pago) }}
                                </span>
                            @endif
                        </x-td>
                        <x-td class="text-right whitespace-nowrap">
                            @if ($verEliminadas)
                                @can(App\Constants\Permisos::INSUMO_COMPRA_GESTIONAR)
                                    <x-button wire:click="restorePurchase({{ $compra->id }})"
                                        wire:confirm="¿Restaurar esta compra?" variant="warning">
                                        Restaurar
                                    </x-button>
                                @endcan
                            @else
                                <x-flex class="justify-end gap-2 items-center">
                                    @can(App\Constants\Permisos::INSUMO_COMPRA_GESTIONAR)
                                        <x-button
                                            @click="$wire.dispatch('abrirFormularioEditarCompra',{compraId:{{ $compra->id }}})"
                                            variant="secondary">
                                            <i class="fa fa-edit"></i>
                                        </x-button>
                                        <x-button wire:click="deletePurchase({{ $compra->id }})"
                                            wire:confirm="¿Eliminar esta compra? Podrás restaurarla luego."
                                            variant="danger">
                                            <i class="fa fa-trash"></i>
                                        </x-button>
                                    @endcan
                                </x-flex>
                            @endif
                        </x-td>
                    </x-tr>
                @empty
                    <x-tr>
                        <x-td colspan="100%" class="text-center">
                            No existen compras registradas con los filtros seleccionados.
                        </x-td>
                    </x-tr>
                @endforelse
            </x-slot>
        </x-table>
    </x-card>

    <!-- Paginación -->
    <div class="pt-2">
        {{ $compras->links() }}
    </div>
    <livewire:compras.compra-form-component />
    <x-loading wire:loading />
</div>
