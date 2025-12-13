<div>

    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Gestión de Productos
        </x-h3>
        <x-button type="button" @click="$wire.dispatch('CrearProducto')" class="w-full md:w-auto ">
            <i class="fa fa-plus"></i> Nuevo Producto
        </x-button>
    </div>
    <x-card2 class="mt-4">
        <form class="flex items-center gap-5">
            <div class="">
                <x-label>Busca por Nombre</x-label>
                <div class="relative w-full">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-primary">
                        <i class="fa fa-search"></i>
                    </div>
                    <x-input type="search" wire:model.live="search" id="default-search" class="!w-auto !pl-10"
                        autocomplete="off" required />
                </div>
            </div>
            <div class="">
                <x-select class="uppercase" label="Categoría" wire:model.live="categoriaSeleccionada">
                    <option value="">SELECCIONAR CATEGORÍA</option>

                    @foreach ($categorias as $cat)
                        <option value="{{ $cat->codigo }}">{{ strtoupper($cat->descripcion) }}</option>
                    @endforeach
                </x-select>
            </div>
        </form>
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
                        <button wire:click="sortBy('categoria')" class="focus:outline-none">
                            CATEGORÍA <i class="fa fa-sort"></i>
                        </button>
                    </x-th>
                    <x-th value="TIPO DE EXISTENCIA (TABLA 5)" class="text-center" />
                    <x-th value="UNIDAD DE MEDIDA (TABLA 6)" class="text-center" />
                    <x-th value="NUTRIENTES" class="text-center" />
                    <x-th value="ACCIONES" class="text-center" />
                </tr>
            </x-slot>
            <x-slot name="tbody">
                @if ($productos && $productos->count() > 0)
                    @foreach ($productos as $indice => $producto)
                        <x-tr>
                            <x-th value="{{ $indice + 1 }}" class="text-center" />
                            <x-td value="{{ $producto->nombre_completo }}" />
                            <x-td value="{{ $producto->categoria_con_descripcion }}" class="text-center" />
                            <x-td value="{{ $producto->tipo_existencia }}" class="text-center" />
                            <x-td value="{{ $producto->unidad_medida }}" class="text-center" />
                            <x-td class="text-left">
                                {!! $producto->lista_nutrientes !!}
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
                                        @if ($producto->kardexActual)
                                            <x-dropdown-link href="{{ route('gestion_insumos.kardex.detalle', ['insumoKardexId' => $producto->kardexActual->id]) }}">
                                                <i class="fa fa-eye"></i>
                                                <span class="ml-2">Ver Kardex</span>
                                            </x-dropdown-link>
                                        @endif
                                        {{-- Ver compras --}}
                                        <x-dropdown-link href="#"
                                            @click.prevent="$wire.dispatch('VerComprasProducto',{ id: {{ $producto->id }} })">
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
                        <x-td colspan="4">No Hay Productos Registrados.</x-td>
                    </x-tr>
                @endif
            </x-slot>
        </x-table>
        <div class="mt-5">
            {{ $productos->links() }}
        </div>
    </x-card2>
    <x-loading wire:loading />
</div>