<div>
    <x-loading wire:loading />
    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
            Registrar Grupo de Cuadrilla
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <!-- Nombre -->
                <div>
                    <x-label for="nombre" value="Nombre del Grupo" />
                    <x-input id="nombre" type="text" class="mt-1 uppercase" wire:model="nombre" />
                    <x-input-error for="nombre" class="mt-2" />
                </div>

                <!-- Código -->
                <div class="mt-2">
                    <x-label for="codigo" value="Código del Grupo" />
                    <x-input id="codigo" type="text" class="mt-1 uppercase" wire:model="codigo" />
                    <x-input-error for="codigo" class="mt-2" />
                </div>

                <!-- Color -->
                <div class="mt-2">
                    <x-label for="color" value="Color del Grupo" />
                    <x-input id="color" type="color" class="mt-1 block w-full" wire:model.live="color" />
                    <div class="w-10 h-10 mt-2 border rounded border-1 border-gray-400"
                        style="background-color:{{ $color }}"></div>
                    <x-input-error for="color" class="mt-2" />
                </div>

                <!-- Modalidad de Pago -->
                <div class="mt-2">
                    <x-label for="modalidad_pago" value="Modalidad de Pago" />
                    <x-select id="modalidad_pago" wire:model="modalidad_pago" class="mt-1">
                        <option value="semanal">Semanal</option>
                        <option value="quincenal">Quincenal</option>
                        <option value="mensual">Mensual</option>
                    </x-select>
                    <x-input-error for="modalidad_pago" class="mt-2" />
                </div>

                <!-- Costo Día Sugerido -->
                <div class="mt-2">
                    <x-label for="costo_dia_sugerido" value="Costo Día Sugerido" />
                    <x-input id="costo_dia_sugerido" type="number" step="0.01" class="mt-1 block w-full"
                        wire:model="costo_dia_sugerido" />
                    <x-input-error for="costo_dia_sugerido" class="mt-2" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-2">
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button wire:click="registrar" wire:loading.attr="disabled">
                    @if (!$grupoId)
                        Registrar
                    @else
                        Actualizar
                    @endif
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
