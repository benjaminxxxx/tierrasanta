<div>
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Registro de Productos
                </x-h3>
                <div class="flex-shrink-0">
                    <button wire:click="closeForm" class="focus:outline-none">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="store">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">


                    <div class="mt-3">
                        <x-label for="nombre_comercial">Nombre del Producto</x-label>
                        <x-input type="text" wire:keydown.enter="store" wire:model="nombre_comercial"
                            class="uppercase" id="nombre_comercial" />
                        <x-input-error for="nombre_comercial" />
                    </div>

                    <div class="mt-3">
                        <x-label for="ingrediente_activo">Ingrediente Activo</x-label>
                        <x-input type="text" wire:keydown.enter="store" class="uppercase"
                            wire:model="ingrediente_activo" id="ingrediente_activo" />
                        <x-input-error for="ingrediente_activo" />
                    </div>

                    <div class="mt-3">
                        <x-label for="categoria_id">Categoría</x-label>
                        <x-select class="uppercase" wire:model="categoria_id" id="categoria_id">
                            <option value="">SELECCIONAR CATEGORÍA</option>
                            @if ($categorias)
                                @foreach ($categorias as $categoria)
                                    <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                @endforeach
                            @endif
                        </x-select>
                        <x-input-error for="categoria_id" />
                    </div>
                    <div class="mt-3">
                        <x-label for="codigo_tipo_existencia">Tipo Existencias (Tabla 5)</x-label>
                        <x-select class="uppercase" wire:model="codigo_tipo_existencia">
                            <option value="">SELECCIONAR TIPO</option>
                            @if ($sunatTipoExistencias)
                                @foreach ($sunatTipoExistencias as $sunatTipoExistencia)
                                    <option value="{{ $sunatTipoExistencia->codigo }}">
                                        {{ $sunatTipoExistencia->codigo }} - {{ $sunatTipoExistencia->descripcion }}
                                    </option>
                                @endforeach
                            @endif
                        </x-select>
                        <x-input-error for="codigo_tipo_existencia" />
                    </div>
                    <div class="mt-3">
                        <x-label for="codigo_unidad_medida">Unidad de Medida (Tabla 6)</x-label>
                        <x-select class="uppercase" wire:model="codigo_unidad_medida">
                            <option value="">SELECCIONAR UNIDAD</option>
                            @if ($sunatCodigoUnidadMedidas)
                                @foreach ($sunatCodigoUnidadMedidas as $sunatCodigoUnidadMedida)
                                    <option value="{{ $sunatCodigoUnidadMedida->codigo }}">
                                        {{ $sunatCodigoUnidadMedida->alias }} -
                                        {{ $sunatCodigoUnidadMedida->descripcion }}</option>
                                @endforeach
                            @endif
                        </x-select>
                        <x-input-error for="codigo_unidad_medida" />
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="closeForm" class="mr-2">Cerrar</x-secondary-button>
            @if ($productoId)
                <x-secondary-button class="mr-2"
                    @click="$wire.dispatch('VerComprasProducto',{'id':{{ $productoId }}})">
                    <i class="fa fa-money-bill"></i> Compras
                </x-secondary-button>

                <livewire:compra-producto-import-export-component :productoid="$productoId"
                    wire:key="registroCompra{{ $productoId }}" />
            @endif

            <x-button type="submit" wire:click="store" class="ml-3">
                <i class="fa fa-save"></i> Guardar
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
