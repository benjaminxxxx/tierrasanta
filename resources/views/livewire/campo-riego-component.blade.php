<div class="w-full">
    <div>
        <x-title>
            Estado de riego
        </x-title>
        <x-subtitle>
            Monitoreo y control de campos de riego
        </x-subtitle>
    </div>
    <x-card class="mt-4">
        <x-flex>
            <x-selector-dia label="Fecha" wire:model.live="fecha" id="fecha" />
            @if ($regadores)
                <x-select label="Encargado" class="uppercase" wire:model.live="regadorSeleccionado"
                    id="regadorSeleccionado">
                    <option value="">Seleccionar Regador</option>
                    @foreach ($regadores as $documento => $regador)
                        <option value="{{ $documento }}">{{ $regador }}
                        </option>
                    @endforeach
                </x-select>
            @endif
        </x-flex>
    </x-card>
    <x-card class="mt-4">
        @can(\App\Constants\Permisos::CAMPO_RIEGO_ESTADO_VER)
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                @foreach ($campos as $campo)
                    @php
                        $borderClass = '';
                        $puedeSeleccionarse = 'true';
                        $riegoInfo = $campo->seRegoEnFecha($fecha);

                        if ($riegoInfo['result']) {
                            $borderClass = 'border-blue-dotted-large';
                            $puedeSeleccionarse = 'false';

                            if ($campo->seEstaRegando()) {
                                $borderClass = 'border-blue-dotted-large-animated';
                            }
                        }
                    @endphp

                    <div data-nombre="{{ $campo->nombre }}" @if ($puedeSeleccionarse == 'false') x-data="{ showTooltip: false }"
                    @click="showTooltip = true" @click.away="showTooltip = false" @endif
                        class="relative bg-muted shadow-lg font-bold text-center flex items-center justify-center rounded-md p-3 text-muted-foreground {{ $borderClass }}">
                        <div class="campo-content">
                            {{ $campo->nombre }}
                            @if ($regadorSeleccionado != null && in_array($regadorSeleccionado, array_column($riegoInfo['riegos'], 'regadorDocumento')))
                                <i class="fa fa-user"></i>
                            @endif
                        </div>

                        @if ($puedeSeleccionarse == 'false')
                            <div x-show="showTooltip" x-transition
                                class="absolute z-10 p-2 bg-white border border-gray-300 shadow-lg rounded-md dark:bg-gray-800 dark:border-gray-700"
                                style="left: 50%; top: 100%; transform: translateX(-50%);">
                                <x-table>
                                    <x-slot name="thead">
                                    </x-slot>
                                    <x-slot name="tbody">
                                        @foreach ($riegoInfo['riegos'] as $riego)
                                            <x-tr>
                                                <x-th value="Regador:" />
                                                <x-td value="{{ $riego['nombreRegador'] }}" class="text-xs" />
                                            </x-tr>
                                            <x-tr>
                                                <x-th value="Hora Inicio:" class="whitespace-nowrap" />
                                                <x-td value="{{ $riego['hora_inicio'] }}" class="text-xs" />
                                            </x-tr>
                                            <x-tr>
                                                <x-th value="Hora Fin:" class="whitespace-nowrap" />
                                                <x-td value="{{ $riego['hora_fin'] }}" class="text-xs" />
                                            </x-tr>
                                        @endforeach
                                    </x-slot>
                                </x-table>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <x-danger>
                No tienes permisos para ver el estado de riego. Por favor, contacta al administrador.
            </x-danger>
        @endcan

    </x-card>

    <x-loading wire:loading />
</div>