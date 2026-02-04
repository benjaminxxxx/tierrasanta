<div>
    <x-flex>
        <x-title>
            Planilla Mensual
        </x-title>
    </x-flex>
    <x-card class="mt-4">
        <x-flex class="justify-between">

            <x-button variant="secondary" wire:click="mesAnterior">
                <i class="fa fa-chevron-left"></i> Mes Anterior
            </x-button>

            <x-flex>
                <x-select-meses wire:model.live="mes" />
                <x-select-anios wire:model.live="anio" />
                @if ($sePuedeVerNegro)
                    <x-group-field>
                        @if ($componente == 'blanco')
                            <x-button type="button" wire:click="ver('negro')">
                                Ver Negro
                            </x-button>
                        @endif
                        @if ($componente == 'negro')
                            <x-button type="button" wire:click="ver('blanco')">
                                Ver Blanco
                            </x-button>
                        @endif
                    </x-group-field>
                @endif
            </x-flex>


            <!-- Botón para mes posterior -->
            <x-button variant="secondary" wire:click="mesSiguiente">
                Mes Siguiente <i class="fa fa-chevron-right"></i>
            </x-button>
        </x-flex>
    </x-card>
    @if ($componente == 'blanco')
        <livewire:planilla-blanco-detalle-component wire:key="{{ $mes }}-{{ $anio }}" :mes="$mes"
            :anio="$anio" />
    @endif
    @if ($componente == 'negro')
        <livewire:planilla-negro-detalle-component wire:key="{{ $mes }}-{{ $anio }}-negro"
            :mes="$mes" :anio="$anio" />
    @endif

    <x-loading wire:loading />
</div>
