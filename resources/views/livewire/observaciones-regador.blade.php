<div>
    <x-dialog-modal-header wire:model="isFormOpen" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Registrar Observaciones
                </x-h3>
                <div class="flex-shrink-0">
                    <button wire:click="closeForm" class="focus:outline-none">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot name="content">
            <div class="lg:flex items-center gap-3">
                <x-table>
                    <x-slot name="thead">

                    </x-slot>
                    <x-slot name="tbody">
                        <tr>
                            <x-th value="Fecha" />
                            <x-td value="{{ $fecha }}" />
                        </tr>
                        <tr>
                            <x-th value="Encargado" />
                            <x-td value="{{ $regadorNombre }}" />
                        </tr>
                        <tr>
                            <x-th value="Uso de Horas acumuladas" />
                            <x-td>
                                @if ($horasAcumuladas && $horasAcumuladas->count() > 0)
                                    @foreach ($horasAcumuladas as $horaAcumulada)
                                        @if(!$horaAcumulada->fecha_uso)
                                        <x-secondary-button class="mr-3" wire:click="usarEstafecha({{$horaAcumulada->id}})">
                                            {{ $horaAcumulada->hora }}
                                        </x-secondary-button>
                                        @else
                                        <x-button class="mr-3" wire:click="noUsarEstafecha({{$horaAcumulada->id}})">
                                            {{ $horaAcumulada->hora }}
                                        </x-button>
                                        @endif
                                    @endforeach
                                @endif
                            </x-td>
                        </tr>
                    </x-slot>
                </x-table>
            </div>


           
                <x-table class="mt-5">
                    <x-slot name="thead">
                        <tr>
                            <x-th value="N°" class="text-center" />
                            <x-th value="Horas" class="text-center" />
                            <x-th value="Observación" class="text-left" />
                            <x-th />
                        </tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @php
                            $contador = 0;
                        @endphp
                        @if ($observaciones)
                        @foreach ($observaciones as $indice => $observacionArray)
                            <x-tr>
                                <x-th class="text-center">
                                    {{ $contador + 1 }}
                                </x-th>
                                <x-td class="text-center">
                                    {{ substr($observacionArray->horas, 0, 5) }}
                                </x-td>
                                <x-td class="text-left">
                                    {{ $observacionArray->detalle_observacion }}
                                </x-td>

                                <x-td class="text-center">
                                    <x-danger-button title="Eliminar Registro"
                                        wire:click="eliminarObservacion({{ $observacionArray->id }})">
                                        <i class="fa fa-trash"></i>
                                    </x-danger-button>
                                </x-td>
                            </x-tr>
                        @endforeach
                        
                        @endif
                        @if ($totalHorasAcumuladas != '00:00')
                        <x-tr>
                            <x-th class="text-center">
                                {{$contador+1}}
                            </x-th>
                            <x-td class="text-center">
                                {{ $totalHorasAcumuladas  }}
                            </x-td>
                            <x-td class="text-left">
                                Uso de Horas Acumuladas
                            </x-td>
                        </x-tr>
                        @endif
                        <x-tr>
                            <x-th class="text-center">
                                N°
                            </x-th>
                            <x-td class="text-center">
                                <x-input type="time" class="!w-36 text-center" wire:model="horas" />
                                <x-input-error for="horas" />
                            </x-td>
                            <x-td class="text-center">
                                <x-input type="text" class="text-left" wire:model="observacion" />
                                <x-input-error for="observacion" />
                            </x-td>
                            <x-td class="text-center">
                                <x-button type="button" wire:click="store" class="mr-2">Agregar
                                    Observaciones</x-button>
                            </x-td>

                        </x-tr>
                    </x-slot>
                </x-table>
            
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="closeForm" class="mr-2">Cerrar</x-secondary-button>

        </x-slot>
    </x-dialog-modal-header>
</div>
