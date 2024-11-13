<div>
    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Personaliza el precio por cuadrillero
        </x-slot>

        <x-slot name="content">

            <x-flex>
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
                                                <x-td class="text-center">
                                                    <x-input class="!w-20 !p-2 text-center" wire:key="{{$diaFecha}}.{{$cuadrillero['cua_asi_sem_cua_id']}}" wire:model.live.debounce.1000ms="diasSemana.{{$diaFecha}}.cuadrillero.{{$cuadrillero['cua_asi_sem_cua_id']}}"  />
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
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                Cerrar
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>
</div>
