<div>
    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Personaliza el precio por cuadrillero
        </x-slot>

        <x-slot name="content">

            <x-flex class="w-full">
                <div class="flex-1">
                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th class="text-center" rowspan="2">
                                    N°
                                </x-th>
                                <x-th rowspan="2">
                                    Cudrillero
                                </x-th>
                                @if ($diasSemana)
                                    @foreach ($diasSemana as $numero => $diaSemana)
                                        <x-th class="text-center">{{ $numero }}</x-th>
                                    @endforeach
                                @endif
                            </x-tr>
                            <x-tr>
                                @if ($diasSemana)
                                    @foreach ($diasSemana as $numero => $diaSemana)
                                        <x-th class="text-center">{{ $diaSemana['dia'] }}</x-th>
                                    @endforeach
                                @endif
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @if ($cuadrilleros)
                                @foreach ($cuadrilleros as $indice => $cuadrillero)
                                    <x-tr>
                                        <x-td class="text-center">
                                            {{ $indice + 1 }}
                                        </x-td>
                                        <x-td>
                                            {{ $cuadrillero['nombres'] }}
                                        </x-td>
                                        @if ($diasSemana)
                                            @foreach ($diasSemana as $diaFecha => $diaSemana)
                                                @php
                                                    $claseBase =  $diasSemana[$diaFecha]['cuadrillero'][$cuadrillero['cua_asi_sem_cua_id']]['costoReferencia'];
                                                    $claseColor = '';
                                                    if($claseBase == 'semana'){
                                                        $claseColor = '!text-lime-600';
                                                    }elseif($claseBase == 'cuadrillero'){
                                                        $claseColor = '!text-amber-400';
                                                    }
                                                @endphp
                                                <x-td class="text-center">
                                                    <x-input class="!w-20 !p-2 text-center {{$claseColor}}"
                                                        wire:key="{{ $diaFecha }}.{{ $cuadrillero['cua_asi_sem_cua_id'] }}"
                                                        wire:model.live.debounce.1000ms="diasSemana.{{ $diaFecha }}.cuadrillero.{{ $cuadrillero['cua_asi_sem_cua_id'] }}.costoDia" />
                                                </x-td>
                                            @endforeach
                                        @endif
                                    </x-tr>
                                @endforeach
                            @endif
                            
                        </x-slot>
                    </x-table>
                </div>
            </x-flex>
            <div>
                <b class="block my-2">Referencia:</b>
                <ul>
                    <li>
                        <x-flex>
                            <div class="w-5 h-5 block bg-lime-600"></div>
                            Montos Personalizados por Día
                        </x-flex>
                    </li>
                    <li>
                        <x-flex>
                            <div class="w-5 h-5 block bg-amber-400"></div>
                            Montos Personalizados por Cuadrillero
                        </x-flex>
                    </li>
                </ul>
                <b class="block my-2">Instrucciones:</b>
                <ul>
                    <li>
                        <p>Modifique directamente los precios, el sistema te permitirá 1 segundo para que puedas digitar y luego lo guardará de forma automática.</p>
                    </li>
                    <li>
                        <p>
                            Si deseas quitar el precio personalizado, simplemente bórralo, el sistema borrará el registro y usará el precio semanal o si existe, el precio personalizado por día.
                        </p>
                    </li>
                </ul>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                Cerrar
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>
</div>
