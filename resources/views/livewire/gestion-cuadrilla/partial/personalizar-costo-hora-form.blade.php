<x-dialog-modal wire:model.live="mostrarFormularioCostoHora" maxWidth="full">
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
                                Cuadrillero
                            </x-th>
                            @if ($diasSemana)
                                @foreach ($diasSemana as $diaSemana)
                                    <x-th class="text-center">{{ \Carbon\Carbon::parse($diaSemana)->format('d') }}</x-th>
                                @endforeach
                            @endif
                        </x-tr>
                        <x-tr>
                            @if ($diasSemana)
                                @foreach ($diasSemana as $diaSemana)
                                    <x-th class="text-center">
                                        {{ \Carbon\Carbon::parse($diaSemana)->isoFormat('ddd') }}
                                    </x-th>
                                @endforeach
                            @endif
                        </x-tr>
                    </x-slot>

                    <x-slot name="tbody">
                        @if ($cuadrillerosCostosPersonalizados)
                            @foreach ($cuadrillerosCostosPersonalizados as $indice => $cuadrillero)
                                <x-tr>
                                    <x-td class="text-center">
                                        {{ $indice + 1 }}
                                    </x-td>
                                    <x-td>
                                        {{ $cuadrillero['cuadrillero_nombres'] ?? '-' }}
                                    </x-td>
                                    @foreach ($cuadrillero['costos'] as $indice => $costo)
                                        @php
                                            $id = $cuadrillero['cuadrillero_id'];
                                        @endphp
                                        <x-td class="text-center">
                                            <x-input class="!p-2 text-center" wire:key="costo-{{ $indice }}-{{ $id }}"
                                                wire:model="cuadrillerosCostosPersonalizados.{{ $id }}.costos.{{ $indice }}"
                                                type="number" step="0.01" />
                                        </x-td>
                                    @endforeach
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
                    <p>Modifique directamente los precios, el sistema te permitirá 1 segundo para que puedas digitar
                        y luego lo guardará de forma automática.</p>
                </li>
                <li>
                    <p>
                        Si deseas quitar el precio personalizado, simplemente bórralo, el sistema borrará el
                        registro y usará el precio semanal o si existe, el precio personalizado por día.
                    </p>
                </li>
            </ul>
        </div>
    </x-slot>

    <x-slot name="footer">
        <x-flex class="flex-end">
            <x-secondary-button wire:click="$set('mostrarFormularioCostoHora', false)" wire:loading.attr="disabled">
                Cerrar
            </x-secondary-button>
            <x-button wire:click="registrarCostoPersonalizado" wire:loading.attr="disabled">
                <i class="fa fa-save"></i> Registrar costo
            </x-button>
        </x-flex>
    </x-slot>
</x-dialog-modal>