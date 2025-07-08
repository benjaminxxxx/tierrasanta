<div>
    <x-modal wire:model.live="mostrarFormularioRegistroDiarioCuadrilla" maxWidth="full">
        <div x-data="registro_cuadrilla_diaria">
            <div class="px-6 py-4">
                <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Registro de reporte diario de cuadrilla
                </div>

                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">

                    <x-flex class="mt-3 mb-2 w-full">
                        <x-input-date wire:model="fecha" label="Seleccione una fecha" error="fecha" />
                    </x-flex>
                    <x-flex class="mt-3 mb-2 w-full">
                        <x-h3>Agregar las labores realizadas</x-h3>
                        <x-secondary-button wire:click="agregarActividad">
                            <i class="fa fa-plus"></i> Agregar labor
                        </x-secondary-button>
                    </x-flex>

                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th>Inicio</x-th>
                                <x-th>Fin</x-th>
                                <x-th>Campo</x-th>
                                <x-th>Labor</x-th>
                                <x-th>Acciones</x-th>
                            </x-tr>
                        </x-slot>

                        <x-slot name="tbody">
                            @foreach ($actividades as $index => $actividad)
                                <x-tr>
                                    <x-td>
                                        <x-input type="time" wire:model="actividades.{{ $index }}.inicio" />
                                    </x-td>
                                    <x-td>
                                        <x-input type="time" wire:model="actividades.{{ $index }}.fin" />
                                    </x-td>
                                    <x-td>
                                        <x-select-campo wire:model="actividades.{{ $index }}.campo"
                                            placeholder="Selecciona un campo" label="" />
                                    </x-td>
                                    <x-td>
                                        <x-searchable-select :options="$labores" wire:model="actividades.{{ $index }}.labor"
                                            search-placeholder="Selecciona una labor" />
                                    </x-td>
                                    <x-td>
                                        <x-danger-button wire:click="removerActividad({{ $index }})">
                                            <i class="fa fa-trash"></i>
                                        </x-danger-button>
                                    </x-td>
                                </x-tr>
                            @endforeach

                        </x-slot>
                    </x-table>

                    <x-flex class="mt-3 mb-2">
                        <x-h3>Agregar cuadrilleros que realizaron dichas labores</x-h3>
                    </x-flex>
                    <x-group-field>
                        <x-searchable-select :options="$cuadrilleros" placeholder="Seleccionar Cuadrillero"
                            wire:model="cuadrilleroSeleccionado" />

                    </x-group-field>

                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th>#</x-th>
                                <x-th>DNI</x-th>
                                <x-th>Nombre</x-th>
                                <x-th>Acciones</x-th>
                            </x-tr>
                        </x-slot>

                        <x-slot name="tbody">
                            <template x-for="(item, index) in cuadrillerosAgregados" :key="item.id">
                                <x-tr>
                                    <x-td x-text="index + 1" />
                                    <x-td x-text="item.dni" />
                                    <x-td x-text="item.nombre" />
                                    <x-td>
                                        <x-danger-button @click="quitarCuadrillero(index)">
                                            <i class="fa fa-trash"></i>
                                        </x-danger-button>
                                    </x-td>
                                </x-tr>
                            </template>

                            <template x-if="cuadrillerosAgregados.length === 0">
                                <x-tr>
                                    <x-td colspan="4">
                                        Sin cuadrilleros agregados.
                                    </x-td>
                                </x-tr>
                            </template>
                        </x-slot>
                    </x-table>

                </div>
            </div>

            <div class="flex flex-row justify-end px-6 py-4 bg-whiten dark:bg-boxdarkbase text-end">
                <x-flex>
                    <x-secondary-button wire:click="$set('mostrarFormularioRegistroDiarioCuadrilla', false)"
                        wire:loading.attr="disabled">
                        Cerrar
                    </x-secondary-button>
                    <x-button wire:click="guardarRegistroDiarioCuadrilla">
                        <i class="fa fa-save"></i> Guardar cambios
                    </x-button>
                </x-flex>
            </div>
        </div>
    </x-modal>
    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('registro_cuadrilla_diaria', () => ({
        cuadrillerosAgregados: @entangle('cuadrillerosAgregados'),
        todosCuadrilleros: @js($todosCuadrilleros),
        cuadrilleroSeleccionado: @entangle('cuadrilleroSeleccionado'),

        init() {
            this.$watch('cuadrilleroSeleccionado', (value) => {

                this.agregarCuadrillero();
            });
        },
        /*
                agregarActividad() {
                    this.actividades.push({
                        inicio: '',
                        fin: '',
                        campo: '',
                        labor: ''
                    });
                },
                 removerActividad(index) {
            this.actividades.splice(index, 1);
        },*/



        agregarCuadrillero() {
            if (!this.cuadrilleroSeleccionado) return;

            // Buscamos en todosCuadrilleros para obtener el objeto completo
            const seleccionado = this.todosCuadrilleros.find(c => c.id == this.cuadrilleroSeleccionado);
            if (!seleccionado) {
                this.cuadrilleroSeleccionado = null;
                return;
            }

            // Verificar si ya existe
            const yaExiste = this.cuadrillerosAgregados.some(c => c.id == seleccionado.id);
            if (yaExiste) {
                this.cuadrilleroSeleccionado = null;
                return;
            }

            // Agregar a la lista
            this.cuadrillerosAgregados.push({
                id: seleccionado.id,
                nombre: seleccionado.name,
                dni: seleccionado.dni
            });

            // Limpiar el input
            this.cuadrilleroSeleccionado = null;
        },

        quitarCuadrillero(index) {
            this.cuadrillerosAgregados.splice(index, 1);
        }
    }));
</script>
@endscript