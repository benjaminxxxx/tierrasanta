<div x-data="insumoKardexCrearComponent" class="space-y-4">

    <div>
        <x-title>Crear Kardex de Insumos</x-title>
        <x-subtitle>Completa el formulario para generar un nuevo kardex de insumos.</x-subtitle>
    </div>

    <x-card>

        {{-- Paso 1: buscador + crear --}}
        @if(!$producto)
            <p class="text-xs font-medium text-card-foreground uppercase tracking-wide mb-4">
                Paso 1 — Selecciona o crea un producto
            </p>

            <div class="flex gap-6 items-start">

                {{-- Mitad izquierda: buscador --}}
                <div class="flex-1">
                    <x-label value="Buscar producto existente" />
                    <x-select-dropdown wire:model="productoId" source="getProductos"
                        placeholder="Nombre comercial o ingrediente activo…" />
                </div>

                {{-- Separador --}}
                <div class="self-stretch w-px bg-muted mx-2"></div>

                {{-- Mitad derecha: crear --}}
                <div class="flex-1 flex flex-col items-center justify-center gap-3 py-2">
                    <p class="text-sm text-gray-500 text-center">¿El producto no existe aún?</p>
                    <x-button @click="$wire.dispatch('CrearProducto')">
                        <i class="fa fa-plus mr-1"></i> Crear nuevo producto
                    </x-button>
                </div>

            </div>
        @endif

        @if($producto)
            {{-- Card producto --}}
            <div class="mt-4 border rounded-xl p-4 space-y-4
                    {{ $producto->trashed()
            ? 'border-red-300 bg-red-50 dark:bg-red-950 dark:border-red-800'
            : 'border-border bg-muted' }}">

                {{-- Cabecera --}}
                <div class="flex justify-between items-start">
                    <div class="space-y-1">
                        @if($producto->trashed())
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600
                                                         bg-red-100 dark:bg-red-900 dark:text-red-300
                                                         border border-red-200 dark:border-red-700
                                                         px-2 py-0.5 rounded-md">
                                <i class="fa fa-trash text-[10px]"></i> Producto eliminado
                            </span>
                        @endif
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                            Producto seleccionado
                        </p>
                        <p class="text-base font-medium">{{ $producto->nombre_comercial }}</p>
                    </div>

                    <x-flex>
                        @if($producto->trashed())
                            <x-button wire:click="restaurarProducto" variant="success">
                                <i class="fa fa-undo"></i> Restaurar
                            </x-button>
                        @else
                            <x-button @click="$wire.dispatch('EditarProducto', { id: {{ $producto->id }} })">
                                <i class="fa fa-edit"></i> Editar
                            </x-button>
                        @endif
                        <x-button wire:click="quitarProducto" variant="danger">
                            <i class="fa fa-sync"></i> Cambiar
                        </x-button>
                    </x-flex>
                </div>

                {{-- Datos principales --}}
                <div class="border-t border-border pt-3 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-muted-foreground mb-1">Ingrediente activo</p>
                        <p>{{ $producto->ingrediente_activo ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground mb-1">Unidad de medida</p>
                        <p>{{ $producto->unidad_medida }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground mb-1">Categoría</p>
                        <p>{{ $producto->categoria?->descripcion ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground mb-1">Subcategoría</p>
                        <p>{{ $producto->subcategoria?->nombre ?? '—' }}</p>
                    </div>
                </div>

                {{-- Nutrientes --}}
                @if($producto->nutrientes->isNotEmpty())
                    <div class="border-t border-border pt-3">
                        <p class="text-xs text-muted-foreground mb-2">Nutrientes</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            @foreach($producto->nutrientes as $nutriente)
                                <div class="flex items-center justify-between
                                                    bg-background border border-border
                                                    rounded-lg px-3 py-2 text-sm">
                                    <span class="text-foreground">{{ $nutriente->nombre }}</span>
                                    <span class="text-xs font-medium text-muted-foreground ml-2 shrink-0">
                                        {{ $nutriente->pivot->porcentaje }}%
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Usos --}}
                <div class="border-t border-border pt-3">
                    <p class="text-xs text-muted-foreground mb-2">Usos</p>
                    @if($producto->usos->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach($producto->usos as $uso)
                                <span
                                    class="text-xs px-2 py-0.5 rounded-md
                                                                                     bg-background border border-border text-foreground">
                                    {{ $uso->nombre }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-muted-foreground">—</p>
                    @endif
                </div>

                {{-- Info eliminación --}}
                @if($producto->trashed())
                    <div class="border-t border-red-200 dark:border-red-800 pt-3
                                                grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-muted-foreground mb-1">Eliminado por</p>
                            <p class="text-red-700 dark:text-red-300 font-medium">
                                {{ $producto->eliminador?->name ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground mb-1">Fecha eliminación</p>
                            <p class="text-red-700 dark:text-red-300">
                                {{ $producto->deleted_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Subboxes kardex --}}
            <div class="mt-5">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Kardex disponibles</p>

                <div class="flex flex-wrap gap-3 items-stretch">

                    @foreach($kardexAgrupados as $grupo)
                            @php
                                $esAnioActivo = ($grupo['blanco'] && $grupo['blanco']->id === $kardexId)
                                    || ($grupo['negro'] && $grupo['negro']->id === $kardexId);
                            @endphp

                            <div class="border rounded-xl p-3 flex flex-col gap-2 min-w-[110px] transition
                                                                                    {{ $esAnioActivo
                        ? 'border-blue-400 ring-2 ring-blue-100 dark:ring-blue-900'
                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300' }}">

                                <span class="text-sm font-medium">Kardex {{ $grupo['anio'] }}</span>

                                <div class="flex gap-2">
                                    @if($grupo['blanco'])
                                                <button wire:click="seleccionarKardex({{ $grupo['blanco']->id }}, 'blanco')"
                                                    class="text-xs px-3 py-1 rounded-lg border font-medium transition
                                                                                                                                                                {{ $kardexId === $grupo['blanco']->id && $tipoKardex === 'blanco'
                                        ? 'bg-blue-100 border-blue-400 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
                                        : 'border-border hover:bg-blue-50 hover:border-blue-300' }}">B</button>
                                    @endif

                                    @if($grupo['negro'])
                                                <button wire:click="seleccionarKardex({{ $grupo['negro']->id }}, 'negro')"
                                                    class="text-xs px-3 py-1 rounded-lg border font-medium transition
                                                                                                                                                                {{ $kardexId === $grupo['negro']->id && $tipoKardex === 'negro'
                                        ? 'bg-gray-800 border-gray-600 text-gray-100'
                                        : 'border-border hover:bg-gray-800 hover:text-gray-100' }}">N</button>
                                    @endif
                                </div>
                            </div>
                    @endforeach

                    {{-- Nuevo kardex --}}
                    <button @click="$wire.dispatch('nuevoInsumoKardex', { productoId: {{ $producto->id }} })" class="border border-dashed border-gray-300 dark:border-gray-600 rounded-xl px-4 py-3
                                               flex flex-col items-center justify-center gap-1 text-gray-400
                                               hover:text-gray-600 hover:border-gray-400 hover:bg-gray-50
                                               dark:hover:bg-gray-800 transition min-w-[90px]">
                        <span class="text-lg leading-none">+</span>
                        <span class="text-xs">Nuevo kardex</span>
                    </button>

                </div>

                {{-- Tag selección --}}
                @if($kardexId && $tipoKardex)
                    @php
                        $anioSeleccionado = collect($kardexAgrupados)->first(
                            fn($g) => ($g['blanco']?->id === $kardexId || $g['negro']?->id === $kardexId)
                        )['anio'] ?? '';
                    @endphp
                    <div class="mt-3 flex items-center gap-2">
                        <span class="text-xs text-gray-400">Seleccionado:</span>
                        <span
                            class="text-xs px-2 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900 dark:text-blue-200">
                            Kardex {{ $anioSeleccionado }} · {{ ucfirst($tipoKardex) }}
                        </span>
                    </div>
                @endif
            </div>
        @endif

    </x-card>

    @if ($kardexId && $tipoKardex)
        <livewire:gestion-insumos.insumo-kardex-detalle-component :insumoKardexId="$kardexId" wire:key="kardex{{ $kardexId }}{{ $tipoKardex }}"/>
    @endif

    <x-loading wire:loading />
</div>

@script
<script>
    Alpine.data('insumoKardexCrearComponent', () => ({
        init() { }
    }))
</script>
@endscript