<div class="flex justify-end mt-5">
    <div>
        <x-flex>
            <x-h3>
                Cuadro resumen
            </x-h3>
            <x-button wire:click="recalcularResumen" size="xs" variant="success" >
                <i class="fa fa-sync"></i> Recalcular resumen
            </x-button>
        </x-flex>
        <x-table class="mt-5">
            <x-slot name="thead">
                <x-tr>
                    <x-th class="">Descripción</x-th>
                    <x-th>Acumulación actual</x-th>
                    <x-th>Condición</x-th>
                    <x-th class="text-center">Fecha</x-th>
                    <x-th class="text-center">Recibo</x-th>
                    <x-th class="text-right">Deuda acumulada</x-th>
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                @forelse ($resumenes as $resumen)
                    <x-tr>
                        <x-td class="uppercase font-bold text-black dark:!text-black" style="background-color:{{ $resumen['color'] }}">{{ $resumen['descripcion'] }}</x-td>
                        <x-td class="text-right">{{ formatear_numero($resumen['deuda_actual']) }}</x-td>
                        <x-td class="text-center">
                            @if ($resumen['tipo']=='sueldo')
                                <x-button variant="light" size="xs" class="" @click="$wire.dispatch('abrirReportePagoPorTramo',{tramoResumenId:{{ $resumen['id'] }}})">
                                {{ $resumen['condicion'] }}
                            </x-button>
                            @else
                                <x-button variant="light" size="xs" wire:click="cambiarEstadoResumen({{ $resumen['id'] }})">
                                {{ $resumen['condicion'] }}
                            </x-button>
                            @endif
                            
                        </x-td>
                        <x-td class="text-center">
                            @if ($resumen['condicion']=='Pagado')
                                <x-input type="date" size="xs" wire:model="resumenes.{{ $resumen['id'] }}.fecha"/>
                            @else
                                {{ formatear_fecha($resumen['fecha']) }}        
                            @endif
                        </x-td>
                        <x-td class="text-center">
                            @if ($resumen['condicion']=='Pagado')
                                <x-input type="text" size="xs" @focus="$el.select()" class="text-center uppercase"  wire:model="resumenes.{{ $resumen['id'] }}.recibo" />
                            @else
                                {{ $resumen['recibo']??'-' }}
                            @endif
                        </x-td>
                        <x-td class="text-right">{{ formatear_numero($resumen['deuda_acumulada']) }}</x-td>
                    </x-tr>
                @empty
                    <x-tr>
                        <x-td colspan="6" class="text-center">No hay datos para mostrar</x-td>
                    </x-tr>
                @endforelse
            </x-slot>
        </x-table>
    </div>
</div>