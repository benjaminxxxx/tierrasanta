<div>
    <x-card>
        <x-spacing>
            <div class="block md:flex items-center gap-5">
                <x-h2>
                    Cuadrilleros
                </x-h2>

                <livewire:cuadrilla-cuadrillero-import-export-component wire:key="element" />
            </div>
            <x-table class="mt-5">
                <x-slot name="thead">
                    <x-tr>
                        <x-th value="N°" class="text-center" />
                        <x-th value="Nombres" />
                        <x-th value="Grupo" class="text-center" />
                        <x-th value="Dni" class="text-center" />
                        <x-th value="Identificador" class="text-center" />
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($cuadrilleros && $cuadrilleros->count() > 0)
                        @foreach ($cuadrilleros as $indice => $cuadrillero)
                            <x-tr>
                                <x-th value="{{ $indice + 1 }}" class="text-center" />
                                <x-td value="{{ $cuadrillero->nombre_completo }}" />
                                <x-td>
                                    <div class="rounded-lg p-2 text-center"
                                        style="background-color:{{ $cuadrillero->grupoCuadrilla->color ? $cuadrillero->grupoCuadrilla->color : '#ffffff' }}">
                                        {{ $cuadrillero->codigo_grupo }}
                                    </div>
                                </x-td>
                                <x-td value="{{ $cuadrillero->dni }}" class="text-center" />
                                <x-td value="{{ $cuadrillero->codigo }}" class="text-center" />
                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="4">No hay Cuadrilleros registrados.</x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>
</div>
