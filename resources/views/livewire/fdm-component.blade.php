<div>

    <x-title>
        Costos FDM
    </x-title>
    <x-subtitle>Estos costos se suman y se envian a COSTO OPERATIVO / MANO DE OBRA INDIRECTA</x-subtitle>

    <x-card class="mt-3">
        <div class="flex items-center justify-between">

            <x-button variant="secondary" wire:click="mesAnterior">
                <i class="fa fa-chevron-left"></i> Mes Anterior
            </x-button>

            <x-flex>

                <x-select wire:model.live="mes">
                    <option value="01">Enero</option>
                    <option value="02">Febrero</option>
                    <option value="03">Marzo</option>
                    <option value="04">Abril</option>
                    <option value="05">Mayo</option>
                    <option value="06">Junio</option>
                    <option value="07">Julio</option>
                    <option value="08">Agosto</option>
                    <option value="09">Septiembre</option>
                    <option value="10">Octubre</option>
                    <option value="11">Noviembre</option>
                    <option value="12">Diciembre</option>
                </x-select>
                <x-input type="number" wire:model.live.debounce.600ms="anio" class="text-center !mt-0 !w-auto"
                    min="1900" />
            </x-flex>


            <!-- Botón para mes posterior -->
            <x-button variant="secondary" wire:click="mesSiguiente" class="ml-3">
                Mes Siguiente <i class="fa fa-chevron-right"></i>
            </x-button>
        </div>
    </x-card>

    <livewire:fdm-costos-component :mes="$mes" :anio="$anio"
        wire:key="k{{ $mes }}-{{ $anio }}" />
        
    <x-loading wire:loading />
</div>
