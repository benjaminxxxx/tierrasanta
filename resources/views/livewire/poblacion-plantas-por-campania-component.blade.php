<div>
    <x-loading wire:loading />

    <x-flex class="w-full justify-between my-5">
        <x-h3>Población Plantas</x-h3>
        <x-flex>
            @if ($campania)
            <x-button type="button" @click="$wire.dispatch('agregarEvaluacion',{campaniaId:{{$campania->id}}})">
                <i class="fa fa-plus"></i> Agregar Evaluación
            </x-button>
            @endif
            
        </x-flex>
    </x-flex>
    <x-flex class="!items-start w-full">
        @if ($campania)
            <x-card class="md:w-[35rem]">
                <x-spacing>
                    <x-h3>
                        Resumen de Población Plantas
                    </x-h3>
                    <x-table class="mt-3">
                        <x-slot name="thead">
                        </x-slot>
                        <x-slot name="tbody">
                            <x-tr>
                                <x-th>Fecha de evaluación día cero</x-th>
                                <x-td>{{ formatear_fecha($campania->pp_dia_cero_fecha_evaluacion) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>Nª de pencas madre día cero</x-th>
                                <x-td>{{ $campania->pp_dia_cero_numero_pencas_madre }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>Fecha de evaluación resiembra</x-th>
                                <x-td>{{ formatear_fecha($campania->pp_resiembra_fecha_evaluacion) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>Nª de pencas madre después de resiembra</x-th>
                                <x-td>{{ $campania->pp_resiembra_numero_pencas_madre }}</x-td>
                            </x-tr>
                           
                        </x-slot>

                    </x-table>
                </x-spacing>
            </x-card>
            <div class="flex-1 overflow-auto">
            
                @livewire('reporte-campo-poblacion-plantas-component',['campaniaId' => $campania->id,'campaniaUnica'=>true],key($campania->id))
    
            </div>
        @endif
    </x-flex>
    
</div>
