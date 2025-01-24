<div>
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Registro de Campaña
                </x-h3>
                <div class="flex-shrink-0">
                    <button wire:click="$set('mostrarFormulario',false)" class="focus:outline-none">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot name="content">
            <div>
                @if ($ultimaCampania)
                    <p><b>Nombre de ultima Campaña: </b>{{ $ultimaCampania->nombre_campania }}</p>
                    <p>Rango: {{ $ultimaCampania->fecha_inicio }} - {{ $ultimaCampania->fecha_fin }}</p>
                @else
                    <p>No existe una campaña actual.</p>
                @endif
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="mb-3">
                    <x-label for="fechaInicio">Fecha de Inicio</x-label>
                    <x-input type="date" wire:model.live="fechaInicio" class="uppercase" />
                    <x-input-error for="fechaInicio" />
                </div>
                <div class="mb-3">
                    <x-label for="nombreCampania">Nombre de la Campaña</x-label>
                    <x-input type="text" wire:model.live="nombreCampania" class="uppercase" />
                    <x-input-error for="nombreCampania" />
                </div>
            </div>
            <div>
                @if (count($errorMensaje))
                    <x-warning>
                        <ul>
                            @foreach ($errorMensaje as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-warning>
                @endif
            </div>
        </x-slot>
        <x-slot name="footer">

            <x-secondary-button type="button" wire:click="$set('mostrarFormulario',false)" wire:loading.attr="disabled"
                class="mr-2">Cancelar</x-secondary-button>

            <x-button type="submit" wire:click="store" wire:loading.attr="disabled" class="ml-3">Registrar</x-button>
        </x-slot>
    </x-dialog-modal>
</div>
