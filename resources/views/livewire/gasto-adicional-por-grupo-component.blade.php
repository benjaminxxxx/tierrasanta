<div>
    <x-loading wire:loading />
    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
            @if ($grupo)
                Registro de Gastos Adicionales para el grupo "{{ $grupo->grupo->nombre }}"
            @endif

        </x-slot>

        <x-slot name="content">
            <form wire:submit="storeRegistrarGastoAdicional">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input-string type="text" label="Descripción" wire:model="descripcion" error="descripcion" />
                    <x-input-number type="number" label="Monto" step="0.01" wire:model="monto" error="monto" />
                    <x-input-date label="Fecha de gasto" wire:model="fechaGasto" error="fechaGasto" />
                    <x-select wire:model="mesContable" label="Mes Contable" error="mesContable">
                        <option value="">Seleccione un mes</option>
                        @foreach (['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $index => $mes)
                            <option value="{{ $index + 1 }}">{{ $mes }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model="anioContable" label="Año contable" wire:key="anioContable{{ $anioContable }}"
                        error="anioContable">
                        <option value="">Seleccione un año</option>
                        @if ($aniosContablesPermitidos && is_array($aniosContablesPermitidos))
                            @foreach ($aniosContablesPermitidos as $anioSeleccionado)
                                <option value="{{ $anioSeleccionado }}">{{ $anioSeleccionado }}</option>
                            @endforeach
                        @endif
                    </x-select>
                </div>
                <x-flex class="justify-end w-full">
                    <x-button type="submit">
                        <i class="fa fa-save"></i> Registrar
                    </x-button>
                </x-flex>
            </form>
            <div>
                <x-h3>
                    Lista de gastos adicionales
                </x-h3>
            </div>
            <x-table class="mt-5">
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">
                            N°
                        </x-th>
                        <x-th>
                            Descripción
                        </x-th>
                        <x-th class="text-right">
                            Monto
                        </x-th>
                        <x-th class="text-center">
                            Fecha de Gasto
                        </x-th>
                        <x-th class="text-center">
                            Fecha Contable
                        </x-th>
                        <x-th class="text-center">
                            Acciones
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($gastos && $gastos->count() > 0)
                        @foreach ($gastos as $indice => $gasto)
                            <x-tr>
                                <x-td class="text-center">
                                    {{ $indice + 1 }}
                                </x-td>
                                <x-td>
                                    {{ $gasto->descripcion }}
                                </x-td>
                                <x-td class="text-right">
                                    {{ $gasto->monto }}
                                </x-td>
                                <x-td class="text-center">
                                    {{ formatear_fecha($gasto->fecha_gasto) }}
                                </x-td>
                                <x-td class="text-center">
                                    {{ $gasto->fechaContable }}
                                </x-td>
                                <x-td class="text-center">
                                    <x-danger-button wire:click="eliminarGasto({{ $gasto->id }})">
                                        <i class="fa fa-trash"></i> Eliminar gasto
                                    </x-danger-button>
                                </x-td>
                            </x-tr>
                        @endforeach
                    @else
                        <p>Aún no hay gastos adicionales.</p>
                    @endif
                </x-slot>
            </x-table>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-2">
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
