<div class="w-full" x-data="{ search: '' }">
    <x-loading wire:loading wire:target="descargarBackup" />
    <x-loading wire:loading wire:target="cargarBackup" />
    <x-loading wire:loading wire:target="descargarBackupCompleto" />
    <x-loading wire:loading wire:target="cargarBackupCompleto" />


    <div class="w-full max-w-screen  text-center">

        <x-card>
            <x-spacing>
                <div class="md:flex items-center justify-between">
                    <!-- Botón para fecha anterior -->

                    <div>
                        <div class="md:flex items-center">
                            <x-secondary-button wire:click="fechaAnterior">
                                <i class="fa fa-chevron-left"></i> Fecha Anterior
                            </x-secondary-button>
                            <!-- Input para seleccionar la fecha -->
                            <x-input type="date" wire:model.live="fecha" class="text-center mx-2 !mt-0 !w-auto" />
                            <div class="relative w-auto">
                                <div
                                    class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-primary">
                                    <i class="fa fa-search mt-2"></i>
                                </div>
                                <x-input type="search" x-model="search" class="w-full !pl-10 !mt-0" autocomplete="off"
                                    placeholder="Busca por Nombres" />
                            </div>

                            <!-- Botón para fecha posterior -->
                            <x-secondary-button wire:click="fechaPosterior" class="ml-3">
                                Fecha Posterior <i class="fa fa-chevron-right"></i>
                            </x-secondary-button>
                        </div>

                    </div>
                    <div>



                        <div x-data="{ open: false }" class="">
                            <!-- Dropdown Button -->
                            <x-button @click="open = !open" class="flex items-center" type="button">
                                Opciones Adicionales
                                <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m1 1 4 4 4-4" />
                                </svg>
                            </x-button>
                            <!-- Dropdown Menu -->
                            <div x-show="open" @click.outside="open = false"
                                class="z-10 text-base relative mr-5 list-none border-1 border-gray-500 bg-white divide-y divide-gray-100 rounded-lg shadow-lg w-auto dark:bg-gray-700">
                                <div class="absolute bg-white shadow-lg">
                                    <ul class="py-2">

                                        <li>
                                            <a href="#" wire:click.prevent="descargarBackup" class="block px-4 py-2 hover:bg-bodydark1 hover:text-primary whitespace-nowrap">
                                                Descargar Backup {{ $fecha }}
                                            </a>
                                        </li>
                                        <li>
                                            <div x-data="{ openarchivoBackupHoyDialog() { $refs.archivoBackupHoyInput.click() } }">
                                                <!-- Botón para abrir el diálogo de archivos -->
                                                <a @click.prevent="openarchivoBackupHoyDialog()" href="#" class="block px-4 py-2 hover:bg-bodydark1 hover:text-primary  whitespace-nowrap">
                                                    Restaurar Backup {{ $fecha }}
                                                </a>
                                                <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" x-ref="archivoBackupHoyInput"
                                                    style="display: none;" wire:model.live="archivoBackupHoy" />
                                            </div>
                                        </li>
                                        <li>
                                            <a href="#" wire:click.prevent="descargarBackupCompleto" class="block px-4 py-2 hover:bg-bodydark1 hover:text-primary  whitespace-nowrap">
                                                Descargar Backup Completo
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </x-spacing>
        </x-card>

        @if ($consolidados && $consolidados->count() > 0)
            @foreach ($consolidados as $riego)
                <div x-show="search === '' || '{{ strtolower($riego->regador_nombre) }}'.includes(search.toLowerCase())"
                    class="mt-5 mb-3">
                    <div class="flex justify-between items-center mb-3 ">
                        <x-h3 class="text-left">REGADOR - {{ $riego->regador_nombre }}</x-h3>
                    </div>
                    <div class="text-left mb-5">
                        <p class="font-2xl">
                            Total Horas de Riego: <b>{{ $riego->total_horas_riego }}</b>
                        </p>
                        <p class="font-2xl">
                            Total Horas de Jornal: <b>{{ $riego->total_horas_jornal }}</b>
                            {{ $riego->horasAcumuladas != '00:00' ? ' (y se acumuló ' . $riego->horasAcumuladas . ')' : '' }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-5 mb-5">
                        <div class="col-span-2 md:col-span-1">
                            <livewire:detalle-horas-riego-component :regador="$riego->regador_documento" :fecha="$riego->fecha"
                                wire:key="horas_riego_{{ $riego->regador_documento }}_{{ $riego->fecha }}" />
                        </div>

                        <div class="col-span-2 md:col-span-1">
                            <livewire:detalle-observaciones-component :regador="$riego->regador_documento" :fecha="$riego->fecha"
                                wire:key="observaciones_{{ $riego->regador_documento }}_{{ $riego->fecha }}" />
                        </div>
                    </div>
                </div>
            @endforeach
        @endif


        <x-card class="w-full overflow-auto">
            <x-spacing>
                <div class="block md:flex justify-between items-start">

                    <div>
                        <div class="lg:flex lg:flex-wrap items-center gap-3">

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
                                            <option value="{{ $regador['documento'] }}">
                                                {{ $regador['nombre_completo'] }}
                                            </option>
                                        @endforeach
                                    </x-select>
                                </div>
                            @endif
                            <div class="my-4">
                                <x-secondary-button type="button" wire:click="agregarDetalle">
                                    Agregar Regador
                                </x-secondary-button>
                            </div>
                        </div>

                    </div>
                    <div>
                        @if ($estaConsolidado)
                            <x-button id="consolidar" wire:click="consolidar" disabled
                                class="!bg-opacity-60  whitespace-nowrap">
                                Fecha Consolidada
                            </x-button>
                        @else
                            <x-button id="consolidar" wire:click="consolidar" class=" whitespace-nowrap">
                                Consolidar Día
                            </x-button>
                        @endif
                    </div>
                </div>

            </x-spacing>
        </x-card>
    </div>
</div>
