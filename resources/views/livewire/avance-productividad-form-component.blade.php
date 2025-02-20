<div>

    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Registro de Avance de Productividad
        </x-slot>

        <x-slot name="content">
            <div <div x-data="{
                kg8: @entangle('kg8'),
                actividades: @entangle('actividades'),
                actualizarKg() {
                    this.actividades.forEach((actividad, index) => {
                        if (actividad.horas) {
                            this.actividades[index].kg = Math.max(0, actividad.horas * (this.kg8 / 8));
                        }
                    });
                }
            }"
            x-init="$watch('kg8', value => actualizarKg())"
        >
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="mb-3">
                        <x-label for="fecha">Fecha</x-label>
                        <x-input type="date" wire:model.live="fecha" class="uppercase" />
                        <x-input-error for="fecha" />
                    </div>
                    @if ($fecha)
                        <div class="mb-3">
                            <x-label for="laborSeleccionada" value="Labor" />
                            <x-select type="number" wire:model.live="laborSeleccionada">
                                @if ($labores)
                                    @foreach ($labores as $labor)
                                        <option value="{{ $labor->id }}">{{ mb_strtoupper($labor->nombre_labor) }}
                                        </option>
                                    @endforeach
                                @endif
                            </x-select>
                            <x-input-error for="laborSeleccionada" />
                        </div>
                    @endif
                        <div class="mb-3">
                            <x-label for="campoSeleccionado" value="Campo" />
                            <x-select type="number" wire:model.live="campoSeleccionado">
                                @foreach ($campos as $campo)
                                    <option value="{{ $campo->nombre }}">{{ mb_strtoupper($campo->nombre) }}
                                    </option>
                                @endforeach
                            </x-select>
                            <x-input-error for="campo" />
                        </div>
                        <div class="mb-3">
                            <x-label for="kg8" value="Cantidad promedio en 8 horas" />
                            <x-input type="number" x-model="kg8" wire:model="kg8" />
                            <x-input-error for="kg8" />
                        </div>
                        <div class="mb-3">
                            <x-label for="valorKgAdicional" value="Valor por hora adicional" />
                            <x-input type="number" wire:model="valorKgAdicional" />
                            <x-input-error for="valorKgAdicional" />
                        </div>
                </div>
                @if (!$valoracion)
                    <x-warning class="my-3">
                        <p>Esta labor no tiene ninguna valoración registrada o no tiene fecha de vigencia dentro de
                            este
                            rango, vaya a <a href="{{ route('configuracion.labores') }}"
                                class="text-blue-600 font-bold">Labores</a>
                            para asignar alguna valoración y poder calcular elmonto del bono.</p>
                    </x-warning>
                @endif
                @if($actividades)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="mb-3 col-span-2">
                            <x-label for="laborSeleccionada" value="Actividades" />
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
                                                <x-input type="number" x-model="actividades[{{ $indice }}].horas" wire:model="actividades[{{ $indice }}].horas"
                                                    @input="actividades[{{ $indice }}].kg = Math.max(0, actividades[{{ $indice }}].horas * (kg8 / 8))"
                                                    />
                                            </x-td>
                                            <x-td class="text-center">
                                                <x-input type="number" class="!bg-gray-100"
                                                    x-model="actividades[{{ $indice }}].kg" readonly />
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
                    </div>
                @endif
            </div>

        </x-slot>
        <x-slot name="footer">
            <x-flex>
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                @if ($valoracion)
                    <x-button wire:click="registrarAvance" wire:loading.attr="disabled">

                        @if ($registroId)
                            <i class="fa fa-pencil"></i> Actualizar
                        @else
                            <i class="fa fa-save"></i> Registrar
                        @endif
                    </x-button>
                @endif

            </x-flex>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>
