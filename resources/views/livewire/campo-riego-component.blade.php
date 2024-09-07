<div class="w-full">
    <style>
        @media(max-width:800px) {
            #campos-container {
                width: 100%;
            }

            #campos-container>div {
                left: inherit !important;
                top: inherit !important;
                width: 100%;
                position: relative;
                margin-top: 10px !important;
                display: block;
                margin: 0 auto;
            }
        }
    </style>
    <div class="w-full max-w-screen  text-center">
        <x-card class="max-w-4xl m-auto overflow-auto">
            <x-spacing>
                <div class="lg:flex lg:flex-wrap items-center gap-3">
                    <div class="my-4">
                        <x-label for="fecha" class="text-left">Fecha</x-label>
                        <x-input type="date" class="!lg:w-auto" wire:model.live="fecha" id="fecha" />
                    </div>
                    <div class="my-4">
                        <x-label for="fecha" class="text-left">Tipo de Personal</x-label>
                        <x-select class="uppercase !lg:w-auto pr-10 max-w-full lg:max-w-xs"
                            wire:model.live="tipoPersonal" id="tipoPersonal">
                            <option value="regadores">Regadores</option>
                            <option value="empleados">Empleados</option>
                            <option value="cuadrilleros">Cuadrilleros</option>
                        </x-select>
                    </div>
                    @if ($regadores)
                        <div class="my-4">
                            <x-label for="regador" class="text-left">Encargado</x-label>
                            <x-select class="uppercase !lg:w-auto pr-10 max-w-full lg:max-w-xs"
                                wire:model.live="regadorSeleccionado" id="regadorSeleccionado">
                                <option value="">Seleccionar Regador</option>
                                @foreach ($regadores as $regador)
                                    <option value="{{ $regador['documento'] }}">{{ $regador['nombre_completo'] }}
                                    </option>
                                @endforeach
                            </x-select>
                        </div>
                    @endif
                    <div class="my-4">


                        @if ($regadorSeleccionado && $fecha)
                            <x-secondary-button id="ver-riego-btn" wire:click="verRiego" class="whitespace-nowrap">
                                Ver o Asignar Riegos
                            </x-secondary-button>
                            <x-secondary-button id="ver-observaciones-btn" wire:click="verObservaciones"
                                class="whitespace-nowrap">
                                Ver o Asignar Observaciones
                            </x-secondary-button>
                        @endif
                        @if ($estaConsolidado)
                        <x-button id="consolidar" wire:click="consolidar" disabled class="!bg-opacity-60  whitespace-nowrap">
                            Fecha Consolidada
                        </x-button>
                        @else
                        <x-button id="consolidar" wire:click="consolidar"  class=" whitespace-nowrap">
                            Consolidar Día
                        </x-button>
                        @endif

                    </div>
                </div>


                <div class="relative" id="campos-container">
                    @foreach ($campos as $campo)
                        @php
                            $borderClass = '';
                            $regadorDocumento = null;
                            $puedeSeleccionarse = 'true';

                            $riegoInfo = $campo->seRegoEnFecha($fecha);

                            if ($riegoInfo['result']) {
                                $borderClass = 'border-blue-dotted-large'; // Borde punteado grande
                                $puedeSeleccionarse = 'false';
                                $regadorDocumento = $riegoInfo['regadorDocumento']; // Documento del regador

                                if ($campo->seEstaRegando()) {
                                    $borderClass = 'border-blue-dotted-large-animated'; // Borde punteado animado
                                }
                            }
                        @endphp
                        <div data-nombre="{{ $campo->nombre }}"
                            @if ($puedeSeleccionarse == 'true') wire:click="seleccionarCampo('{{ $campo->nombre }}', {{ $puedeSeleccionarse }})"
                            @else
                                x-data="{ showTooltip: false }"
                                @click="showTooltip = true"
                                @click.away="showTooltip = false" @endif
                            class="campo {{ array_search($campo->nombre, array_column($camposSeleccionados, 'nombre')) !== false ? 'selectedCampo' : ($campo->orden == 1 ? 'bg-lime-600 text-white' : 'bg-stone-300') }} break-work shadow-lg font-bold text-center flex items-center justify-center rounded-md p-3 {{ $borderClass }}"
                            style="left: {{ $campo->pos_x }}px; top: {{ $campo->pos_y }}px; }}">


                            <div class="campo-content">
                                {{ $campo->nombre }}
                                @if ($regadorSeleccionado != null)
                                    @if ($regadorDocumento == $regadorSeleccionado)
                                        <i class="fa fa-user"></i>
                                    @endif
                                @endif
                            </div>

                            @if ($puedeSeleccionarse == 'false')
                                <!-- Tooltip -->
                                <div x-show="showTooltip" x-transition
                                    class="absolute z-10 p-2 bg-white border border-gray-300 shadow-lg rounded-md"
                                    style="left: 50%; top: 100%; transform: translateX(-50%);">
                                    <x-table class="">
                                        <x-slot name="thead">
                                        </x-slot>
                                        <x-slot name="tbody">
                                            <x-tr>
                                                <x-th value="Regador:" class="" />
                                                <x-td value="{{ $riegoInfo['nombreRegador'] }}" class="font-xs" />
                                            </x-tr>
                                            <x-tr>
                                                <x-th value="Hora Inicio:" class="whitespace-nowrap" />
                                                <x-td value="{{ $riegoInfo['hora_inicio'] }}" class="font-xs" />
                                            </x-tr>
                                            <x-tr>
                                                <x-th value="Hora Fin:" class="whitespace-nowrap" />
                                                <x-td value="{{ $riegoInfo['hora_fin'] }}" class="font-xs" />
                                            </x-tr>
                                        </x-slot>
                                    </x-table>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

            </x-spacing>
        </x-card>
    </div>
</div>
