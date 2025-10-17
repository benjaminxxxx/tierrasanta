<x-card2>
    <x-flex class="justify-between">
        <x-button wire:click="mesAnterior" class="!w-auto">
            <i class="fa fa-chevron-left"></i> <span class="hidden md:inline-block">Mes Anterior</span>
        </x-button>

        <x-flex>
            <x-select wire:model.live="mes">
                @foreach ([
                    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
                    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
                    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
                ] as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </x-select>

            <x-input type="number" wire:model.live="anio" class="text-center hidden md:inline-block" min="1900" />
        </x-flex>

        <x-button wire:click="mesSiguiente" class="!w-auto">
            <span class="hidden md:inline-block">Mes Siguiente</span> <i class="fa fa-chevron-right"></i>
        </x-button>
    </x-flex>
    <x-loading wire:loading/>
</x-card2>
