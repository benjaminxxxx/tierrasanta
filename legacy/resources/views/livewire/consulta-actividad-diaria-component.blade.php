<div>
    <x-loading wire:loading />
    <x-card>
        <x-spacing>
            <x-h3>
                Calendario de actividades
            </x-h3>
            <p class="text-base text-gray-900 dark:text-white mt-3">
                Busque las actividades como mallita o vaciado de infestadores para poder encontrar la fecha exacta de
                dicha actividad.

            </p>
            @if ($campania)
                <p class="text-base text-gray-900 dark:text-white mb-3">
                    Mostrando únicamente las actividades registradas desde el
                    <strong>{{ $campania->fecha_inicio }}</strong>
                    @if ($campania->fecha_fin)
                        hasta el <strong>{{ $campania->fecha_fin }}</strong>,
                    @else
                        hasta la fecha actual,
                    @endif
                    según el periodo de la campaña seleccionada.
                </p>
            @endif

            <form wire:submit="buscar">
                <x-flex>
                    <x-group-field>
                        <x-input-string wire:model="campo" label="Buscar por campo" />
                    </x-group-field>
                    <x-group-field>
                        <x-input-number wire:model="codigo" label="Buscar por código" />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string wire:model="descripcion" label="Buscar por descripción" />
                    </x-group-field>
                    <x-group-field>
                        <x-button type="submit">
                            <i class="fa fa-search"></i> Buscar
                        </x-button>
                    </x-group-field>
                </x-flex>
            </form>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">
                            N°
                        </x-th>
                        <x-th class="text-center">
                            Campo
                        </x-th>
                        <x-th class="text-center">
                            Fecha
                        </x-th>
                        <x-th class="text-center">
                            Código labor
                        </x-th>
                        <x-th>
                            Descripción labor
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($listaActividades as $indice => $listaActividad)
                        <x-tr>
                            <x-td class="text-center">
                                {{ $indice + 1 }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $listaActividad->campo }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $listaActividad->fecha }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $listaActividad->labor }}
                            </x-td>
                            <x-td>
                                {{ $listaActividad->nombre_labor }}
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-slot>

            </x-table>
        </x-spacing>
    </x-card>
</div>
