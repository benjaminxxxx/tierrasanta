<div>
    <x-loading wire:loading />
    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Resumen de labores realizadas para Planilla
        </x-h3>
    </div>
    <x-card2>
        <x-flex class="justify-between">
            <x-secondary-button wire:click="mesAnterior">
                <i class="fa fa-chevron-left"></i> Mes Anterior
            </x-secondary-button>
            <x-flex>
                <x-select-meses wire:model.live="mes" />
                <x-group-field class="!mb-2">
                    <x-label>
                        Año
                    </x-label>
                    <x-input type="number" wire:model.live="anio" class="text-center" min="1900" />
                </x-group-field>
            </x-flex>
            <x-secondary-button wire:click="mesSiguiente">
                Mes Siguiente <i class="fa fa-chevron-right"></i>
            </x-secondary-button>
        </x-flex>
    </x-card2>
    <livewire:resumen-planilla-detalle-component :mes="$mes" :anio="$anio"
        wire:key="reporte_detalle_{{ $mes }}_{{ $anio }}" />
</div>