<div>
    <x-loading wire:loading />
    <x-card2>
        <div class="flex items-center justify-between">

            <x-secondary-button wire:click="mesAnterior">
                <i class="fa fa-chevron-left"></i> Mes Anterior
            </x-secondary-button>

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

                <!-- Selección de año -->
                <x-group-field>
                    <x-input type="number" wire:model.live="anio" class="text-center !mt-0 !w-auto" min="1900" />
                </x-group-field>
                @if($sePuedeVerNegro)
                    <x-group-field>
                        @if($componente == 'blanco')
                            <x-button type="button" wire:click="ver('negro')">
                                Ver Negro
                            </x-button>
                        @endif
                        @if($componente == 'negro')
                            <x-button type="button" wire:click="ver('blanco')">
                                Ver Blanco
                            </x-button>
                        @endif
                    </x-group-field>
                @endif
            </x-flex>


            <!-- Botón para mes posterior -->
            <x-secondary-button wire:click="mesSiguiente" class="ml-3">
                Mes Siguiente <i class="fa fa-chevron-right"></i>
            </x-secondary-button>
        </div>
    </x-card2>
    @if($componente == 'blanco')
        <livewire:planilla-blanco-detalle-component wire:key="{{$mes}}-{{$anio}}" :mes="$mes" :anio="$anio" />
    @endif
    @if($componente == 'negro')
        <livewire:planilla-negro-detalle-component wire:key="{{$mes}}-{{$anio}}-negro" :mes="$mes" :anio="$anio" />
    @endif


</div>