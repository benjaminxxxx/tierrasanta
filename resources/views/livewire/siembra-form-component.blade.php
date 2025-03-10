<div>
    
    <x-loading wire:loading />

    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Registro de Siembra
        </x-slot>

        <x-slot name="content">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 ">
                
                <x-input-date wire:model="fecha_siembra" label="Fecha de Siembra" />
                <x-input-date wire:model="fecha_renovacion" label="Fecha de Limpieza" />
                <x-select-campo wire:model="campo_nombre" label="Campo" placeholder="Elige un campo" class="w-full" />
                <x-input-string wire:model="variedad_tuna" label="Variedad de Tuna" />
                <x-input-string wire:model="sistema_cultivo" label="Sistema de Cultivo" />
                <x-input-number wire:model="tipo_cambio" label="Tipo de Cambio" />

            </div>

        </x-slot>
        <x-slot name="footer">

            <x-form-buttons action="storeSiembra" id="{{ $siembra_id ?? '' }}" />

        </x-slot>
    </x-dialog-modal>
    
</div>
