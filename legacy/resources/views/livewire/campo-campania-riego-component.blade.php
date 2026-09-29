<div>
    <x-loading wire:loading />
    @if (!$campaniaUnica)
        <x-flex>
            <x-h3>
                Riego
            </x-h3>
        </x-flex>
    @endif

    <x-card>
        <x-spacing>
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
                            Horario
                        </x-th>
                        <x-th class="text-center">
                            Total de Horas
                        </x-th>
                        <x-th class="text-left">
                            Regador
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($riegos as $indice => $riego)
                        <x-tr>
                            <x-td class="text-center">
                                {{ $indice + 1 }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $riego->campo }}
                            </x-td>
                            <x-td class="text-center">
                                {{ formatear_fecha($riego->fecha) }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $riego->hora_inicio }} - {{ $riego->hora_fin }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $riego->total_horas }}
                            </x-td>
                            <x-td class="text-left">
                                {{ $riego->regador }}
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
            <div class="mt-5">
                {{ $riegos->links() }}
            </div>
        </x-spacing>
    </x-card>
</div>
