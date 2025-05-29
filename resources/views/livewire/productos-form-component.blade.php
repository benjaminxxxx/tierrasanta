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

                    <x-group-field>
                        <x-input-string label="Nombre del Producto" wire:keydown.enter="store"
                            wire:model="nombre_comercial" class="uppercase" id="nombre_comercial"
                            error="nombre_comercial" />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Ingrediente Activo" wire:keydown.enter="store"
                            wire:model="ingrediente_activo" class="uppercase" id="ingrediente_activo"
                            error="ingrediente_activo" />
                    </x-group-field>
                    <x-group-field>
                        <x-select class="uppercase" label="Categoría" wire:model.live="categoria" error="categoria">
                            <option value="">SELECCIONAR CATEGORÍA</option>
                            <option value="fertilizante">FERTILIZANTE</option>
                            <option value="pesticida">PESTICIDA</option>
                            <option value="combustible">COMBUSTIBLE</option>
                        </x-select>
                    </x-group-field>
                    <x-group-field>
                        <x-select class="uppercase" label="Tipo Existencias (Tabla 5)"
                            wire:model="codigo_tipo_existencia" error="codigo_tipo_existencia">
                            <option value="">SELECCIONAR TIPO</option>
                            @if ($sunatTipoExistencias)
                                @foreach ($sunatTipoExistencias as $sunatTipoExistencia)
                                    <option value="{{ $sunatTipoExistencia->codigo }}">
                                        {{ $sunatTipoExistencia->codigo }} - {{ $sunatTipoExistencia->descripcion }}
                                    </option>
                                @endforeach
                            @endif
                        </x-select>
                    </x-group-field>
                    <x-group-field>
                        <x-select class="uppercase" label="Unidad de Medida (Tabla 6)" wire:model="codigo_unidad_medida"
                            error="codigo_unidad_medida">
                            <option value="">SELECCIONAR UNIDAD</option>
                            @if ($sunatCodigoUnidadMedidas)
                                @foreach ($sunatCodigoUnidadMedidas as $sunatCodigoUnidadMedida)
                                    <option value="{{ $sunatCodigoUnidadMedida->codigo }}">
                                        {{ $sunatCodigoUnidadMedida->alias }} -
                                        {{ $sunatCodigoUnidadMedida->descripcion }}</option>
                                @endforeach
                            @endif
                        </x-select>
                    </x-group-field>
                    @if ($categoria == 'pesticida')
                        <x-group-field>
                            <x-select class="uppercase" label="Tipo de pesticida" wire:model="categoria_pesticida"
                                error="categoria_pesticida">
                                <option value="">SELECCIONAR CATEGORÍA DE PESTICIDA</option>
                                @foreach ($listaCategoriasPesticida as $listaCategoriaPesticida)
                                    <option value="{{ $listaCategoriaPesticida->codigo }}">
                                        {{ $listaCategoriaPesticida->descripcion }}</option>
                                @endforeach
                            </x-select>
                        </x-group-field>
                    @endif
                </div>
                @if ($categoria == 'fertilizante')
                    <div class="mt-2 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <x-group-field>
                            <x-input-number label="% Nitrógeno" wire:keydown.enter="store"
                                wire:model="porcentaje_nitrogeno" wire:key="porcentaje_nitrogeno"
                                error="porcentaje_nitrogeno" />
                        </x-group-field>
                        <x-group-field>
                            <x-input-number label="% Fósforo" wire:keydown.enter="store" wire:model="porcentaje_fosforo"
                                wire:key="porcentaje_fosforo" error="porcentaje_fosforo" />
                        </x-group-field>
                        <x-group-field>
                            <x-input-number label="% Potasio" wire:keydown.enter="store" wire:model="porcentaje_potasio"
                                wire:key="porcentaje_potasio" error="porcentaje_potasio" />
                        </x-group-field>
                        <x-group-field>
                            <x-input-number label="% Calcio" wire:keydown.enter="store" wire:model="porcentaje_calcio"
                                wire:key="porcentaje_calcio" error="porcentaje_calcio" />
                        </x-group-field>
                        <x-group-field>
                            <x-input-number label="% Magnesio" wire:keydown.enter="store"
                                wire:model="porcentaje_magnesio" wire:key="porcentaje_magnesio"
                                error="porcentaje_magnesio" />
                        </x-group-field>
                        <x-group-field>
                            <x-input-number label="% Zinc" wire:keydown.enter="store" wire:model="porcentaje_zinc"
                                wire:key="porcentaje_zinc" error="porcentaje_zinc" />
                        </x-group-field>
                        <x-group-field>
                            <x-input-number label="% Manganeso" wire:keydown.enter="store"
                                wire:model="porcentaje_manganeso" wire:key="porcentaje_manganeso"
                                error="porcentaje_manganeso" />
                        </x-group-field>
                        <x-group-field>
                            <x-input-number label="% Hierro" wire:keydown.enter="store" wire:model="porcentaje_hierro"
                                wire:key="porcentaje_hierro" error="porcentaje_hierro" />
                        </x-group-field>
                    </div>
                @endif

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
