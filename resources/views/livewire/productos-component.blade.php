<div>

    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Productos
        </x-h3>
        <x-button type="button" @click="$wire.dispatch('CrearProducto')" class="w-full md:w-auto ">
            <i class="fa fa-plus"></i> Nuevo Producto
        </x-button>
    </div>
    <x-card>
        <x-spacing>
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
                    <x-label>Categoría</x-label>
                    <x-select class="uppercase" wire:model.live="categoria_id_filtro">
                        <option value="">SELECCIONAR CATEGORÍA</option>
                        @if ($categorias)
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                            @endforeach
                        @endif
                    </x-select>
                </div>
            </form>
            <x-table class="mt-5">
                <x-slot name="thead">
                    <tr>
                        <x-th class="text-center">
                            N°
                        </x-th>
                        <x-th class="text-center">
                            CÓDIGO DE EXISTENCIA
                        </x-th>
                        <x-th>
                            <button wire:click="sortBy('nombre_comercial')" class="focus:outline-none">
                                NOMBRE COMERCIAL <i class="fa fa-sort"></i>
                            </button>
                        </x-th>
                        <x-th>
                            <button wire:click="sortBy('ingrediente_activo')" class="focus:outline-none">
                                INGREDIENTE ACTIVO <i class="fa fa-sort"></i>
                            </button>
                        </x-th>
                        <x-th value="UNIDAD DE MEDIDA" class="text-center" />
                        <x-th class="text-center">
                            <button wire:click="sortBy('categoria_id')" class="focus:outline-none">
                                CATEGORÍA <i class="fa fa-sort"></i>
                            </button>
                        </x-th>
                        <x-th value="TIPO DE EXISTENCIA (TABLA 5)" class="text-center" />
                        <x-th value="UNIDAD DE MEDIDA (TABLA 6)" class="text-center" />
                        <x-th value="ACCIONES" class="text-center" />
                    </tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($productos && $productos->count() > 0)
                        @foreach ($productos as $indice => $producto)
                            <x-tr>
                                <x-th value="{{ $indice + 1 }}" class="text-center" />
                                <x-td value="{{ $producto->codigo_existencia }}" class="text-center" />
                                <x-td value="{{ $producto->nombre_comercial }}" />
                                <x-td value="{{ $producto->ingrediente_activo }}" />
                                <x-td value="{{ $producto->unidad_medida }}" class="text-center" />
                                <x-td value="{{ $producto->categoria->nombre }}" class="text-center" />
                                <x-td value="({{ $producto->tabla5->codigo }}) {{ $producto->tabla5->descripcion }}"
                                    class="text-center" />
                                <x-td value="({{ $producto->tabla5->codigo }}) {{ $producto->tabla6->descripcion }}"
                                    class="text-center" />

                                <x-td class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <x-secondary-button
                                            @click="$wire.dispatch('VerComprasProducto',{'id':{{ $producto->id }}})">
                                            <i class="fa fa-money-bill"></i> Compras
                                        </x-secondary-button>
                                        <x-secondary-button
                                            @click="$wire.dispatch('EditarProducto',{'id':{{ $producto->id }}})">
                                            <i class="fa fa-edit"></i>
                                        </x-secondary-button>
                                        <x-danger-button wire:click="confirmarEliminacion({{ $producto->id }})">
                                            <i class="fa fa-trash"></i>
                                        </x-danger-button>
                                    </div>

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
        </x-spacing>
    </x-card>
</div>
