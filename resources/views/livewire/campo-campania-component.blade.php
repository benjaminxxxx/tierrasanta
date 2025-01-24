<div>
    <x-loading wire:loading />

    <x-h3 class="mb-4">
        Camapañas por Campo
    </x-h3>
    <x-card>
        <x-spacing>
            <x-flex class="!items-end">
                <div>
                    <x-label class="mb-2">
                        Seleccionar el Campo
                    </x-label>
                    <x-select wire:model.live="campoSeleccionado">
                        <option value="">Seleccione un Campo</option>
                        @foreach ($campos as $campo)
                            <option value="{{ $campo->nombre }}">{{ $campo->nombre }}</option>
                        @endforeach
                    </x-select>
                </div>
                @if ($campoSeleccionado)
                    <x-button @click="$wire.dispatch('registroCampania',{campoNombre:'{{ $campoSeleccionado }}'})">
                        <i class="fa fa-plus"></i> Crear campaña
                    </x-button>
                @endif

            </x-flex>
        </x-spacing>
    </x-card>
    @if ($campanias)
        <x-card class="mt-4">
            <x-spacing>
                @if ($campanias->count() > 0)
                    <ol class="relative border-s border-gray-200 dark:border-gray-700">
                        @foreach ($campanias as $campania)
                            <li class="mb-10 ms-6">
                                <span
                                    class="absolute flex items-center justify-center w-6 h-6 bg-blue-100 rounded-full -start-3 ring-8 ring-white dark:ring-gray-900 dark:bg-blue-900">
                                    <i class="fas fa-calendar-minus"></i>
                                </span>
                                <div class="md:flex justify-between">
                                    <div>
                                        <h3
                                            class="flex items-center mb-1 text-lg font-semibold text-gray-900 dark:text-white">
                                            Campaña {{ $campania->nombre_campania }}
                                        </h3>
                                        <time
                                            class="block mb-2 text-sm font-normal leading-none text-gray-400 dark:text-gray-500">Vigencia
                                            desde
                                            {{ $campania->fecha_vigencia }}</time>
                                        <ul class="text-base font-normal text-gray-500 dark:text-gray-400">
                                            <li>FECHA DE INICIO: {{ $campania->fecha_inicio }}</li>
                                            <li>FECHA DE FINALIZACIÓN: {{ $campania->fecha_fin }}</li>
                                            <li>GASTO PLANILLA: {{ $campania->gasto_planilla }}</li>
                                            <li>GASTO CUADRILLA: {{ $campania->gasto_cuadrilla }}</li>
                                            @foreach ($campania->listaConsumo as $consumo)
                                            <li>CONSUMOS {{$consumo['categoria']}}: {{$consumo['monto']}}</li>
                                            @endforeach
                                        </ul>
                                        <x-button type="button" wire:click="actualizarGastosConsumo({{$campania->id}})" class="mt-4">
                                            <i class="fa fa-refresh"></i> Actualizar Gastos y Consumos
                                        </x-button>
                                    </div>
                                    <div class="mt-3 md:mt-0">
                                        <x-danger-button wire:click="eliminarCampania({{ $campania->id }})">
                                            Eliminar Campaña
                                        </x-danger-button>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <x-warning>
                        <p>No hay ninguna campaña registrada para este campo.</p>
                    </x-warning>
                @endif
            </x-spacing>
        </x-card>
    @endif
</div>
