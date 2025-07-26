<div>
    <x-modal wire:model.live="mostrarAgregarCuadrillero">
        <div x-data="gestion_cuadrilla_agregar_cuadrillero">
            <div class="px-6 py-4">
                <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Agregar Cuadrillero
                </div>

                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <div @keydown.arrow-up.prevent="navigateList($event)"
                        @keydown.arrow-down.prevent="navigateList($event)" @keydown.enter.prevent="navigateList($event)"
                        @input="selectedIndex = 0">
                        <div>
                            <x-label>Escriba el nombre del Cuadrillero</x-label>
                            <div class="flex items-center gap-2">
                                <div class="relative w-full mt-2">
                                    <div
                                        class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-primary">
                                        <i class="fa fa-search"></i>
                                    </div>
                                    <x-input type="search" wire:model.live="search" class="w-full !pl-10"
                                        autocomplete="off" placeholder="Busca por Nombres, Apellidos o Documento"
                                        required />

                                </div>
                                <x-button type="button"
                                    @click="$wire.dispatch('registrarCuadrillero',{seleccionable:true})"
                                    title="Registrar Cuadrillero">
                                    <i class="fa fa-plus"></i>
                                </x-button>
                                <x-button type="button" class="whitespace-nowrap"
                                    @click="$wire.dispatch('registrarCuadrilleroDePlanilla',{seleccionable:true})"
                                    title="Registrar Cuadrillero de Planilla">
                                    P <i class="fa fa-plus"></i>
                                </x-button>
                            </div>
                            <ul class="mt-2">
                                <template x-for="(cuadrillero, index) in cuadrillerosFiltrados" :key="cuadrillero.id">
                                    <li :class="{ 'bg-primary text-primaryText': selectedIndex === index }"
                                        class="px-4 py-2 cursor-pointer hover:bg-primary hover:text-primaryText"
                                        @mouseenter="setSelectedIndex(index)" @click="agregarCuadrillero(cuadrillero)">
                                        <span x-text="cuadrillero.nombres"></span> - DNI: <span
                                            x-text="cuadrillero.dni"></span>
                                    </li>
                                </template>
                            </ul>

                        </div>
                    </div>
                    <div class="mt-5">
                        <template x-for="(c, index) in cuadrillerosAgregados" :key="index">
                            <div
                                class="inline-block mr-2 px-4 py-2 border rounded-md transition bg-white text-gray-700 border-gray-300">
                                <span x-text="c.nombres"></span>
                                <button @click="eliminarCuadrillero(index)" type="button"
                                    class="ml-2 inline-flex items-center px-2 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </template>
                    </div>
                        <div class=" mt-2" x-show="cuadrillerosAgregados.length > 0">
                            <x-label for="codigo_grupo" value="¿A que grupo se asignarán estas personas?" />
                            <div class="flex items-center gap-2">
                                <x-select wire:model.live="codigo_grupo" class="mt-1 w-full"
                                    wire:key="codigo_grupo-{{ $codigo_grupo }}">
                                    <option value="">SELECCIONAR GRUPO</option>
                                    @foreach ($grupos as $grupo)
                                        <option value="{{ $grupo->codigo }}">{{ $grupo->codigo }} - {{ $grupo->nombre }}
                                        </option>
                                    @endforeach
                                </x-select>

                                <x-button type="button" @click="$wire.dispatch('registrarGrupo')" title="Registrar Grupo">
                                    <i class="fa fa-plus"></i>
                                </x-button>
                            </div>
                            <x-input-error for="codigo_grupo" class="mt-2" />
                        </div>
                </div>
            </div>

            <div class="flex flex-row justify-end px-6 py-4 bg-whiten dark:bg-boxdarkbase text-end gap-4">
                <x-secondary-button wire:click="$set('mostrarAgregarCuadrillero', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                @if ($cuadrillerosAgregados && $codigo_grupo)
                    <x-button wire:click="agregarListaAgregada" wire:loading.attr="disabled">
                        <i class="fa fa-plus"></i> Agregar Cuadrilleros
                    </x-button>
                @endif
            </div>
        </div>
    </x-modal>
</div>
@script
<script>
    Alpine.data('gestion_cuadrilla_agregar_cuadrillero', () => ({
        selectedIndex: 0,
        cuadrillerosFiltrados: [],
        cuadrilleros: @json($listaCuadrilleros),
        search: @entangle('search'),
        cuadrillerosAgregados: @entangle('cuadrillerosAgregados'),
        init() {
            this.$watch('search', (value) => {
                this.selectedIndex = 0;
                if (value.trim() == '') {
                    this.cuadrillerosFiltrados = [];
                    return;
                }
                this.cuadrillerosFiltrados = this.cuadrilleros.filter(c =>
                    (c.nombres?.toLowerCase() || '').includes(value.toLowerCase()) ||
                    (c.dni?.toLowerCase() || '').includes(value.toLowerCase())
                );
            });
        },
        agregarCuadrillero(cuadrillero) {
            this.cuadrillerosAgregados.push({
                nombres: cuadrillero.nombres,
                id: cuadrillero.id
            });
            this.search = '';
            this.cuadrillerosFiltrados = [];
        },
        navigateList(event) {
            if (this.cuadrillerosFiltrados.length === 0) return;

            if (event.key === 'ArrowDown') {
                this.selectedIndex = (this.selectedIndex + 1) % this.cuadrillerosFiltrados.length;
            } else if (event.key === 'ArrowUp') {
                this.selectedIndex = (this.selectedIndex - 1 + this.cuadrillerosFiltrados.length) % this.cuadrillerosFiltrados.length;
            } else if (event.key === 'Enter') {
                this.agregarCuadrillero(this.cuadrillerosFiltrados[this.selectedIndex]);
            }
        },
        eliminarCuadrillero(index) {
            this.cuadrillerosAgregados.splice(index, 1);
        },
        setSelectedIndex(index) {
            this.selectedIndex = index;
        }
    }));

</script>
@endscript