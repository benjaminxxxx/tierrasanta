<div>
    <x-dialog-modal wire:model.live="mostrarAgregarCuadrillero">
        <x-slot name="title">
            Agregar Cuadrillero
        </x-slot>

        <x-slot name="content">
            <div x-data="{
                selectedIndex: 0,
                selectItem() {
                    const cuadrilleroId = this.$refs.results.children[this.selectedIndex].dataset.id;
                    $wire.agregarCuadrillero(cuadrilleroId);
                },
                navigateList(event) {
                    if (event.key === 'ArrowDown') {
                        this.selectedIndex = (this.selectedIndex + 1) % this.$refs.results.children.length;
                    } else if (event.key === 'ArrowUp') {
                        this.selectedIndex = (this.selectedIndex - 1 + this.$refs.results.children.length) % this.$refs.results.children.length;
                    } else if (event.key === 'Enter') {
                        this.selectItem();
                    }
                },
                setSelectedIndex(index) {
                    this.selectedIndex = index;
                }
            }" @keydown.arrow-up.prevent="navigateList($event)"
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
                            <x-input type="search" wire:model.live="search" class="w-full !pl-10" autocomplete="off"
                                placeholder="Busca por Nombres, Apellidos o Documento" required />

                        </div>
                        <x-button type="button" @click="$wire.dispatch('registrarCuadrillero',{seleccionable:true})"
                            title="Registrar Cuadrillero">
                            <i class="fa fa-plus"></i>
                        </x-button>
                    </div>
                    <ul x-ref="results" class="mt-2">

                        @foreach ($results as $index => $cuadrillero)
                            <li data-id="{{ $cuadrillero->id }}"
                                :class="{ 'bg-primary text-primaryText': selectedIndex === {{ $index }} }"
                                class="px-4 py-2 cursor-pointer hover:bg-primary hover:text-primaryText"
                                @mouseenter="setSelectedIndex({{ $index }})"
                                wire:click="agregarCuadrillero({{ $cuadrillero->id }})">
                                {{ $cuadrillero->nombres }} - DNI: {{ $cuadrillero->dni }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="mt-5">
                @foreach ($cuadrillerosAgregados as $indice => $cuadrillerosAgregado)
                    <div
                        class="inline-block px-4 py-2 border rounded-md transition bg-white text-gray-700 border-gray-300">
                        {{ $cuadrillerosAgregado['nombres'] }}
                        <x-danger-button wire:click="eliminarCuadrilleroAsistencia({{ $indice }})" class="ml-2">
                            <i class="fa fa-trash"></i>
                        </x-danger-button>
                    </div>
                @endforeach
            </div>

            @if ($cuadrillerosAgregados)


                <div class=" mt-2">
                    <x-label for="codigo_grupo" value="¿A que grupo se asignarán estas personas?" />
                    <div class="flex items-center gap-2">
                        <x-select wire:model.live="codigo_grupo" class="mt-1 w-full"
                            wire:key="codigo_grupo-{{ $codigo_grupo }}">
                            <option value="">SELECCIONAR GRUPO</option>
                            @foreach ($grupos as $grupo)
                                <option value="{{ $grupo->codigo }}">{{ $grupo->codigo }} - {{ $grupo->nombre }}</option>
                            @endforeach
                        </x-select>

                        <x-button type="button" @click="$wire.dispatch('registrarGrupo')" title="Registrar Grupo">
                            <i class="fa fa-plus"></i>
                        </x-button>
                    </div>
                    <x-input-error for="codigo_grupo" class="mt-2" />
                </div>
            @endif

        </x-slot>

        <x-slot name="footer">
            <x-flex>
                <x-secondary-button wire:click="$set('mostrarAgregarCuadrillero', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                @if ($cuadrillerosAgregados && $codigo_grupo)
                    <x-button wire:click="agregarListaAgregada" wire:loading.attr="disabled">
                        <i class="fa fa-plus"></i> Agregar Cuadrilleros
                    </x-button>
                @endif
            </x-flex>
        </x-slot>
    </x-dialog-modal>
</div>
