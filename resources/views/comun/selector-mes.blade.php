<x-card2>
    <x-flex class="justify-between">
        <x-button wire:click="mesAnterior">
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

            <x-input type="number"
                     class="text-center !mt-0 !w-auto"
                     min="1900"
                     wire:model="anio"
            />
        </x-flex>

        <x-button wire:click="mesSiguiente">
            Mes Siguiente <i class="fa fa-chevron-right"></i>
        </x-button>
    </x-flex>
</x-card2>
