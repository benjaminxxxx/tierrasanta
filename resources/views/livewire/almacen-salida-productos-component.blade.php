<div>
    <x-loading wire:loading />
    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            {{ $destino == 'combustible' ? 'Combustible' : 'Almacén' }}
        </x-h3>
        <x-button type="button"
            @click="$wire.dispatch('nuevoRegistro',{mes:{{ $mes }},anio:{{ $anio }}})"
            class="w-full md:w-auto ">
            <i class="fa fa-plus"></i> Nuevo Registro de Salida
        </x-button>
        @if ($destino == 'combustible')
            <x-button type="button" @click="$wire.dispatch('verStock',{tipo:'combustible'})" class="w-full md:w-auto ">
                <i class="fa fa-eye"></i> Ver Stock de Combustible
            </x-button>
        @else
            <x-button type="button" @click="$wire.dispatch('verStock')" class="w-full md:w-auto ">
                <i class="fa fa-eye"></i> Ver Stock de Productos
            </x-button>
        @endif

    </div>
    <x-card>
        <x-spacing>
            <div class="flex items-center justify-between">

                <x-secondary-button wire:click="mesAnterior">
                    <i class="fa fa-chevron-left"></i> Mes Anterior
                </x-secondary-button>

                <div class="hidden md:flex items-center gap-5">
                    <!-- Selección de mes -->
                    <div class="mx-2">
                        <x-select wire:model.live="mes" class="!mt-0  !w-auto">
                            <option value="01">Enero</option>
                            <option value="02">Febrero</option>
                            <option value="03">Marzo</option>
                            <option value="04">Abril</option>
                            <option value="05">Mayo</option>
                            <option value="06">Junio</option>
                            <option value="07">Julio</option>
                            <option value="08">Agosto</option>
                            <option value="09">Septiembre</option>
                            <option value="10">Octubre</option>
                            <option value="11">Noviembre</option>
                            <option value="12">Diciembre</option>
                        </x-select>
                    </div>

                    <!-- Selección de año -->
                    <div class="mx-2">
                        <x-input type="number" wire:model.live="anio" class="text-center !mt-0 !w-auto"
                            min="1900" />
                    </div>
                </div>


                <!-- Botón para mes posterior -->
                <x-secondary-button wire:click="mesSiguiente" class="ml-3">
                    Mes Siguiente <i class="fa fa-chevron-right"></i>
                </x-secondary-button>
            </div>
        </x-spacing>
    </x-card>
    <livewire:almacen-salida-detalle-component :tipo="$destino" wire:key="{{ $mes }}.{{ $anio }}"
        :mes="$mes" :anio="$anio" />
    @if ($destino == 'combustible')
        <x-card class="mt-5">
            <x-spacing>
                <x-h3>
                    Reportes
                </x-h3>
                <p>
                    Para generar correctamente el reporte asegúrate de seguir los siguientes pasos
                </p>
                <ul class="max-w-md space-y-1 text-gray-500 list-inside dark:text-gray-400 my-4">
                    <li class="flex items-center">
                        <svg class="w-3.5 h-3.5 me-2 text-green-500 dark:text-green-400 shrink-0" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
                        </svg>
                        Crear un Kardex para cada producto de tipo combustible.
                    </li>
                    <li class="flex items-center">
                        <svg class="w-3.5 h-3.5 me-2 text-green-500 dark:text-green-400 shrink-0" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
                        </svg>
                        Registrar las salidas y sus distribuciones.
                    </li>
                    <li class="flex items-center">
                        <svg class="w-3.5 h-3.5 me-2 text-green-500 dark:text-green-400 shrink-0" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
                        </svg>
                        Cada cierre de mes ir a Kardex y generar los precios por cada salida.
                    </li>
                    <li class="flex items-center">
                        <svg class="w-3.5 h-3.5 me-2 text-green-500 dark:text-green-400 shrink-0" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
                        </svg>
                        Clic en el botón calcular distribución negro/blanco.
                    </li>
                </ul>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5">
                    <div class="border border-1 border-gray-400 rounded-lg  p-3 md:p-7">
                        <p class="font-bold font-md mb-3">Distribución Blanco</p>
                        <x-flex class="border border-1 rounded-lg w-full justify-center gap-7">
                            <x-secondary-button type="button"
                                @click="$wire.dispatch('calcularDistribucion',{tipo:'blanco',mes:{{ $mes }},anio:{{ $anio }}})"
                                class="w-full">
                                <i class="fa-solid fa-calculator"></i> Calcular
                            </x-secondary-button>
                            @if ($reporteMensualCombustible && $reporteMensualCombustible->file_blanco)
                                <x-button-a
                                    href="{{ Storage::disk('public')->url($reporteMensualCombustible->file_blanco) }}"
                                    class="w-full text-center">
                                    <i class="fa fa-download"></i> Descargar Excel
                                </x-button-a>
                            @endif
                        </x-flex>
                    </div>
                    <div class="border border-1 border-gray-400 rounded-lg  p-3 md:p-7">
                        <p class="font-bold font-md mb-3">Distribución Negro</p>
                        <x-flex class="border border-1 rounded-lg w-full justify-center gap-7">
                            <x-secondary-button type="button"
                                @click="$wire.dispatch('calcularDistribucion',{tipo:'negro',mes:{{ $mes }},anio:{{ $anio }}})"
                                class="w-full">
                                <i class="fa-solid fa-calculator"></i> Calcular
                            </x-secondary-button>
                            @if ($reporteMensualCombustible && $reporteMensualCombustible->file_negro)
                                <x-button-a
                                    href="{{ Storage::disk('public')->url($reporteMensualCombustible->file_negro) }}"
                                    class="w-full text-center">
                                    <i class="fa fa-download"></i> Descargar Excel
                                </x-button-a>
                            @endif
                        </x-flex>
                    </div>
                </div>
            </x-spacing>
        </x-card>
    @endif
</div>
