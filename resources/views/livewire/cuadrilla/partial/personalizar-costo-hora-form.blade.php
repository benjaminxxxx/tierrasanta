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
                            <x-th class="text-center" rowspan="2">
                                Grupo
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
                            @foreach ($cuadrillerosCostosPersonalizados as $claveCuadrilla => $cuadrillero)
                                <x-tr wire:key="tr-{{ $claveCuadrilla }}">
                                    <x-td class="text-center">
                                        {{-- 1. Se usa $loop->iteration en lugar de $indice + 1 --}}
                                        {{ $loop->iteration }}
                                    </x-td>
                                    <x-td>
                                        {{ $cuadrillero['grupo_codigo'] ?? '-' }}
                                    </x-td>
                                    <x-td>
                                        {{ $cuadrillero['cuadrillero_nombres'] ?? '-' }}
                                    </x-td>
                                    @foreach ($cuadrillero['costos'] as $idxDia => $costo)
                                        <x-td class="text-center">
                                            {{-- 2. Se actualiza wire:key y wire:model usando $claveCuadrilla --}}
                                            <x-input class="!p-2 text-center" 
                                                wire:key="costo-{{ $claveCuadrilla }}-{{ $idxDia }}"
                                                wire:model="cuadrillerosCostosPersonalizados.{{ $claveCuadrilla }}.costos.{{ $idxDia }}"
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
        
    </x-slot>

    <x-slot name="footer">
        <x-flex class="flex-end">
            <x-button wire:click="$set('mostrarFormularioCostoHora', false)" variant="secondary" wire:loading.attr="disabled">
                Cerrar
            </x-button>
            <x-button wire:click="registrarCostoPersonalizado" wire:loading.attr="disabled">
                <i class="fa fa-save"></i> Registrar costo
            </x-button>
        </x-flex>
    </x-slot>
</x-dialog-modal>