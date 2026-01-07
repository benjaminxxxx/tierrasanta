<div>
    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="lg">
        <x-slot name="title">
            Historial de Salida por Compra
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                @php
                    $filtro = ['A', 'F', 'V'];
                @endphp
                @if (!$tipoAsistenciaId || ($tipoAsistenciaId && !in_array($codigoOriginal, $filtro)))
                    <x-input type="text" autocomplete="off" label="Código" wire:model="codigo" error="codigo" />
                @else
                    <x-input type="text" autocomplete="off" label="Código" wire:model="codigo" readonly
                        error="codigo" />
                @endif
                {{-- Descripción --}}
                <x-input type="text" autocomplete="off" label="Descripción" wire:model="descripcion"
                    error="descripcion" />

                {{-- Horas Jornal --}}
                <x-input type="text" autocomplete="off" label="Horas Jornal" wire:model="horasJornal"
                    error="horasJornal" />

                <div class="mb-3">
                    <x-input id="color" label="Color" type="color" class="mt-1 block w-full"
                        wire:model.live="color" error="color" />
                    <div class="w-10 h-10 mt-2 border rounded border-1 border-gray-400"
                        style="background-color:{{ $color }}"></div>
                </div>

            </div>
        </x-slot>

        <x-slot name="footer">
            <x-flex class="justify-end">
                <x-button variant="secondary" wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-button>
                <x-button wire:click="guardarPlanTipoAsistencia" wire:loading.attr="disabled">

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
