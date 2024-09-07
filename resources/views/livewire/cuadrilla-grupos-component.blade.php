<div>
    <x-card>
        <x-spacing>
            <div class="block md:flex items-center gap-5">
                <x-h2>
                    Grupos de Cuadrillas
                </x-h2>
            </div>
            <x-table class="mt-5">
                <x-slot name="thead">
                    <x-tr>
                        <x-th value="N°" class="text-center" />
                        <x-th value="Código del Grupo" />
                        <x-th value="Nombre del Grupo" />
                        <x-th value="Precio Sugerido por Jornal" class="text-center" />
                        <x-th value="Color" class="text-center" />
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($grupos && $grupos->count() > 0)
                        @foreach ($grupos as $indice => $grupo)
                            <x-tr>
                                <x-th value="{{ $indice + 1 }}" class="text-center" />
                                <x-td>
                                    <div class="rounded-lg p-2 text-center"
                                        style="background-color:{{ $grupo->color ? $grupo->color : '#ffffff' }}">
                                        {{ $grupo->codigo }}
                                    </div>
                                </x-td>
                                <x-td value="{{ $grupo->nombre }}" />
                                <x-td value="{{ $grupo->costo_dia_sugerido }}" class="text-center" />
                                <x-td>
                                    <div class="rounded-lg p-2 text-center"
                                        style="background-color:{{ $grupo->color ? $grupo->color : '#ffffff' }}">
                                        {{ $grupo->color }}
                                    </div>
                                </x-td>
                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="4">No hay Grupos registrados.</x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>
</div>
