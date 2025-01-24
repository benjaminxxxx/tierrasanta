<div>

    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Agregue a los Cuadrilleros que trabajaron ese día a esta Labor y Campo
        </x-slot>

        <x-slot name="content">
            @if ($actividad)
                <p>Esta labor: <b>{{ $actividad->labores->nombre_labor }}</b> se realizó en un lapso de
                    <b>{{ $actividad->horas_trabajadas }}</b> horas en el campo
                    <b>{{ $actividad->campo }}</b>.
                </p>
                @if ($actividad->labor_valoracion_id)
                    <p>Esta labor tiene una valoración promedio de
                        <b>{{ $actividad->valoracion->kg_8 }}Kg.</b> en
                        8
                        horas
                    </p>
                    <p>Cada Kg. después de
                        <b>{{ ($actividad->valoracion->kg_hora) * $actividad->horas_trabajadas }}Kg</b>.
                        tiene un valor de
                        <b>{{ $actividad->valoracion->valor_kg_adicional }}</b> por hora.
                    </p>
                @else
                    <p>Esta Labor no tiene una valoración por hora.</p>
                @endif
            @endif

            <div>
                @if ($actividad)
                    <x-h3>
                        Cuadrilleros que realizaron: {{ $actividad->labores->nombre_labor }}
                    </x-h3>
                    <x-table class="my-3">
                        <x-slot name="thead">
                            <x-tr>
                                <x-th class="text-center">
                                    N°
                                </x-th>
                                <x-th>
                                    Cuadrillero
                                </x-th>
                                
                                @if ($actividad->labor_valoracion_id)
                                    @foreach ($actividad->recogidas as $indice => $recogida)
                                        <x-th class="text-center">
                                            Recogida {{ $indice + 1 }} ({{$recogida->kg_estandar}})
                                        </x-th>
                                    @endforeach
                                    <x-th class="text-center">
                                        Bono
                                    </x-th>
                                @endif
                                <x-th class="text-center">
                                    Trabajó en esta Actividad
                                </x-th>
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @php
                                $indiceContador = 0;
                            @endphp
                            @if ($cuadrillerosAgregados)
                                @foreach ($cuadrillerosAgregados as $indice2 => $cuadrilleroAgregado)
                                    @php
                                        $indiceContador++;
                                    @endphp
                                    <x-tr>
                                        <x-td class="text-center">
                                            {{ $indiceContador }}
                                        </x-td>
                                        <x-td>
                                            <p>
                                                {{ $cuadrilleroAgregado['grupo_nombre'] }} -
                                                {{ $cuadrilleroAgregado['nombres'] }}
                                            </p>
                                        </x-td>
                                        @if ($actividad->labor_valoracion_id)
                                            @foreach ($actividad->recogidas as $indice => $recogida)
                                                <x-td class="text-center">
                                                    <x-input class="!w-16 !p-2 text-center"
                                                        wire:model="cuadrillerosSeleccionados.{{$cuadrilleroAgregado['cua_asi_sem_cua_id']}}.recogida.{{ $recogida->id }}"
                                                        wire:key="cuadrillerosSeleccionados.{{$cuadrilleroAgregado['cua_asi_sem_cua_id']}}.recogida.{{ $recogida->id }}" />
                                                </x-td>
                                            @endforeach
                                            <x-td class="text-center">
                                                {{ $cuadrilleroAgregado['total_bono'] }}
                                            </x-td>
                                        @endif
                                        <x-td class="text-center">
                                            <x-checkbox wire:model="cuadrillerosSeleccionados.{{$cuadrilleroAgregado['cua_asi_sem_cua_id']}}.trabajo" wire:key="cuadrillerosSeleccionados.{{$cuadrilleroAgregado['cua_asi_sem_cua_id']}}.trabajo"/>
                                        </x-td>
                                    </x-tr>
                                @endforeach
                            @endif
                        </x-slot>
                    </x-table>

                @endif
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-flex>
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                @if (count($cuadrillerosAgregados) > 0)
                    <x-button wire:click="registrarCuadrilleros" wire:loading.attr="disabled">
                        <i class="fa fa-plus"></i>Registrar cuadrilleros
                    </x-button>
                @endif
            </x-flex>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>
