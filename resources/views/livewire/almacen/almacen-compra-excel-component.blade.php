<div>
    {{-- Exportar: elegir el mes --}}
    <x-dialog-modal wire:model.live="mostrarExportar" maxWidth="lg">
        <x-slot name="title">Exportar compras a Excel</x-slot>
        <x-slot name="content">
            <p class="text-sm text-muted-foreground mb-3">
                El Excel trae las compras del mes (una fila por producto), la lista de productos ordenada (para elegir sin
                equivocarse), los proveedores por RUC y las listas de códigos. Sirve también como plantilla: agrega filas de
                compras nuevas, productos o proveedores nuevos y súbelo con "Importar Excel".
            </p>
            <div class="flex gap-3">
                <x-select wire:model="mes" label="Mes" class="w-auto">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}">{{ ucfirst(\Illuminate\Support\Carbon::create(2000, $m, 1)->translatedFormat('F')) }}</option>
                    @endforeach
                </x-select>
                <x-select wire:model="anio" label="Año" class="w-auto">
                    @foreach (range(now()->year, 2020) as $a)
                        <option value="{{ $a }}">{{ $a }}</option>
                    @endforeach
                </x-select>
            </div>
        </x-slot>
        <x-slot name="footer">
            <div class="flex gap-2">
                <x-button variant="secondary" wire:click="$set('mostrarExportar', false)">Cancelar</x-button>
                <x-button wire:click="exportar"><i class="fa fa-file-excel"></i> Descargar</x-button>
            </div>
        </x-slot>
    </x-dialog-modal>

    {{-- Importar: subir, revisar y confirmar --}}
    <x-dialog-modal wire:model.live="mostrarImportar" maxWidth="full">
        <x-slot name="title">Importar compras desde Excel</x-slot>
        <x-slot name="content">
            <div class="space-y-4">
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <x-label value="Excel de compras (descargado con Exportar)" />
                        <input type="file" wire:model="archivo" accept=".xlsx,.xls" class="block text-sm" />
                        <x-input-error for="archivo" />
                    </div>
                    <span wire:loading wire:target="archivo" class="text-sm text-muted-foreground"><i class="fa fa-spinner fa-spin"></i> Leyendo el Excel…</span>
                </div>

                @if ($analisis)
                    @php $r = $analisis['resumen']; @endphp
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <x-card><p class="text-xs text-muted-foreground">Nuevas (se registrarán)</p><p class="text-2xl font-bold text-green-600">{{ $r['nuevas'] }}</p><p class="text-xs">S/ {{ number_format($r['total_nuevas'], 2) }}</p></x-card>
                        <x-card><p class="text-xs text-muted-foreground">Ya registradas (se omiten)</p><p class="text-2xl font-bold">{{ $r['existentes'] }}</p></x-card>
                        <x-card><p class="text-xs text-muted-foreground">Con errores (se omiten)</p><p class="text-2xl font-bold {{ $r['con_error'] ? 'text-red-600' : '' }}">{{ $r['con_error'] }}</p></x-card>
                        <x-card><p class="text-xs text-muted-foreground">Se crearán</p><p class="text-sm"><b>{{ count($analisis['productos_nuevos']) }}</b> producto(s) · <b>{{ count($analisis['proveedores_nuevos']) }}</b> proveedor(es)</p></x-card>
                    </div>

                    @if ($analisis['productos_nuevos'] || $analisis['proveedores_nuevos'])
                        <div class="rounded-md border border-amber-300 bg-amber-50 dark:bg-amber-900/20 p-3 text-sm space-y-1">
                            @foreach ($analisis['productos_nuevos'] as $p)
                                <p>
                                    <i class="fa fa-box"></i> Producto nuevo: <b>{{ $p['descripcion'] }}</b> ({{ $p['categoria'] ?: 'sin categoría' }}, unidad {{ $p['unidad'] ?: '01' }})
                                    @if ($p['errores'])
                                        <span class="text-red-600">— {{ implode('; ', $p['errores']) }}</span>
                                    @endif
                                </p>
                            @endforeach
                            @foreach ($analisis['proveedores_nuevos'] as $v)
                                <p><i class="fa fa-truck"></i> Proveedor nuevo: <b>{{ $v['ruc'] }}</b> {{ $v['razon_social'] }}</p>
                            @endforeach
                        </div>
                    @endif

                    <div class="overflow-x-auto max-h-[55vh] overflow-y-auto border border-border rounded-md">
                        <table class="w-full text-sm">
                            <thead class="text-xs text-muted-foreground bg-muted sticky top-0">
                                <tr>
                                    <th class="text-left px-2 py-1.5">Estado</th>
                                    <th class="text-left">Fecha</th>
                                    <th class="text-left">Comprobante</th>
                                    <th class="text-left">Proveedor</th>
                                    <th class="text-left">Kardex</th>
                                    <th class="text-left">Productos</th>
                                    <th class="text-right px-2">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (collect($analisis['compras'])->sortBy(fn($c) => ['error' => 0, 'nueva' => 1, 'existente' => 2][$c['estado']]) as $c)
                                    <tr class="border-t border-border align-top {{ $c['estado'] === 'existente' ? 'opacity-50' : '' }}">
                                        <td class="px-2 py-1.5 whitespace-nowrap">
                                            @if ($c['estado'] === 'nueva')
                                                <x-badge color="green">Nueva</x-badge>
                                            @elseif ($c['estado'] === 'existente')
                                                <x-badge color="gray">Ya registrada</x-badge>
                                            @else
                                                <x-badge color="red">Con errores</x-badge>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap">{{ $c['fecha'] && !str_starts_with($c['fecha'], 'invalida') ? formatear_fecha($c['fecha']) : $c['fecha'] }}</td>
                                        <td class="whitespace-nowrap">{{ $c['tipo_texto'] }} {{ $c['serie'] ? $c['serie'] . '-' : '' }}{{ $c['numero'] }}</td>
                                        <td>
                                            {{ $c['proveedor'] ?? '—' }}
                                            <span class="block text-xs text-muted-foreground">{{ $c['ruc'] }}{{ $c['proveedor_nuevo'] ? ' · nuevo' : '' }}</span>
                                        </td>
                                        <td>{{ $c['kardex'] }}</td>
                                        <td>
                                            @foreach ($c['lineas'] as $l)
                                                <div class="text-xs {{ $l['errores'] ? 'text-red-600' : '' }}">
                                                    {{ $l['producto'] }}{{ !empty($l['producto_nuevo']) ? ' (nuevo)' : '' }}
                                                    · {{ $l['cantidad'] + 0 }} × {{ number_format((float) $l['precio'], 2) }} = <b>{{ number_format($l['subtotal'], 2) }}</b>
                                                    @if (!empty($l['sugerencias']))
                                                        <span class="text-muted-foreground">— ¿quisiste decir: {{ implode(', ', $l['sugerencias']) }}?</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @foreach ($c['errores'] as $e)
                                                <p class="text-xs text-red-600"><i class="fa fa-times-circle"></i> {{ $e }}</p>
                                            @endforeach
                                            @foreach ($c['avisos'] as $a)
                                                <p class="text-xs text-amber-700 dark:text-amber-400"><i class="fa fa-exclamation-triangle"></i> {{ $a }}</p>
                                            @endforeach
                                        </td>
                                        <td class="text-right px-2 font-semibold whitespace-nowrap">{{ number_format($c['total'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($r['con_error'])
                        <p class="text-xs text-muted-foreground">Las compras con errores no se registran: corrige el Excel (o agrega el producto/proveedor en sus hojas) y vuelve a subirlo.</p>
                    @endif
                @endif
            </div>
        </x-slot>
        <x-slot name="footer">
            <div class="flex gap-2">
                <x-button variant="secondary" wire:click="$set('mostrarImportar', false)">Cancelar</x-button>
                @if ($analisis && $analisis['resumen']['nuevas'])
                    <x-button wire:click="importar" wire:confirm="Se registrarán {{ $analisis['resumen']['nuevas'] }} compra(s) nuevas. ¿Confirmar?">
                        <i class="fa fa-check"></i> Registrar {{ $analisis['resumen']['nuevas'] }} compra(s)
                    </x-button>
                @endif
            </div>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading wire:target="exportar,importar" />
</div>
