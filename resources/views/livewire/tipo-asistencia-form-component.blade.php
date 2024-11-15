<div>
    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Historial de Salida por Compra
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="mb-3">

                    <x-label for="codigo">Código</x-label>
                    @php
                        $filtro = ['A', 'F', 'V'];
                    @endphp
                    @if (!$tipoAsistenciaId || ($tipoAsistenciaId && !in_array($codigoOriginal,$filtro)))
                        <x-input autocomplete="off" wire:model="codigo" type="text" />
                    @else
                        <x-input autocomplete="off" wire:model="codigo" class="!bg-gray-100" type="text" readonly />
                    @endif

                    <x-input-error for="codigo" />
                </div>
                <div class="mb-3">
                    <x-label for="descripcion">Descripción</x-label>
                    <x-input autocomplete="off" wire:model="descripcion" type="text" />
                    <x-input-error for="descripcion" />
                </div>
                <div class="mb-3">
                    <x-label for="horasJornal">Horas Jornal</x-label>
                    <x-input autocomplete="off" wire:model="horasJornal" type="text" />
                    <x-input-error for="horasJornal" />
                </div>
                <div class="mb-3">
                    <x-label for="color" value="Color" />
                    <x-input id="color" type="color" class="mt-1 block w-full" wire:model.live="color" />
                    <div class="w-10 h-10 mt-2 border rounded border-1 border-gray-400"
                        style="background-color:{{ $color }}"></div>
                    <x-input-error for="color" class="mt-2" />
                </div>

            </div>
        </x-slot>

        <x-slot name="footer">
            <x-flex class="justify-end">
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button wire:click="storeTipoAsistencia" wire:loading.attr="disabled">

                    @if ($tipoAsistenciaId)
                        <i class="fa fa-sync"></i> Actualizar
                    @else
                        <i class="fa fa-save"></i> Registrar
                    @endif
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>
</div>
