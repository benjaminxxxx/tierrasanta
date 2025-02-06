<div>
    <x-loading wire:loading />
    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Registro de Kardex
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-3">
                    <x-label for="nombre" value="Nombre del Kardex"/>
                    <x-input type="text" wire:model="nombre" placeholder="Ejemplo: Kardex {{date('Y')}}" />
                    <x-input-error for="nombre" />
                </div>
                <div class="mb-3">
                    <x-label for="fecha_inicial" value="Fecha de Inicio"/>
                    <x-input type="date" wire:model="fecha_inicial" />
                    <x-input-error for="fecha_inicial" />
                </div>
                <div class="mb-3">
                    <x-label for="fecha_final" value="Fecha de Fin"/>
                    <x-input type="date" wire:model="fecha_final" />
                    <x-input-error for="fecha_final" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-flex>
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button wire:click="storeKardexForm" wire:loading.attr="disabled">
                    <i class="fa fa-save"></i> Registrar
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>
</div>
