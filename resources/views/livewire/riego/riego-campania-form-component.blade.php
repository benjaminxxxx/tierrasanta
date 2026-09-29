<div>
    <x-dialog-modal wire:model.live="mostrarFormularioRiegoCampania">
        <x-slot name="title">
            Editar Infestación
        </x-slot>

        <x-slot name="content">
            <form wire:submit="guardarRiegoCampania" id="form-eval-riego-campania" class="space-y-4">
                <x-input type="number" label="Descarga por Hectárea (m3/ha/hora)" wire:model="riego_descarga_ha_hora" />
            </form>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarFormularioRiegoCampania', false)" wire:loading.attr="disabled">
                Cerrar
            </x-button>
            <x-button class="ml-2" wire:click="guardarRiegoCampania" form="form-eval-riego-campania" type="submit" wire:loading.attr="disabled">
                <i class="fa fa-save"></i> Guardar Cambios
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>