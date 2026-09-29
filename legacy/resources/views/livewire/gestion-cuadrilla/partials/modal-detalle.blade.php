{{-- resources/views/livewire/gestion-cuadrilla/partials/modal-detalle.blade.php --}}
<x-dialog-modal wire:model="mostrarModalDetalle">
    <x-slot name="title">Agregar Gasto</x-slot>

    <x-slot name="content">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-label>Nro. Documento</x-label>
                <x-input wire:model="formDetalle.nro_documento" />
            </div>
            <div>
                <x-label>Ref. Nro Caja</x-label>
                <x-input wire:model="formDetalle.referencia_nro_caja" />
            </div>
            <div class="col-span-2">
                <x-label>Razón Social</x-label>
                <x-input wire:model="formDetalle.razon_social" />
            </div>
            <div>
                <x-label>Tipo de Gasto</x-label>
                <x-select wire:model="formDetalle.tipo_gasto">
                    <option value="PAGO_CUADRILLA">Pago Cuadrilla</option>
                    <option value="PAGO_BONOS_ACUMULADOS">Bonos Acumulados</option>
                    <option value="GASTO_ADICIONAL">Gasto Adicional</option>
                </x-select>
                <x-input-error for="formDetalle.tipo_gasto" />
            </div>
            <div>
                <x-label>Monto</x-label>
                <x-input type="number" step="0.01" wire:model="formDetalle.monto" />
                <x-input-error for="formDetalle.monto" />
            </div>
            <div class="col-span-2">
                <x-label>Descripción</x-label>
                <x-input wire:model="formDetalle.descripcion" />
                <x-input-error for="formDetalle.descripcion" />
            </div>
            <div class="col-span-2">
                <x-label>Observaciones</x-label>
                <x-input wire:model="formDetalle.observaciones" />
            </div>
        </div>
    </x-slot>

    <x-slot name="footer">
        <x-button variant="secondary" wire:click="$set('mostrarModalDetalle', false)">Cancelar</x-button>
        <x-button variant="primary" wire:click="guardarDetalle">
            <i class="fa-solid fa-check"></i> Guardar
        </x-button>
    </x-slot>
</x-dialog-modal>