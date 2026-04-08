<div class="space-y-4">
    <x-flex>
        <x-title>Gestión de categorías</x-title>
        <x-button href="{{ route('subcategorias.index') }}">
            Administrar subcategorías ↗
        </x-button>
    </x-flex>

    <div class="grid grid-cols-3 gap-3">
        <x-metric label="Categorías" :value="$categorias->count()" />
        <x-metric label="Subcategorías" :value="$categorias->sum(fn($c) => $c->subcategorias->count())" />
        <x-metric label="Productos" :value="$categorias->sum(fn($c) => $c->insumos->count())" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($categorias as $categoria)
            <x-card>
                <div class="flex justify-between items-start mb-2">
                    <span class="font-medium text-sm">{{ $categoria->descripcion }}</span>
                    <x-button wire:click="verProductosCategoria('{{ $categoria->codigo }}')"
                        variant="info" size="xs">
                        {{ $categoria->insumos->count() }} productos
                    </x-button>
                </div>
                <p class="text-card-foreground mb-1">{{ $categoria->definicion }}</p>
                <p class="text-sm text-white/80 italic mb-3">{{ $categoria->criterio_uso }}</p>

                <p class="text-xs font-medium uppercase tracking-wide text-card-foreground mb-2">
                    Subcategorías
                </p>
                @forelse($categoria->subcategorias as $sub)
                    <div class="text-sm px-2 py-1.5 bg-muted rounded mb-1">

                        <x-flex class="justify-between">
                            <div>
                                {{ $sub->nombre }}
                            </div>
                            <div>
                                <x-button variant="success" size="xs" wire:click="verListaProductos({{ $sub->id }})">
                                    Ver productos ({{ $sub->productos->count() }})
                                </x-button>
                            </div>
                        </x-flex>
                    </div>
                @empty
                    <p class="text-xs text-card-foreground">Sin subcategorías registradas</p>
                @endforelse
            </x-card>
        @endforeach
    </div>
    <x-dialog-modal wire:model.live="modalProductosCategoria">
        <x-slot name="title">
            {{ $categoriaActivaNombre }}
        </x-slot>

        <x-slot name="content">
            @if(count($productosCategoriaSel) > 0)
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs font-medium uppercase tracking-wide text-card-foreground border-b border-muted">
                            <th class="text-left py-2 pr-4">Producto</th>
                            <th class="text-left py-2 pr-4">Categoría</th>
                            <th class="text-left py-2">Subcategoría</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productosCategoriaSel as $p)
                            <tr class="border-b border-muted last:border-0">
                                <td class="py-2 pr-4">{{ $p['nombre'] }}</td>
                                <td class="py-2 pr-4">{{ $p['categoria'] }}</td>
                                <td class="py-2">
                                    @if($p['subcategoria'])
                                        {{ $p['subcategoria'] }}
                                    @else
                                        <span class="text-xs text-card-foreground italic">Sin subcategoría</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-sm text-card-foreground">Sin productos en esta categoría.</p>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalProductosCategoria', false)">
                Cerrar
            </x-button>
        </x-slot>
    </x-dialog-modal>
    {{-- Modal --}}
    <x-dialog-modal wire:model.live="modalProductos">
        <x-slot name="title">
            Productos — {{ $subcategoriaNombre }}
        </x-slot>

        <x-slot name="content">
            <div class="mb-4">
                <x-label value="Agregar producto" />
                <x-select-dropdown wire:model.live="productoSeleccionado" source="getProductos"
                    placeholder="Buscar producto..." />
            </div>

            <p class="text-xs font-medium uppercase tracking-wide text-card-foreground mb-2">
                Productos asignados
            </p>

            @forelse($productosModal as $p)
                <div class="flex items-center justify-between px-3 py-2 bg-muted rounded mb-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm">{{ $p['nombre'] }}</span>

                        @if($p['es_nuevo'] && $p['categoria_anterior'])
                            <span class="text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full">
                                venía de: {{ $p['categoria_anterior'] }}
                            </span>
                        @elseif($p['es_nuevo'])
                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">
                                nuevo
                            </span>
                        @endif
                    </div>
                    <x-button variant="danger" size="xs" wire:click="quitarProducto({{ $p['id'] }})">
                        Quitar
                    </x-button>
                </div>
            @empty
                <p class="text-xs text-card-foreground">Sin productos asignados</p>
            @endforelse
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalProductos', false)">
                Cancelar
            </x-button>
            <x-button wire:click="guardarProductos" class="ml-3">
                <i class="fa fa-save"></i> Guardar
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>