<div class="space-y-4">

    <x-flex>
        <x-h3>
            Gestión de Productos
        </x-h3>
        <x-button type="button" @click="$wire.dispatch('CrearProducto')" class="w-full md:w-auto ">
            <i class="fa fa-plus"></i> Nuevo Producto
        </x-button>
    </x-flex>
    <x-card>
        {{-- Fila 1: búsqueda + categoría + limpiar --}}
        <div class="flex flex-wrap items-end gap-4">

            {{-- Buscar --}}
            <div>
                <x-label>Buscar por nombre</x-label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-primary">
                        <i class="fa fa-search"></i>
                    </div>
                    <x-input type="search" wire:model.live="search" class="!pl-10 !w-auto" autocomplete="off" />
                </div>
            </div>

            {{-- Categoría --}}
            <div>
                <x-select label="Categoría" wire:model.live="categoriaSeleccionada" class="uppercase">
                    <option value="">TODAS LAS CATEGORÍAS</option>
                    @foreach ($categorias as $cat)
                        <option value="{{ $cat->codigo }}">{{ strtoupper($cat->descripcion) }}</option>
                    @endforeach
                </x-select>
            </div>

            {{-- Tipo pesticida — solo si categoria = pesticida --}}
            @if($listaSubCategorias)
                <div>
                    <x-select label="Subcategoria" wire:model.live="subcategoria_id" class="uppercase">
                        <option value="">TODOS LOS TIPOS</option>
                        @foreach ($listaSubCategorias as $subcat)
                            <option value="{{ $subcat->id }}">{{ strtoupper($subcat->nombre) }}</option>
                        @endforeach
                    </x-select>
                </div>
            @endif

            {{-- Usos --}}
            <div>
                <x-select label="Uso / Aplicación" wire:model.live="usoSeleccionado">
                    <option value="">TODOS LOS USOS</option>
                    @foreach ($listaUsosFiltro as $uso)
                        <option value="{{ $uso['id'] }}">{{ $uso['nombre'] }}</option>
                    @endforeach
                </x-select>
            </div>

            {{-- Limpiar --}}
            <div class="pb-0.5">
                <x-button variant="secondary" wire:click="limpiarFiltros">
                    <i class="fa fa-times mr-1"></i> Limpiar
                </x-button>
            </div>
        </div>

        {{-- Fila 2: nutrientes (solo si categoría = fertilizante) --}}
        @if($categoriaSeleccionada === 'fertilizante')
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm text-muted-foreground font-medium">Filtrar por nutriente:</span>
                @foreach ($listaNutrientesFiltro as $nutriente)
                    <label class="inline-flex items-center gap-1.5 cursor-pointer select-none">
                        <x-input type="checkbox" wire:model.live="nutrientesSeleccionados" value="{{ $nutriente['codigo'] }}"
                            class="rounded border-border text-primary focus:ring-primary" />
                        <span class="text-sm">{{ $nutriente['nombre'] }}</span>
                    </label>
                @endforeach
            </div>
        @endif

        {{-- Indicadores de filtros activos --}}
        @if($search || $subcategoria_id || $usoSeleccionado || !empty($nutrientesSeleccionados))
            <div class="flex flex-wrap gap-2 text-xs">
                @if($search)
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20">
                        <i class="fa fa-search"></i> "{{ $search }}"
                        <button wire:click="$set('search','')" class="hover:text-primary/70">×</button>
                    </span>
                @endif
                @if($usoSeleccionado)
                    @php $usoLabel = collect($listaUsosFiltro)->firstWhere('id', (int) $usoSeleccionado)['nombre'] ?? $usoSeleccionado @endphp
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20">
                        <i class="fa fa-tag"></i> {{ $usoLabel }}
                        <button wire:click="$set('usoSeleccionado','')" class="hover:text-primary/70">×</button>
                    </span>
                @endif
                @foreach($nutrientesSeleccionados as $nc)
                    @php $nLabel = collect($listaNutrientesFiltro)->firstWhere('codigo', $nc)['nombre'] ?? $nc @endphp
                    <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20">
                        <i class="fa fa-flask"></i> {{ $nLabel }}
                        <button
                            wire:click="$js(`$wire.nutrientesSeleccionados = $wire.nutrientesSeleccionados.filter(n => n !== '{{ $nc }}')`)"
                            class="hover:text-primary/70">×</button>
                    </span>
                @endforeach
            </div>
        @endif
    </x-card>
    <x-card>
        <x-table class="mt-5">
            <x-slot name="thead">
                <tr>
                    <x-th class="text-center">
                        N°
                    </x-th>
                    <x-th>
                        <button wire:click="sortBy('nombre_comercial')" class="focus:outline-none">
                            NOMBRE COMERCIAL <i class="fa fa-sort"></i>
                        </button>
                    </x-th>
                    <x-th class="text-center">
                        <button wire:click="sortBy('categoria_codigo')" class="focus:outline-none">
                            CATEGORÍA <i class="fa fa-sort"></i>
                        </button>
                    </x-th>
                    <x-th value="TIPO DE EXISTENCIA (TABLA 5)" class="text-center" />
                    <x-th value="NUTRIENTES" class="text-center" />
                    <x-th value="USOS" class="text-center" />
                    <x-th value="ACCIONES" class="text-center" />
                </tr>
            </x-slot>
            <x-slot name="tbody">
                @if ($productos && $productos->count() > 0)
                    @foreach ($productos as $indice => $producto)
                        <x-tr>
                            <x-th value="{{ $indice + 1 }}" class="text-center" />
                            {{-- Nombre + ingrediente activo como sub --}}
                            <x-td class="text-left">
                                {!! $producto->nombre_completo_kg !!}
                            </x-td>

                            {{-- Categoría --}}
                            <x-td class="text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span>{{ $producto->categoria_con_descripcion }}</span>
                                    {{-- Indicador kardex año actual --}}
                                    @if ($producto->kardexActual)
                                        <span class="inline-flex items-center gap-1 text-xs text-green-600 font-medium">
                                            <i class="fa fa-check-circle"></i>
                                            Kardex {{ now()->year }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs text-muted-foreground">
                                            <i class="fa fa-circle-xmark"></i>
                                            Sin kardex
                                        </span>
                                    @endif
                                </div>
                            </x-td>

                            {{-- Tipo existencia --}}
                            <x-td value="{{ $producto->tipo_existencia }}" class="text-center" />

                            {{-- Nutrientes --}}
                            <x-td class="text-left">
                                {!! $producto->lista_nutrientes !!}
                            </x-td>

                            {{-- Usos --}}
                            <x-td class="text-left">
                                @if ($producto->usos->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($producto->usos->unique('id') as $uso)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs
                                                                             font-medium bg-primary/10 text-primary border border-primary/20">
                                                {{ $uso->nombre }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-muted-foreground">—</span>
                                @endif
                            </x-td>

                            <x-td class="text-center">
                                <x-dropdown align="right" width="48">
                                    <x-slot name="trigger">
                                        <button type="button"
                                            class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-100 transition">
                                            Opciones
                                            <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </x-slot>

                                    <x-slot name="content">
                                        {{-- Ver auditoría --}}
                                        <x-dropdown-link href="#"
                                            @click.prevent="$wire.verAuditoriaProducto({{ $producto->id }})">
                                            <i class="fa fa-history"></i>
                                            <span class="ml-2">Historial</span>
                                        </x-dropdown-link>
                                        @if ($producto->kardexActual)
                                            <x-dropdown-link
                                                href="{{ route('gestion_insumos.kardex.detalle', ['insumoKardexId' => $producto->kardexActual->id]) }}">
                                                <i class="fa fa-eye"></i>
                                                <span class="ml-2">Ver Kardex</span>
                                            </x-dropdown-link>
                                        @endif
                                        {{-- Ver compras --}}
                                        <x-dropdown-link href="#"
                                            href="{{ route('almacen.compras', ['producto_id' => $producto->id]) }}">
                                            <i class="fa fa-money-bill"></i>
                                            <span class="ml-2">Compras</span>
                                        </x-dropdown-link>

                                        {{-- Editar --}}
                                        <x-dropdown-link href="#"
                                            @click.prevent="$wire.dispatch('EditarProducto',{ id: {{ $producto->id }} })">
                                            <i class="fa fa-edit"></i>
                                            <span class="ml-2">Editar</span>
                                        </x-dropdown-link>

                                        {{-- Eliminar --}}
                                        <x-dropdown-link href="#"
                                            @click.prevent=" $wire.confirmarEliminacion({{ $producto->id }}) ">
                                            <i class="fa fa-trash text-red-600"></i>
                                            <span class="ml-2">Eliminar</span>
                                        </x-dropdown-link>

                                    </x-slot>
                                </x-dropdown>
                            </x-td>

                        </x-tr>
                    @endforeach
                @else
                    <x-tr>
                        <x-td colspan="100%">No Hay Productos Registrados.</x-td>
                    </x-tr>
                @endif
            </x-slot>
        </x-table>
        <div class="mt-5">
            {{ $productos->links() }}
        </div>
    </x-card>
    <x-dialog-modal wire:model.live="modalAuditoria">
        <x-slot name="title">Historial de auditoría — Producto</x-slot>

        <x-slot name="content">
            @php
                $entradaCreacion = collect($auditoriaHistorial)->firstWhere('accion', 'crear');
                $ultimaEdicion = collect($auditoriaHistorial)->where('accion', 'editar')->sortByDesc('fecha_accion')->first();
            @endphp

            <div class="flex gap-6 mb-4 text-xs text-muted-foreground border-b border-border pb-3">
                <div>
                    <span class="font-semibold text-card-foreground">Creado por:</span>
                    {{ $entradaCreacion['usuario_nombre'] ?? '—' }}
                    @if($entradaCreacion)
                        <span class="ml-1 text-gray-400">
                            {{ \Carbon\Carbon::parse($entradaCreacion['fecha_accion'])->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
                <div>
                    <span class="font-semibold text-card-foreground">Última edición:</span>
                    {{ $ultimaEdicion['usuario_nombre'] ?? '—' }}
                    @if($ultimaEdicion)
                        <span class="ml-1 text-gray-400">
                            {{ \Carbon\Carbon::parse($ultimaEdicion['fecha_accion'])->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
            </div>

            @forelse($auditoriaHistorial as $entrada)
                    <div class="mb-4 border-b border-border pb-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-semibold uppercase
                                        {{ $entrada['accion'] === 'crear' ? 'text-green-600' :
                ($entrada['accion'] === 'eliminar' ? 'text-red-600' : 'text-yellow-600') }}">
                                {{ $entrada['accion'] }}
                            </span>
                            <span class="text-gray-400 text-xs">
                                {{ \Carbon\Carbon::parse($entrada['fecha_accion'])->format('d/m/Y H:i') }}
                                — {{ $entrada['usuario_nombre'] ?? 'Sistema' }}
                            </span>
                        </div>

                        @if(!empty($entrada['cambios']))
                            @if($entrada['accion'] === 'editar')
                                <table class="mt-2 w-full text-xs text-gray-700">
                                    <thead>
                                        <tr class="text-left text-gray-400">
                                            <th class="pr-4">Campo</th>
                                            <th class="pr-4">Antes</th>
                                            <th>Después</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($entrada['cambios']['antes'] ?? [] as $campo => $valorAntes)
                                            <tr>
                                                <td class="pr-4 font-medium text-muted-foreground">{{ $campo }}</td>
                                                <td class="pr-4 text-red-500">{{ $valorAntes ?? '—' }}</td>
                                                <td class="text-green-600">{{ $entrada['cambios']['despues'][$campo] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <pre
                                    class="mt-2 text-xs bg-muted rounded p-2 overflow-auto max-h-40">{{ json_encode(array_values($entrada['cambios'])[0] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @endif
                        @endif

                        @if(!empty($entrada['observacion']))
                            <p class="mt-1 text-xs italic text-card-foreground">{{ $entrada['observacion'] }}</p>
                        @endif
                    </div>
            @empty
                <p class="text-sm text-card-foreground">Sin historial de cambios.</p>
            @endforelse
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalAuditoria', false)">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>