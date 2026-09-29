<div x-data="gestion_riego">
    <x-dialog-modal maxWidth="full" wire:model="mostrarFormularioAgregarRegador">
        <x-slot name="title">
            Agregar regadores
        </x-slot>

        <x-slot name="content">
            <x-flex>
                <x-select wire:model="tipoPersonal" label="Tipo de Personal" class="w-auto">
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
                            <x-th class="text-center">Tipo</x-th>
                            <x-th>Nombre</x-th>
                            <x-th class="text-center">Acciones</x-th>
                        </x-tr>
                    </x-slot>

                    <x-slot name="tbody">
                        <template x-for="(item, index) in trabajadoresAgregados"
                            :key="`${item.id}-${item.tipoPersonal}`">
                            <x-tr>
                                <x-td x-text="index + 1" class="text-center" />
                                <x-td x-text="item.tipo" class="text-center uppercase" />
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
                document.addEventListener('delay-riegos', function() {
                    setTimeout(function() {
                        location.href = location.href;
                    }, 1000); // 2000 milisegundos (2 segundos) de retraso
                });
            },
            agregarRegador() {

                if (!this.regadorSeleccionado) return;

                // Buscamos en trabajadores para obtener el objeto completo
                const seleccionado = this.tipoPersonal == 'empleados' ? this.trabajadores.find(c => c.id == this
                    .regadorSeleccionado) : this.cuadrilleros.find(c => c.id == this.regadorSeleccionado);
                if (!seleccionado) {
                    this.regadorSeleccionado = null;
                    return;
                }

                // Verificar si ya existe
                const yaExiste = this.trabajadoresAgregados.some(c => c.id == seleccionado.id && c.tipo == this
                    .tipoPersonal);
                if (yaExiste) {
                    this.regadorSeleccionado = null;
                    return;
                }

                // Agregar a la lista
                this.trabajadoresAgregados.push({
                    nombre: seleccionado.name,
                    id: seleccionado.id,
                    tipo: this.tipoPersonal
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
