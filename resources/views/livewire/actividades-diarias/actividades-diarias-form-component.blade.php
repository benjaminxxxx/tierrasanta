<div>
    <x-modal maxWidth="full" wire:model="mostrarFormularioActividadDiaria" class="overflow-y-auto">
        <div class="px-6 py-4">
            <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                <x-h3>
                    Agregar Nueva Actividad
                </x-h3>
            </div>

            <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input-date wire:model="fecha" label="Seleccione una fecha" />
                    <x-group-field>
                        <x-label for="laborSeleccionada" value="Seleccione una labor" />
                        <x-searchable-select :options="$laboresSeleccion" search-placeholder="Selecciona una labor"
                            wire:model.live="laborSeleccionada" />
                        <x-input-error for="laborSeleccionada" />
                    </x-group-field>

                    <x-group-field>
                        <x-label for="horaInicio" value="Hora de inicio y fin" />
                        <x-flex>
                            <div>
                                <x-input type="time" wire:model="horaInicio" />
                                <x-input-error for="horaInicio" />
                            </div>
                            <div>
                                <x-input type="time" wire:model="horaFin" />
                                <x-input-error for="horaFin" />
                            </div>
                        </x-flex>
                    </x-group-field>
                </div>

                <br>
            </div>
        </div>

        <div class="flex flex-row justify-end px-6 py-4 bg-whiten dark:bg-boxdarkbase text-end gap-4">
            <x-secondary-button @click="$wire.set('mostrarFormularioActividadDiaria', false)">
                Cancelar
            </x-secondary-button>
            <x-button wire:click="test">
                <i class="fa fa-save"></i> Guardar Actividad
            </x-button>
        </div>
    </x-modal>
    <x-loading wire:loading />
</div>