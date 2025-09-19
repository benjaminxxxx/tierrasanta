<x-modal wire:model.live="mostrarAgregarCuadrillero">
    <div class="px-6 py-4">
        <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
            Agregar Cuadrillero
        </div>

        <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
            <div class=" mt-2">
                <x-label for="codigo_grupo" value="Primero seleccione el grupo donde desea agregar cuadrilleros para esta semana" />
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
                            <x-input type="search" wire:model.live="search" class="!pl-10 uppercase" autocomplete="off"
                                placeholder="Busca por Nombres, Apellidos o Documento" required />

                        </div>
                        <x-button type="button" x-show="search && cuadrillerosFiltrados.length === 0"
                            wire:click="registrarComoNuevo" title="Registrar Cuadrillero">
                            <i class="fa fa-save"></i> Registrar como nuevo
                        </x-button>
                    </div>
                    <ul class="mt-2">
                        <template x-for="(cuadrillero, index) in cuadrillerosFiltrados" :key="cuadrillero.id">
                            <li :class="{ 'bg-primary text-primaryText': selectedIndex === index }"
                                class="px-4 py-2 cursor-pointer hover:bg-primary hover:text-primaryText"
                                @mouseenter="setSelectedIndex(index)" @click="agregarCuadrillero(cuadrillero)">
                                <span x-text="cuadrillero.nombres"></span> - DNI: <span x-text="cuadrillero.dni"></span>
                            </li>
                        </template>
                    </ul>

                </div>
            </div>
            <div class="mt-5">
                <template x-for="(c, index) in cuadrillerosAgregados" :key="index">
                    <div
                        class="w-full flex items-start justify-between border p-3 rounded-md bg-white text-gray-700 mb-1 items-center">
                        <!-- Nombre del cuadrillero -->
                        <div class="w-full break-words pr-4">
                            <span x-text="index+1" class="font-bold text-red-600"></span> - <span
                                x-text="c.nombres"></span>
                        </div>

                        <!-- Acciones -->
                        <div class="flex space-x-1">
                            <x-button @click="subir(index)" type="button">
                                <i class="fa fa-arrow-up"></i>
                            </x-button>
                            <x-button @click="bajar(index)" type="button">
                                <i class="fa fa-arrow-down"></i>
                            </x-button>
                            <x-danger-button @click="eliminarCuadrillero(index)" type="button">
                                <i class="fa fa-trash"></i>
                            </x-danger-button>
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </div>

    <div class="flex flex-row justify-end px-6 py-4 bg-whiten dark:bg-boxdarkbase text-end gap-4">
        <x-secondary-button wire:click="$set('mostrarAgregarCuadrillero', false)" wire:loading.attr="disabled">
            Cerrar
        </x-secondary-button>
        <x-button wire:click="agregarListaOBSOLETOAgregada" wire:loading.attr="disabled">
            <i class="fa fa-plus"></i> Agregar Cuadrilleros v2
        </x-button>
    </div>
</x-modal>
<livewire:gestion-cuadrilla.administrar-cuadrillero.cuadrilla-grupo-form-component />