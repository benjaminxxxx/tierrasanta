<div x-data="{ search: '' }">
    <x-loading wire:loading />
    <x-card>
        <x-spacing>
            <div class="md:flex items-center justify-between">

                <div>
                    <div class="md:flex items-center">
                        <x-secondary-button wire:click="fechaAnterior" class="my-2 lg:my-0 w-full lg:w-auto">
                            <i class="fa fa-chevron-left"></i> Fecha Anterior
                        </x-secondary-button>
                        <x-input type="date" wire:model.live="fecha" class="text-center lg:mx-2 my-2 lg:my-0 w-full lg:w-auto" />
                        <div class="relative w-auto my-2 lg:my-0">
                            <div
                                class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-primary">
                                <i class="fa fa-search"></i>
                            </div>
                            <x-input type="search" x-model="search" class="w-full !pl-10 !mt-0" autocomplete="off"
                                placeholder="Busca por Nombres" />
                        </div>

                        <!-- Botón para fecha posterior -->
                        <x-secondary-button wire:click="fechaPosterior" class="w-full lg:w-auto my-3 lg:my-0 lg:ml-3">
                            Fecha Posterior <i class="fa fa-chevron-right"></i>
                        </x-secondary-button>
                    </div>

                </div>
                <div>
                    <div class="lg:flex items-center gap-4">
                        <x-button @click="$wire.dispatch('guardarTodo')" class="w-full lg:auto">Guardar Todo</x-button>
                        <div x-data="{ open: false }" class="my-4 lg:my-0">
                            <!-- Dropdown Button -->
                            <x-secondary-button @click="open = !open" class="flex items-center justify-center w-full lg:w-auto whitespace-nowrap" type="button">
                                Opciones Adicionales
                                <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m1 1 4 4 4-4" />
                                </svg>
                            </x-secondary-button>
                            <!-- Dropdown Menu -->
                            <div x-show="open" @click.outside="open = false"
                                class="z-10 text-base relative mr-5 list-none border-1 border-gray-500 bg-white divide-y divide-gray-100 rounded-lg shadow-lg w-auto dark:bg-gray-700">
                                <div class="absolute bg-white shadow-lg">
                                    <ul class="py-2">
    
                                        <li>
                                            <a href="#" wire:click.prevent="descargarBackup"
                                                class="block px-4 py-2 hover:bg-bodydark1 hover:text-primary whitespace-nowrap">
                                                Descargar Backup {{ $fecha }}
                                            </a>
                                        </li>
                                        <li>
                                            <livewire:reporte-diario-riego-import-export-component :fecha="$fecha"/>
                                        </li>
                                        <li>
                                            <a href="#" wire:click.prevent="descargarBackupCompleto"
                                                class="block px-4 py-2 hover:bg-bodydark1 hover:text-primary  whitespace-nowrap">
                                                Descargar Backup Completo
                                            </a>
                                        </li>
                                    </ul>
                                </div>
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
                

                <div class="mb-5">
                    <livewire:reporte-diario-riego-detalle-component :regador="$riego->regador_documento" :fecha="$riego->fecha"
                        wire:key="horas_riego_{{ $riego->regador_documento }}_{{ $riego->fecha }}" />

                </div>
            </div>
        @endforeach
    @endif

    <x-card class="w-full overflow-auto">
        <x-spacing>
            <div class="block md:flex justify-between items-start">

                <div>
                    <div class="lg:flex lg:flex-wrap items-end gap-3">

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
                            <x-secondary-button type="button" wire:click="agregarDetalle" wire:loading.attr="disabled">
                                Agregar Regador
                            </x-secondary-button>
                        </div>
                    </div>

                </div>
            </div>

        </x-spacing>
    </x-card>

</div>
@script
<script>
    document.addEventListener('delay-riegos', function () {
        setTimeout(function () {
            location.href = location.href;
        }, 1000); // 2000 milisegundos (2 segundos) de retraso
    });
</script>
@endscript
