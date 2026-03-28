<div>
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Registro de Productos
                </x-h3>
            </div>
        </x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="guardarProducto" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                    <x-input label="Nombre del Producto" wire:keydown.enter="guardarProducto" wire:model="nombre_comercial"
                        class="uppercase" id="nombre_comercial" error="nombre_comercial" />

                    <x-input label="Ingrediente Activo" wire:keydown.enter="guardarProducto" wire:model="ingrediente_activo"
                        class="uppercase" id="ingrediente_activo" error="ingrediente_activo" />

                    <x-select class="uppercase" label="Categoría" wire:model.live="categoria_codigo"
                        error="categoria_codigo" fullWidth="true">
                        <option value="">SELECCIONAR CATEGORÍA</option>
                        <option value="fertilizante">FERTILIZANTE</option>
                        <option value="pesticida">PESTICIDA</option>
                        <option value="corrector_salinidad">CORRECTOR DE SALINIDAD</option>
                        <option value="combustible">COMBUSTIBLE</option>
                    </x-select>

                    <x-select class="uppercase" label="Tipo Existencias (Tabla 5)" wire:model="codigo_tipo_existencia"
                        error="codigo_tipo_existencia" fullWidth="true">
                        <option value="">SELECCIONAR TIPO</option>
                        @if ($sunatTipoExistencias)
                            @foreach ($sunatTipoExistencias as $sunatTipoExistencia)
                                <option value="{{ $sunatTipoExistencia->codigo }}">
                                    {{ $sunatTipoExistencia->codigo }} - {{ $sunatTipoExistencia->descripcion }}
                                </option>
                            @endforeach
                        @endif
                    </x-select>

                    <x-select class="uppercase" label="Unidad de Medida (Tabla 6)" wire:model="codigo_unidad_medida"
                        error="codigo_unidad_medida" fullWidth="true">
                        <option value="">SELECCIONAR UNIDAD</option>
                        @if ($sunatCodigoUnidadMedidas)
                            @foreach ($sunatCodigoUnidadMedidas as $sunatCodigoUnidadMedida)
                                <option value="{{ $sunatCodigoUnidadMedida->codigo }}">
                                    {{ $sunatCodigoUnidadMedida->alias }} -
                                    {{ $sunatCodigoUnidadMedida->descripcion }}
                                </option>
                            @endforeach
                        @endif
                    </x-select>
                    @if ($categoria_codigo == 'pesticida')

                        <x-select class="uppercase" label="Tipo de pesticida" wire:model="categoria_pesticida"
                            error="categoria_pesticida" fullWidth="true">
                            <option value="">SELECCIONAR CATEGORÍA DE PESTICIDA</option>
                            @foreach ($listaCategoriasPesticida as $listaCategoriaPesticida)
                                <option value="{{ $listaCategoriaPesticida->codigo }}">
                                    {{ $listaCategoriaPesticida->descripcion }}
                                </option>
                            @endforeach
                        </x-select>
                    @endif
                </div>
                {{-- Selector de usos --}}
                <div class="mt-4" x-data="selectorUsos" wire:ignore>

                    {{-- Área tipo textarea con badges --}}
                    <label class="block text-sm font-bold text-foreground mb-3">Usos / Aplicaciones</label>
                    <div class="w-full rounded-md flex flex-wrap gap-2 cursor-text"
                        @click="$refs.busqueda.focus()">

                        {{-- Badges de usos seleccionados --}}
                        <template x-for="uso in seleccionados" :key="uso.id">
                            <span
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium
                         bg-muted text-muted-foreground border border-border
                         transition-all duration-150">
                                <span x-text="uso.nombre"></span>
                                <button type="button" @click.stop="quitar(uso.id)"
                                    class="rounded-full p-0.5 hover:bg-muted transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </span>
                        </template>

                        {{-- Input de búsqueda --}}
                        <x-input x-ref="busqueda" x-model="query" @input="buscar" @keydown.escape="cerrar"
                            @keydown.backspace="borrarUltimo" type="text" placeholder="Buscar uso..."
                            class="flex-1 min-w-[120px]" />
                    </div>

                    {{-- Dropdown de sugerencias --}}
                    <div x-show="abierto && sugerencias.length > 0"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0" @click.outside="cerrar"
                        class="relative z-50">
                        <ul
                            class="absolute mt-1 w-full max-h-48 overflow-y-auto rounded-md border border-border
                   bg-card shadow-lg text-sm divide-y divide-border">
                            <template x-for="opcion in sugerencias" :key="opcion.id">
                                <li @click="agregar(opcion)"
                                    class="px-3 py-2 cursor-pointer hover:bg-muted flex items-center justify-between group">
                                    <span x-text="opcion.nombre" class="text-foreground"></span>
                                    <span
                                        class="text-xs text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity">
                                        + agregar
                                    </span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
                @if ($categoria_codigo == 'fertilizante')
                    <div>
                        <label class="block text-sm font-bold text-foreground mb-3">Porcentajes</label>
                        <div class="mt-2 grid grid-cols-1 md:grid-cols-4 gap-4">

                            <x-input type="number" label="% Nitrógeno" wire:keydown.enter="guardarProducto"
                                wire:model="porcentaje_nitrogeno" wire:key="porcentaje_nitrogeno"
                                error="porcentaje_nitrogeno" />

                            <x-input type="number" label="% Fósforo" wire:keydown.enter="guardarProducto"
                                wire:model="porcentaje_fosforo" wire:key="porcentaje_fosforo"
                                error="porcentaje_fosforo" />

                            <x-input type="number" label="% Potasio" wire:keydown.enter="guardarProducto"
                                wire:model="porcentaje_potasio" wire:key="porcentaje_potasio"
                                error="porcentaje_potasio" />

                            <x-input type="number" label="% Calcio" wire:keydown.enter="guardarProducto"
                                wire:model="porcentaje_calcio" wire:key="porcentaje_calcio"
                                error="porcentaje_calcio" />

                            <x-input type="number" label="% Magnesio" wire:keydown.enter="guardarProducto"
                                wire:model="porcentaje_magnesio" wire:key="porcentaje_magnesio"
                                error="porcentaje_magnesio" />

                            <x-input type="number" label="% Zinc" wire:keydown.enter="guardarProducto"
                                wire:model="porcentaje_zinc" wire:key="porcentaje_zinc" error="porcentaje_zinc" />

                            <x-input type="number" label="% Manganeso" wire:keydown.enter="guardarProducto"
                                wire:model="porcentaje_manganeso" wire:key="porcentaje_manganeso"
                                error="porcentaje_manganeso" />

                            <x-input type="number" label="% Hierro" wire:keydown.enter="guardarProducto"
                                wire:model="porcentaje_hierro" wire:key="porcentaje_hierro"
                                error="porcentaje_hierro" />
                        </div>
                    </div>
                @endif

            </form>
        </x-slot>
        <x-slot name="footer">
            <x-flex class="justify-end">
                <x-button variant="secondary" type="button" wire:click="closeForm" class="mr-2">Cerrar</x-button>
                @if ($productoId)
                    <x-button variant="secondary" class="mr-2"
                        @click="$wire.dispatch('VerComprasProducto',{'id':{{ $productoId }}})">
                        <i class="fa fa-money-bill"></i> Compras
                    </x-button>

                    <livewire:compra-producto-import-export-component :productoid="$productoId"
                        wire:key="registroCompra{{ $productoId }}" />
                @endif

                <x-button type="submit" wire:click="guardarProducto" class="ml-3">
                    <i class="fa fa-save"></i> Guardar
                </x-button>
            </x-flex>

        </x-slot>
    </x-dialog-modal>
</div>
@script
    <script>
        Alpine.data('selectorUsos', () => ({
            todos: @js($listaUsos),
            seleccionados: [],
            usosEntangle: @entangle('usos'),
            query: '',
            sugerencias: [],
            abierto: false,

            init() {
                // Precargar seleccionados desde Livewire
                this.$watch('usosEntangle', ids => {
                    this.seleccionados = this.todos.filter(u => ids.includes(u.id));
                });

                // Carga inicial
                this.seleccionados = this.todos.filter(u => this.usosEntangle.includes(u.id));
            },

            buscar() {
                const q = this.query.toLowerCase().trim();
                if (!q) {
                    this.sugerencias = [];
                    this.abierto = false;
                    return;
                }

                const idsSeleccionados = this.seleccionados.map(u => u.id);
                this.sugerencias = this.todos.filter(u =>
                    u.nombre.toLowerCase().includes(q) &&
                    !idsSeleccionados.includes(u.id)
                );
                this.abierto = true;
            },

            agregar(opcion) {
                this.seleccionados.push(opcion);
                this.usosEntangle = this.seleccionados.map(u => u.id);
                this.query = '';
                this.sugerencias = [];
                this.abierto = false;
                this.$refs.busqueda.focus();
            },

            quitar(id) {
                this.seleccionados = this.seleccionados.filter(u => u.id !== id);
                this.usosEntangle = this.seleccionados.map(u => u.id);
            },

            borrarUltimo() {
                if (this.query === '' && this.seleccionados.length > 0) {
                    this.seleccionados.pop();
                    this.usosEntangle = this.seleccionados.map(u => u.id);
                }
            },

            cerrar() {
                this.abierto = false;
                this.query = '';
                this.sugerencias = [];
            },
        }));
    </script>
@endscript
