<div>
    <x-loading wire:loading wire:target="mes" />
    <x-card>
        <x-spacing>
            <div class="block md:flex items-center gap-5">
                <x-h2>
                    Planilla de Horas Trabajadas
                </x-h2>
            </div>
            <div class="flex ga-5 my-10">
                <div>
                    <x-label for="mes">Mes</x-label>
                    <x-select class="uppercase" wire:model.live="mes" id="mes">
                        <option value="">Seleccionar Mes</option>
                        @foreach ($meses as $mes)
                            <option value="{{ $mes['value'] }}">{{ $mes['label'] }}</option>
                        @endforeach
                    </x-select>
                </div>
            </div>
           
            <x-table class="mt-5">
                <x-slot name="thead">
                    <tr>
                        <x-th value="N°" class="text-center" />
                        <x-th value="N° de Orden" class="text-center" />
                        <x-th value="Nombres" />
                        @if ($dias)
                            @foreach ($dias as $dia)
                                <x-th value="{{ $dia['dia'] }}" />
                            @endforeach
                        @endif
                    </tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($empleados && $empleados->count() > 0)
                        @foreach ($empleados as $indice => $empleado)
                            <x-tr>
                                <x-th value="{{ $indice + 1 }}" class="text-center" />
                                <x-td value="{{ $empleado->cargo->nombre }}" class="font-xs" />
                                <x-td value="{{ $empleado->nombreCompleto }}"
                                    style="background-color:{{ $empleado->grupo ? $empleado->grupo->color : '#ffffff' }}" />
                                @if ($dias)
                                    @foreach ($dias as $dia)
                                        <x-td
                                            style="max-width:{{ $dia['es_dia_domingo'] ? '20px' : '50px' }};background-color:{{ $dia['es_dia_domingo'] ? '#FFC000' : '#ffffff' }}">
                                            @if (!$dia['es_dia_domingo'])
                                                <input type="text"
                                                    class="border border-1 border-gray text-center w-[50px]">
                                            @endif
                                        </x-td>
                                    @endforeach
                                @endif
                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="4">No hay Empleados registrados.</x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>
</div>
