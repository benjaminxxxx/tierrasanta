<div x-data="gestion_riego">

    <x-card>
        <x-flex class="justify-between">
            <x-flex>
                <x-button variant="secondary" wire:click="fechaAnterior" class="w-full lg:w-auto">
                    <i class="fa fa-chevron-left"></i> Fecha Anterior
                </x-button>
                <x-input type="date" wire:model.live.debounce.1500ms="fecha" class="text-center w-full lg:w-auto" />
                <x-button variant="secondary" wire:click="fechaPosterior" class="w-full lg:w-auto">
                    Fecha Posterior <i class="fa fa-chevron-right"></i>
                </x-button>
            </x-flex>
            <x-flex>
                <x-button @click="$wire.set('mostrarFormularioAgregarRegador', true)">
                    <i class="fa fa-plus"></i> Agregar Regador
                </x-button>
                <div x-data="{ open: false }" class="my-4 lg:my-0">
                    <!-- Dropdown Button -->
                    <x-button @click="open = !open"
                        class="flex items-center justify-center w-full lg:w-auto whitespace-nowrap" type="button">
                        Opciones Adicionales
                        <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m1 1 4 4 4-4" />
                        </svg>
                    </x-button>
                    <!-- Dropdown Menu -->
                    <div x-show="open" @click.outside="open = false"
                        class="z-10 text-base relative mr-5 list-none border-1 border-gray-500 bg-white divide-y divide-gray-100 rounded-lg shadow-lg w-auto dark:bg-gray-700">
                        <div class="absolute bg-white shadow-lg">
                            <ul class="py-2">
                                <li>
                                    <button @click="$wire.dispatch('guardarTodo')"
                                        class="w-full text-left block px-4 py-2 hover:bg-bodydark1 hover:text-primary whitespace-nowrap">
                                        Guardar Todo
                                    </button>
                                </li>
                                <li>
                                    <a href="#" wire:click.prevent="descargarBackup"
                                        class="block px-4 py-2 hover:bg-bodydark1 hover:text-primary whitespace-nowrap">
                                        Descargar Backup {{ $fecha }}
                                    </a>
                                </li>
                                <li>
                                    <livewire:gestion-riego.reporte-diario-riego-import-export-component
                                        :fecha="$fecha" />
                                </li>
                                <li>
                                    <a href="#" wire:click.prevent="descargarBackupCompleto"
                                        class="block px-4 py-2 hover:bg-bodydark1 hover:text-primary  whitespace-nowrap">
                                        Descargar Backup Completo
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </x-flex>
        </x-flex>
    </x-card>
    <div class="my-4">
        @if ($consolidados && $consolidados->count() > 0)
            @foreach ($consolidados as $riego)
                <livewire:gestion-riego.reporte-diario-riego-detalle-component :regador="$riego->regador_documento"
                    :fecha="$riego->fecha" wire:key="horas_riego_{{ $riego->regador_documento }}_{{ $riego->fecha }}" />
            @endforeach
        @endif
    </div>
    <x-dialog-modal maxWidth="full" wire:model="mostrarFormularioAgregarRegador">
        <x-slot name="title">
            Agregar regadores
        </x-slot>

        <x-slot name="content">
            <x-flex>
                <x-select wire:model="tipoPersonal" label="Tipo de Personal">
                    <option value="empleados">Empleados</option>
                    <option value="cuadrilleros">Cuadrilleros</option>
                </x-select>
                <x-group-field x-show="tipoPersonal=='empleados'">
                    {{-- los campos searchables esperan id name atributos --}}
                    <x-label for="regadorSeleccionado" value="Selecciona un trabajador" />
                    <x-searchable-select :options="$trabajadores" search-placeholder="Escriba el nombre del trabajador"
                        wire:model="regadorSeleccionado" />
                    <x-input-error for="regadorSeleccionado" />
                </x-group-field>
                <x-group-field x-show="tipoPersonal=='cuadrilleros'">
                    {{-- los campos searchables esperan id name atributos --}}
                    <x-label for="regadorSeleccionado" value="Selecciona un cuadrillero" />
                    <x-searchable-select :options="$cuadrilleros" search-placeholder="Escriba el nombre del cuadrillero"
                        wire:model="regadorSeleccionado" />
                    <x-input-error for="regadorSeleccionado" />
                </x-group-field>
            </x-flex>
            {{-- Cuadrilleros agregados --}}
            <div class="mt-4">
                <x-table>
                    <x-slot name="thead">
                        <x-tr>
                            <x-th class="text-center">#</x-th>
                            <x-th class="text-center">DNI</x-th>
                            <x-th>Nombre</x-th>
                            <x-th class="text-center">Acciones</x-th>
                        </x-tr>
                    </x-slot>

                    <x-slot name="tbody">
                        <template x-for="(item, index) in trabajadoresAgregados" :key="item.dni">
                            <x-tr>
                                <x-td x-text="index + 1" class="text-center" />
                                <x-td x-text="item.dni"  class="text-center"/>
                                <x-td x-text="item.nombre" />
                                <x-td class="text-center">
                                    <x-button variant="danger" @click="quitarTrabajador(index)" class="m-auto">
                                        <i class="fa fa-trash"></i>
                                    </x-button>
                                </x-td>
                            </x-tr>
                        </template>

                        <template x-if="trabajadoresAgregados.length === 0">
                            <x-tr>
                                <x-td colspan="4">
                                    Sin cuadrilleros agregados.
                                </x-td>
                            </x-tr>
                        </template>
                    </x-slot>
                </x-table>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarFormularioAgregarRegador', false)"
                wire:loading.attr="disabled">
                Cerrar
            </x-button>
            <x-button wire:click="agregarRegadores" wire:loading.attr="disabled">
                <i class="fa fa-plus"></i> Agregar Regadores
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />

</div>
@script
<script>
    Alpine.data('gestion_riego', () => ({
        tipoPersonal: @entangle('tipoPersonal'),
        trabajadoresAgregados: @entangle('trabajadoresAgregados'),
        trabajadores: @js($trabajadores),
        cuadrilleros: @js($cuadrilleros),
        regadorSeleccionado: @entangle('regadorSeleccionado'),
        init() {
            this.$watch('regadorSeleccionado', (value) => {

                this.agregarRegador();
            });
            document.addEventListener('delay-riegos', function () {
                setTimeout(function () {
                    location.href = location.href;
                }, 1000); // 2000 milisegundos (2 segundos) de retraso
            });
        },
        agregarRegador() {

            if (!this.regadorSeleccionado) return;

            // Buscamos en trabajadores para obtener el objeto completo
            const seleccionado = this.tipoPersonal == 'empleados' ? this.trabajadores.find(c => c.id == this.regadorSeleccionado) : this.cuadrilleros.find(c => c.id == this.regadorSeleccionado);
            if (!seleccionado) {
                this.regadorSeleccionado = null;
                return;
            }

            // Verificar si ya existe
            const yaExiste = this.trabajadoresAgregados.some(c => c.dni == seleccionado.id);
            if (yaExiste) {
                this.regadorSeleccionado = null;
                return;
            }
            // Agregar a la lista
            this.trabajadoresAgregados.push({
                nombre: seleccionado.name,
                dni: seleccionado.id
            });

            // Limpiar el input
            this.regadorSeleccionado = null;
        },
        quitarTrabajador(index) {
            this.trabajadoresAgregados.splice(index, 1);
        }
    }));

</script>
@endscript