<div>
    <x-dialog-modal wire:model.live="mostrarAgregarCuadrillero">
        <x-slot name="title">
            Agregar Cuadrilleros
        </x-slot>

        <x-slot name="content">
            <div x-data="formAgregarCuadrillero">
                <div class=" mt-2">
                    <x-label for="codigo_grupo"
                        value="Primero seleccione el grupo donde desea agregar cuadrilleros para esta semana" />
                    <div class="flex items-center gap-2">
                        <x-select wire:model.live="codigo_grupo" class="mt-1 w-full"
                            wire:key="codigo_grupo-{{ $codigo_grupo ?? Str::random(4) }}">
                            <option value="">SELECCIONAR GRUPO</option>
                            @foreach ($grupos as $grupo)
                                <option value="{{ $grupo->codigo }}">{{ $grupo->codigo }} - {{ $grupo->nombre }}
                                </option>
                            @endforeach
                        </x-select>

                        <x-button type="button" wire:click="$dispatch('registrarGrupo')" title="Registrar Grupo">
                            <i class="fa fa-plus"></i>
                        </x-button>
                    </div>
                    <x-input-error for="codigo_grupo" class="mt-2" />
                </div>
                <div @keydown.arrow-up.prevent="navigateList($event)" @keydown.arrow-down.prevent="navigateList($event)"
                    @keydown.enter.prevent="navigateList($event)" @input="selectedIndex = 0">
                    <div x-show="codigo_grupo">
                        <x-label>Para agregar mas cuadrilleros digite su nombre aquí</x-label>
                        <div class="flex items-center gap-2">
                            <div class="relative mt-2">
                                <div
                                    class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-primary">
                                    <i class="fa fa-search"></i>
                                </div>
                                <x-input type="search" x-model="search" x-ref="buscador" class="!pl-10 uppercase"
                                    autocomplete="off" placeholder="Busca por Nombres, Apellidos o Documento"
                                    required />

                            </div>
                            <x-button type="button" x-show="search && cuadrillerosFiltrados.length === 0"
                                @click="registrarComoNuevo">
                                <i class="fa fa-save"></i> Registrar como nuevo
                            </x-button>
                        </div>
                        <ul class="mt-2 absolute border border-gray-600 shadow-2xl bg-white dark:bg-gray-700 z-[99]">
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
                        <x-resumen-item>
                            <x-slot name="label">
                                <span x-text="index+1" class="font-bold text-red-600"></span> - <span
                                    x-text="c.nombres"></span>
                            </x-slot>
                            <x-slot name="value">
                                <div class="flex space-x-1">
                                    <x-button @click="subir(index)" type="button">
                                        <i class="fa fa-arrow-up"></i>
                                    </x-button>
                                    <x-button @click="bajar(index)" type="button">
                                        <i class="fa fa-arrow-down"></i>
                                    </x-button>
                                    <x-button variant="danger" @click="eliminarCuadrillero(index)" type="button">
                                        <i class="fa fa-trash"></i>
                                    </x-button>
                                </div>
                            </x-slot>
                        </x-resumen-item>
                    </template>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarAgregarCuadrillero', false)"
                wire:loading.attr="disabled">
                Cerrar
            </x-button>
            <x-button wire:click="agregarListaAgregada" wire:loading.attr="disabled">
                <i class="fa fa-plus"></i> Agregar Cuadrilleros
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
@script
    <script>
        Alpine.data('formAgregarCuadrillero', () => ({
            codigo_grupo: @entangle('codigo_grupo'),
            selectedIndex: 0,
            cuadrillerosFiltrados: [],
            cuadrillerosBuscar: @json($listaCuadrilleros),
            search: '',
            cuadrillerosAgregados: @entangle('cuadrillerosAgregados'),
            get cuadrillerosFiltrados() {
                if (!this.search.trim()) return [];
                return this.cuadrillerosBuscar.filter(c =>
                    (c.nombres?.toLowerCase() || '').includes(this.search.toLowerCase()) ||
                    (c.dni?.toLowerCase() || '').includes(this.search.toLowerCase())
                );
            },
            setSelectedIndex(index) {
                this.selectedIndex = index;
            },
            agregarCuadrillero(cuadrillero) {

                const yaExiste = this.cuadrillerosAgregados.some(c => c.nombres.toUpperCase() === cuadrillero
                    .nombres.toUpperCase());
                if (yaExiste) {
                    alert('El cuadrillero ya está agregado en la tabla');
                    this.search = '';
                    this.cuadrillerosFiltrados = [];
                    this.$refs.buscador.focus();
                    return;
                }

                this.cuadrillerosAgregados.push({
                    nombres: cuadrillero.nombres,
                    id: cuadrillero.id
                });
                this.search = '';
                this.cuadrillerosFiltrados = [];
                this.$refs.buscador.focus();
            },
            subir(index) {
                if (index > 0) {
                    [this.cuadrillerosAgregados[index - 1], this.cuadrillerosAgregados[index]] = [this
                        .cuadrillerosAgregados[index], this.cuadrillerosAgregados[index - 1]
                    ];
                }
            },
            bajar(index) {
                if (index < this.cuadrillerosAgregados.length - 1) {
                    [this.cuadrillerosAgregados[index + 1], this.cuadrillerosAgregados[index]] = [this
                        .cuadrillerosAgregados[index], this.cuadrillerosAgregados[index + 1]
                    ];
                }
            },
            eliminarCuadrillero(index) {
                this.cuadrillerosAgregados.splice(index, 1);
            },
            navigateList(event) {
                if (this.cuadrillerosFiltrados.length === 0) return;

                if (event.key === 'ArrowDown') {
                    this.selectedIndex = (this.selectedIndex + 1) % this.cuadrillerosFiltrados.length;
                } else if (event.key === 'ArrowUp') {
                    this.selectedIndex = (this.selectedIndex - 1 + this.cuadrillerosFiltrados.length) % this
                        .cuadrillerosFiltrados.length;
                } else if (event.key === 'Enter') {
                    this.agregarCuadrillero(this.cuadrillerosFiltrados[this.selectedIndex]);
                }
            },


            registrarComoNuevo() {
                if (!this.search.trim()) return; // evita registrar vacío

                const cuadrillero = {
                    id: null, // porque no existe en la BD
                    nombres: this.search.trim().toUpperCase()
                };

                this.agregarCuadrillero(cuadrillero);
            }
        }));
    </script>
@endscript
