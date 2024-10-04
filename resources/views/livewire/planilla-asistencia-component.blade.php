<div>
    <x-loading wire:loading />
    <x-card>
        <x-spacing>
            <div class="md:flex items-center justify-between">

                <x-secondary-button wire:click="mesAnterior">
                    <i class="fa fa-chevron-left"></i> Mes Anterior
                </x-secondary-button>

                <div class="flex items-center gap-5">
                    <!-- Selección de mes -->
                    <div class="mx-2">
                        <x-select wire:model.live="mes" class="!mt-0  !w-auto">
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
                    </div>

                    <!-- Selección de año -->
                    <div class="mx-2">
                        <x-input type="number" wire:model.live="anio" class="text-center !mt-0 !w-auto"
                            min="1900" />
                    </div>
                </div>


                <!-- Botón para mes posterior -->
                <x-secondary-button wire:click="mesSiguiente" class="ml-3">
                    Mes Siguiente <i class="fa fa-chevron-right"></i>
                </x-secondary-button>
            </div>
        </x-spacing>
    </x-card>

    <livewire:planilla-asistencia-detalle-component wire:key="{{$mes}}-{{$mes}}" :mes="$mes" :anio="$anio"/>
</div>
