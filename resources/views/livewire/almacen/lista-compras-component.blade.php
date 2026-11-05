{{-- Compras (DESIGN.md): lista con productos por comprobante, vista "Por producto" y eliminación con motivo auditada. --}}
@php
    $f = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '—';
    $n = fn($v, $dec = 2) => number_format((float) $v, $dec);
    $cant = fn($v) => rtrim(rtrim(number_format((float) $v, 4, '.', ','), '0'), '.');
    $tipoDoc = fn($c) => ['01' => 'Factura', '03' => 'Boleta', '00' => 'Nota de venta'][$c] ?? 'Comprobante';
    $comprobante = fn($c) => ($c->serie ? $c->serie . '-' : '') . $c->numero;
    $proveedor = fn($c) => $c->proveedor?->persona?->razon_social ?: ($c->proveedor?->persona?->nombres ?: 'S/N');
    $btn2 = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btnPrincipal = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25 disabled:opacity-50';
    $btnIcono = 'inline-flex h-8 w-8 items-center justify-center rounded-[10px] border border-mist bg-paper text-graphite hover:bg-fog hover:text-ink focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $input = 'h-9 w-full rounded-[10px] border border-mist bg-paper px-3 text-sm text-ink placeholder:text-steel focus:border-carmin focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $tab = fn($clave) => $vista === $clave ? 'border-carmin text-ink' : 'border-transparent text-graphite hover:text-ink';
    $kardex = fn($t) => $t === 'blanco'
        ? '<span class="rounded-[6px] border border-hairline px-1.5 py-0.5 text-[11px] font-medium text-graphite">Blanco</span>'
        : '<span class="rounded-[6px] bg-fog px-1.5 py-0.5 text-[11px] font-medium text-graphite">Negro</span>';
    $puedeGestionar = auth()->user()->can(App\Constants\Permisos::INSUMO_COMPRA_GESTIONAR);
    $hayFiltros = $search || $producto || $tipoComprobante || $fechaDesde || $fechaHasta || $proveedorId;
    $buscado = mb_strtoupper(str_replace(' ', '', trim($producto)));
@endphp

<div class="space-y-4 font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Compras</h1>
            <p class="text-sm text-graphite">Comprobantes de compra con sus productos. Busca por producto para ver en qué compras se compró y cuánto.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="{{ $btn2 }}" @click="$wire.dispatch('abrirExportarCompras')" title="Compras del mes + productos y proveedores: también es la plantilla para importar">
                <i class="fa fa-file-excel"></i> Exportar Excel
            </button>
            <button type="button" class="{{ $btn2 }}" @click="$wire.dispatch('abrirImportarCompras')" title="Sube el Excel: verás qué compras son nuevas antes de registrarlas">
                <i class="fa fa-file-import"></i> Importar Excel
            </button>
            @if ($puedeGestionar)
                <button type="button" class="{{ $btnPrincipal }}" @click="$wire.dispatch('abrirFormularioNuevaCompra')">
                    <i class="fa fa-plus"></i> Nueva compra
                </button>
            @endif
        </div>
    </div>

    {{-- Filtros --}}
    <div class="flex flex-wrap items-end gap-3 rounded-[10px] border border-mist bg-paper p-4">
        <label class="block w-64">
            <span class="mb-1 block text-sm font-medium text-ink">Producto</span>
            <input type="text" wire:model.live.debounce.300ms="producto" class="{{ $input }}" placeholder="Nombre del producto" />
        </label>
        <label class="block w-64">
            <span class="mb-1 block text-sm font-medium text-ink">Buscar</span>
            <input type="text" wire:model.live.debounce.300ms="search" class="{{ $input }}" placeholder="Serie, número, proveedor o producto" />
        </label>
        <div class="w-44">
            <x-select wire:model.live="tipoComprobante" label="Comprobante">
                <option value="">Todos</option>
                <option value="01">Factura (01)</option>
                <option value="03">Boleta (03)</option>
                <option value="00">Nota de venta (00)</option>
            </x-select>
        </div>
        <div class="w-40"><x-selector-dia wire:model.live="fechaDesde" label="Desde" /></div>
        <div class="w-40"><x-selector-dia wire:model.live="fechaHasta" label="Hasta" /></div>
        <div class="ml-auto flex items-center gap-4">
            @if ($hayFiltros)
                <button type="button" wire:click="clearFilters" class="text-sm text-graphite underline-offset-2 hover:text-ink hover:underline">Limpiar filtros</button>
            @endif
            <x-toggle-switch :checked="$verEliminadas" label="Ver eliminadas" wire:model.live="verEliminadas" />
        </div>
    </div>

    {{-- Vistas --}}
    <div class="flex gap-6 border-b border-hairline text-sm font-medium">
        <button type="button" wire:click="$set('vista', 'compras')" class="-mb-px border-b-2 pb-2 {{ $tab('compras') }}">Compras</button>
        <button type="button" wire:click="$set('vista', 'producto')" class="-mb-px border-b-2 pb-2 {{ $tab('producto') }}">Por producto</button>
    </div>

    @if ($verEliminadas)
        <p class="rounded-[6px] bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">
            <i class="fa fa-triangle-exclamation"></i> Mostrando compras eliminadas: no cuentan en el stock ni en el kardex. Se pueden restaurar con motivo.
        </p>
    @endif

    @if ($vista === 'producto' && !$porProducto)
        <div class="rounded-[10px] border border-mist bg-paper px-4 py-8 text-center text-sm text-graphite">
            Escribe el nombre del producto arriba para ver en qué compras se compró, cuánto y a qué costo.
        </div>
    @elseif ($porProducto)
        {{-- Por producto: totales y líneas --}}
        @if ($detalle['totales']->count() > 1)
            <p class="rounded-[6px] bg-blue-50 px-3 py-2 text-xs text-blue-800 dark:bg-blue-400/10 dark:text-blue-300">
                <i class="fa fa-circle-info"></i> "{{ $producto }}" coincide con {{ $detalle['totales']->count() }} productos distintos. Si son el mismo producto escrito de dos formas, sus kardex quedan separados.
            </p>
        @endif
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($detalle['totales'] as $t)
                <div class="rounded-[10px] border border-mist bg-paper p-4">
                    <p class="text-sm font-semibold text-ink">{{ $t['producto'] }}</p>
                    <p class="mt-1 text-xs text-pewter">{{ $t['compras'] }} {{ $t['compras'] === 1 ? 'compra' : 'compras' }}</p>
                    <div class="mt-2 flex items-baseline justify-between gap-2 tabular-nums">
                        <span class="text-lg font-semibold text-ink">{{ $cant($t['cantidad']) }} <span class="text-xs font-normal text-graphite">{{ $t['unidad'] }}</span></span>
                        <span class="text-sm text-graphite">S/ {{ $n($t['total']) }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="overflow-x-auto rounded-[10px] border border-mist bg-paper">
            <table class="w-full text-[13px]">
                <thead class="bg-fog text-left text-xs text-graphite">
                    <tr>
                        <th class="px-3 py-2 font-medium">Fecha</th>
                        <th class="px-3 py-2 font-medium">Comprobante</th>
                        <th class="px-3 py-2 font-medium">Proveedor</th>
                        <th class="px-3 py-2 font-medium">Producto</th>
                        <th class="px-3 py-2 text-right font-medium">Cantidad</th>
                        <th class="px-3 py-2 font-medium">Presentación</th>
                        <th class="px-3 py-2 text-right font-medium">En unidad base</th>
                        <th class="px-3 py-2 text-right font-medium">Precio unit.</th>
                        <th class="px-3 py-2 text-right font-medium">Total</th>
                        <th class="px-3 py-2 font-medium">Kardex</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($detalle['lineas'] as $d)
                        <tr wire:key="linea-{{ $d->id }}" class="border-t border-hairline hover:bg-fog">
                            <td class="whitespace-nowrap px-3 py-2 tabular-nums">{{ $f($d->compra->fecha_emision) }}</td>
                            <td class="whitespace-nowrap px-3 py-2">
                                <span class="text-graphite">{{ $tipoDoc($d->compra->tipo_comprobante_codigo) }}</span>
                                <span class="font-medium text-ink">{{ $comprobante($d->compra) }}</span>
                            </td>
                            <td class="px-3 py-2">{{ $proveedor($d->compra) }}</td>
                            <td class="px-3 py-2 font-medium text-ink">{{ $d->producto?->nombre_comercial ?? 'Producto eliminado' }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $cant($d->cantidad) }}</td>
                            <td class="px-3 py-2 text-graphite">{{ $d->nombre_presentacion ?: '—' }}</td>
                            <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums">{{ $cant($d->cantidad_base) }} <span class="text-xs text-pewter">{{ $d->producto?->tabla6?->alias }}</span></td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $n($d->costo_unitario, 4) }}</td>
                            <td class="px-3 py-2 text-right font-medium tabular-nums">{{ $d->compra->moneda }} {{ $n($d->total_linea) }}</td>
                            <td class="px-3 py-2">{!! $kardex($d->compra->tipo_kardex) !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-3 py-8 text-center text-graphite">No hay compras de "{{ $producto }}" con los filtros elegidos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        {{-- Compras con sus productos --}}
        <div class="overflow-x-auto rounded-[10px] border border-mist bg-paper">
            <table class="w-full text-[13px]">
                <thead class="bg-fog text-left text-xs text-graphite">
                    <tr>
                        <th class="px-3 py-2 font-medium">
                            <button type="button" wire:click="sortBy('fecha_emision')" class="inline-flex items-center gap-1 hover:text-ink">
                                Fecha <i class="fa fa-sort text-[10px] text-steel"></i>
                            </button>
                        </th>
                        <th class="px-3 py-2 font-medium">Comprobante</th>
                        <th class="px-3 py-2 font-medium">Proveedor</th>
                        <th class="px-3 py-2 font-medium">Productos</th>
                        <th class="px-3 py-2 text-right font-medium">
                            <button type="button" wire:click="sortBy('total')" class="inline-flex items-center gap-1 hover:text-ink">
                                Total <i class="fa fa-sort text-[10px] text-steel"></i>
                            </button>
                        </th>
                        <th class="px-3 py-2 font-medium">{{ $verEliminadas ? 'Eliminada' : 'Pago' }}</th>
                        <th class="px-3 py-2 text-right font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($compras as $compra)
                        <tr wire:key="compra-{{ $compra->id }}" class="border-t border-hairline align-top hover:bg-fog">
                            <td class="whitespace-nowrap px-3 py-2 tabular-nums">{{ $f($compra->fecha_emision) }}</td>
                            <td class="whitespace-nowrap px-3 py-2">
                                <p class="font-medium text-ink">{{ $comprobante($compra) }}</p>
                                <p class="text-xs text-pewter">{{ $tipoDoc($compra->tipo_comprobante_codigo) }} · {!! $kardex($compra->tipo_kardex) !!}</p>
                            </td>
                            <td class="px-3 py-2">
                                <p>{{ $proveedor($compra) }}</p>
                                <p class="text-xs text-pewter">{{ $compra->almacen->nombre ?? '' }}</p>
                            </td>
                            <td class="px-3 py-2">
                                <ul class="space-y-0.5">
                                    @foreach ($compra->detalles as $d)
                                        @php $coincide = $buscado !== '' && str_contains(mb_strtoupper(str_replace(' ', '', (string) $d->producto?->nombre_comercial)), $buscado); @endphp
                                        <li class="flex items-baseline justify-between gap-3">
                                            <button type="button" wire:click="verProducto(@js($d->producto?->nombre_comercial ?? ''))"
                                                class="text-left hover:underline {{ $coincide ? 'font-semibold text-ink' : 'text-graphite' }}"
                                                title="Ver todas las compras de este producto">
                                                {{ $d->producto?->nombre_comercial ?? 'Producto eliminado' }}
                                            </button>
                                            <span class="whitespace-nowrap tabular-nums {{ $coincide ? 'font-semibold text-ink' : 'text-graphite' }}">
                                                {{ $cant($d->cantidad) }} {{ $d->nombre_presentacion ?: $d->producto?->tabla6?->alias }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-right font-semibold tabular-nums">{{ $compra->moneda }} {{ $n($compra->total) }}</td>
                            <td class="px-3 py-2">
                                @if ($verEliminadas)
                                    @php $e = $eliminaciones[$compra->id] ?? null; @endphp
                                    <p class="whitespace-nowrap tabular-nums">{{ $compra->deleted_at?->format('d/m/Y H:i') }}</p>
                                    @if ($e)
                                        <p class="text-xs text-graphite">{{ $e['usuario'] ?? 'Sistema' }}</p>
                                        <p class="max-w-[16rem] text-xs text-pewter">{{ $e['motivo'] }}</p>
                                    @endif
                                @else
                                    <span class="rounded-[6px] px-2 py-0.5 text-xs font-medium {{ $compra->forma_pago === 'contado' ? 'bg-green-50 text-green-700 dark:bg-green-400/10 dark:text-green-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-400' }}">
                                        {{ ucfirst($compra->forma_pago) }}
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-2">
                                <div class="flex justify-end gap-1.5">
                                    <button type="button" wire:click="verHistorial({{ $compra->id }})" class="{{ $btnIcono }}" title="Historial">
                                        <i class="fa fa-clock-rotate-left text-xs"></i>
                                    </button>
                                    @if ($puedeGestionar)
                                        @if ($verEliminadas)
                                            <button type="button" wire:click="pedirMotivo({{ $compra->id }}, 'restaurar')" class="{{ $btn2 }} !h-8">
                                                <i class="fa fa-rotate-left text-xs"></i> Restaurar
                                            </button>
                                        @else
                                            <button type="button" @click="$wire.dispatch('abrirFormularioEditarCompra', {compraId: {{ $compra->id }}})" class="{{ $btnIcono }}" title="Editar">
                                                <i class="fa fa-pen text-xs"></i>
                                            </button>
                                            <button type="button" wire:click="pedirMotivo({{ $compra->id }}, 'eliminar')" class="{{ $btnIcono }} hover:!text-red-700 dark:hover:!text-red-400" title="Eliminar">
                                                <i class="fa fa-trash text-xs"></i>
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-8 text-center text-graphite">No hay compras con los filtros elegidos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $compras->links() }}</div>
    @endif

    {{-- Modal: eliminar / restaurar con motivo --}}
    <x-dialog-modal wire:model="modalMotivo" maxWidth="lg">
        <x-slot name="title">{{ $accionMotivo === 'restaurar' ? 'Restaurar compra' : 'Eliminar compra' }}</x-slot>
        <x-slot name="content">
            @if ($compraMotivo)
                <div class="space-y-4 text-sm">
                    <div class="rounded-[10px] border border-mist bg-fog px-3 py-2">
                        <p class="font-medium text-ink">{{ $tipoDoc($compraMotivo->tipo_comprobante_codigo) }} {{ $comprobante($compraMotivo) }} · {{ $f($compraMotivo->fecha_emision) }}</p>
                        <p class="text-graphite">{{ $proveedor($compraMotivo) }} · {{ $compraMotivo->moneda }} {{ $n($compraMotivo->total) }}</p>
                        <ul class="mt-2 space-y-0.5 border-t border-hairline pt-2">
                            @foreach ($compraMotivo->detalles as $d)
                                <li class="flex justify-between gap-3">
                                    <span class="text-ink">{{ $d->producto?->nombre_comercial }}</span>
                                    <span class="tabular-nums text-graphite">{{ $cant($d->cantidad) }} {{ $d->nombre_presentacion }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <p class="text-graphite">
                        @if ($accionMotivo === 'restaurar')
                            La compra vuelve a contar en el stock y en el kardex de todos sus productos.
                        @else
                            Se elimina la compra completa con <strong class="font-semibold text-ink">todos</strong> sus productos y se descuenta su stock.
                            No se borra: queda en "Ver eliminadas" y en la auditoría con quién la eliminó y por qué.
                            Si solo un producto está mal, mejor edita la compra.
                        @endif
                    </p>
                    <label class="block">
                        <span class="mb-1 block font-medium text-ink">Motivo *</span>
                        <input type="text" wire:model="motivo" wire:keydown.enter="confirmarMotivo" class="{{ $input }}"
                            placeholder="{{ $accionMotivo === 'restaurar' ? 'Ej.: se eliminó por error' : 'Ej.: producto mal registrado (ULTRA FERRO en vez de ULTRAFERRO)' }}" />
                        @error('motivo') <span class="mt-1 block text-xs text-red-700 dark:text-red-400"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span> @enderror
                    </label>
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="{{ $btn2 }}" wire:click="$set('modalMotivo', false)">Cancelar</button>
            <button type="button" class="{{ $btnPrincipal }} ml-2" wire:click="confirmarMotivo" wire:loading.attr="disabled" wire:target="confirmarMotivo">
                <i class="fa fa-check"></i> {{ $accionMotivo === 'restaurar' ? 'Restaurar' : 'Eliminar compra' }}
            </button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal: historial --}}
    <x-dialog-modal wire:model="modalHistorial" maxWidth="lg">
        <x-slot name="title">Historial de la compra {{ $historialTitulo }}</x-slot>
        <x-slot name="content">
            <ul class="divide-y divide-hairline text-sm">
                @forelse ($historial as $h)
                    <li class="py-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="rounded-[6px] px-2 py-0.5 text-xs font-medium {{ $h['accion'] === 'eliminar' ? 'bg-red-50 text-red-700 dark:bg-red-400/10 dark:text-red-400' : 'bg-fog text-graphite' }}">{{ ucfirst($h['accion']) }}</span>
                            <span class="text-xs text-pewter">{{ $h['fecha']?->format('d/m/Y H:i') }} · {{ $h['usuario'] ?? 'Sistema' }}</span>
                        </div>
                        @if ($h['motivo'])
                            <p class="mt-1 text-graphite">Motivo: {{ $h['motivo'] }}</p>
                        @endif
                        @if ($h['lineas'])
                            <ul class="mt-1 space-y-0.5 text-xs text-graphite">
                                @foreach ($h['lineas'] as $l)
                                    <li class="flex justify-between gap-3"><span>{{ $l['producto'] }}</span><span class="tabular-nums">{{ $cant($l['cantidad']) }} {{ $l['presentacion'] }} · {{ $n($l['total_linea']) }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @empty
                    <li class="py-3 text-graphite">Sin registros en la auditoría (las compras anteriores a este cambio no guardaban historial).</li>
                @endforelse
            </ul>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="{{ $btn2 }}" wire:click="$set('modalHistorial', false)">Cerrar</button>
        </x-slot>
    </x-dialog-modal>

    <livewire:almacen.compra-form-component />
    <livewire:almacen.almacen-compra-excel-component />
    <x-loading wire:loading wire:target="confirmarMotivo,verHistorial,verProducto,clearFilters" />
</div>
