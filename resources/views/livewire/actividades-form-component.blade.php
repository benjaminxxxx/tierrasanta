<div>

    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Formulario para agregar actividades en la fecha indicada
        </x-slot>

        <x-slot name="content">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 ">
                <x-group-field>
                    <x-input-date type="date" label="Fecha" wire:model.live="fecha" class="uppercase !bg-gray-100 cursor-not-allowed"
                        disabled error="fecha" />
                </x-group-field>
                @if ($fecha)
                    <x-group-field>
                        <x-select type="number" wire:model.live="laborSeleccionada" label="Labor seleccionada" error="laborSeleccionada">
                            <option value="">Seleccionar Labor</option>
                            @if ($labores)
                                @foreach ($labores as $labor)
                                    <option value="{{ $labor->id }}">
                                        {{ mb_strtoupper($labor->nombre_labor) }}
                                    </option>
                                @endforeach
                            @endif
                        </x-select>
                    </x-group-field>
                @endif
                <x-group-field>
                    <x-select type="number" wire:model.live="campoSeleccionado" label="Campo" error="campoSeleccionado">
                        @foreach ($campos as $campo)
                            <option value="{{ $campo->nombre }}">{{ mb_strtoupper($campo->nombre) }}
                            </option>
                        @endforeach
                    </x-select>
                </x-group-field>
                <x-group-field>
                   <x-input-number wire:model="horas_trabajadas" label="Horas de labor" error="horas_trabajadas"/>
                </x-group-field>
            </div>

            @if ($laborSeleccionada && !$valoracion)
                <x-warning class="my-3">
                    <p>Esta labor no está configurada para calcular Bonos, vaya a <a
                            href="{{ route('configuracion.labores') }}" class="text-blue-600 font-bold">Labores</a>
                        para asignar alguna valoración y poder calcular el monto del bono.</p>
                </x-warning>
            @elseif ($laborSeleccionada && $valoracion)
                <div>
                    <ul>
                        <li>
                            <p>Cantidad promedio en 8 horas: <b>{{ $valoracion->kg_8 }}</b></p>
                        </li>
                        <li>
                            <p>Cantidad promedio por hora: <b>{{ $valoracion->kg_8 / 8 }}</b></p>
                        </li>
                        <li>
                            <p>Valor por hora Adicional: <b>{{ $valoracion->valor_kg_adicional }}</b></p>
                        </li>
                        <li>
                            <p>Estos datos están vigentes desde: <b>{{ $valoracion->vigencia_desde }}</b></p>
                        </li>
                    </ul>
                </div>
                <div class="mb-3 col-span-2">
                    <x-label value="Actividades" />
                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th class="text-center">
                                    N°
                                </x-th>
                                <x-th class="text-center">
                                    Horas trabajadas
                                </x-th>
                                <x-th class="text-center">
                                    KG
                                </x-th>
                                <x-th class="text-center">

                                </x-th>
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @foreach ($actividades as $indice => $actividad)
                                <x-tr>
                                    <x-td>
                                        Recogida {{ $indice + 1 }}
                                    </x-td>
                                    <x-td class="text-center">
                                        <x-input type="number"
                                            wire:model.live="actividades.{{ $indice }}.horas" />
                                    </x-td>
                                    <x-td class="text-center">
                                        <x-input type="number" class="!bg-gray-100"
                                            wire:model.live="actividades.{{ $indice }}.kg" />
                                    </x-td>
                                    <x-td class="text-center">
                                        <x-danger-button type="button"
                                            wire:click="quitarActividad({{ $indice }})">
                                            <i class="fa fa-trash"></i>
                                        </x-danger-button>
                                    </x-td>
                                </x-tr>
                            @endforeach
                        </x-slot>
                    </x-table>
                    <x-flex class="justify-end">
                        <x-secondary-button type="button" wire:click="agregarActividad" class="my-4">
                            <i class="fa fa-plus"></i> Agregar actividad
                        </x-secondary-button>
                    </x-flex>
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-flex class="justify-end w-full">
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button wire:click="registrarLaborCuadrilla" wire:loading.attr="disabled">

                    @if ($actividadId)
                        <i class="fa fa-pencil"></i> Actualizar
                    @else
                        <i class="fa fa-save"></i> Registrar
                    @endif
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>
