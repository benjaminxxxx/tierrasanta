<div>
    <x-loading wire:loading />
    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="complete">
        <x-slot name="title">
            Distribución de combustible
        </x-slot>

        <x-slot name="content">
            <x-flex class="w-full gap-4 !items-start">
                <div class="flex-1">
                    <x-table class="w-full">
                        <x-slot name="thead">
                            <x-tr>
                                <x-th>FECHA</x-th>
                                <x-th>HORA INICIO</x-th>
                                <x-th>HORA SALIDA</x-th>
                                <x-th>TOTAL DE HORAS</x-th>
                                <x-th>CAMPO</x-th>

                                <x-th>CANTIDAD COMBUSTIBLE</x-th>
                                <x-th>COSTO COMBUSTIBLE S/.</x-th>
                                <x-th>INGRESO</x-th>
                                <x-th>LABOR</x-th>
                                <x-th>TRACTOR</x-th>

                                <x-th>PRECIO</x-th>
                                <x-th>RATIO</x-th>
                                <x-th>VALOR Y/O COSTO</x-th>
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @foreach ($listaSalidas as $salida)
                                {{-- Fila de la salida --}}
                                <x-tr class="bg-blue-100">
                                    <x-td>{{ $salida->fecha_reporte }}</x-td>
                                    <x-td></x-td>
                                    <x-td></x-td>
                                    <x-td></x-td>
                                    <x-td></x-td>

                                    <x-td></x-td>
                                    <x-td></x-td>
                                    <x-td class="text-red-600 font-bold">{{ $salida->cantidad }}</x-td>
                                    <x-td></x-td>
                                    <x-td>{{ $salida->maquinaria ? $salida->maquinaria->nombre : 'N/A' }}</x-td>
                                    
                                    <x-td>{{ 'S/. ' . number_format($salida->total_costo, 2) }}</x-td>
                                    <x-td></x-td>
                                    <x-td></x-td>
                                </x-tr>

                                {{-- Filas de las distribuciones asociadas --}}
                                @foreach ($salida['distribuciones'] as $dist)
                              
                                    <x-tr class="bg-gray-100">
                                        <x-td>{{ $dist->fecha }}</x-td>
                                        <x-td>{{ $dist->hora_inicio }}</x-td>
                                        <x-td>{{ $dist->hora_salida }}</x-td>
                                        <x-td>{{ $dist->horas }}</x-td>
                                        <x-td>{{ $dist->campo }}</x-td>

                                        <x-td>{{ $dist->cantidad_combustible }}</x-td>
                                        <x-td>{{ $dist->costo_combustible }}</x-td>
                                        <x-td></x-td>
                                        <x-td>{{ $dist->actividad }}</x-td>
                                        <x-td>{{ $dist->maquinaria_nombre }}</x-td>

                                        <x-td></x-td>
                                        <x-td>{{ $dist->ratio }}</x-td>
                                        <x-td>{{ $dist->valor_costo }}</x-td>
                                    </x-tr>
                                @endforeach
                            @endforeach
                        </x-slot>
                    </x-table>
                </div>
                <div class="w-full md:w-[20rem]">

                    <x-h3>Registrar Distribución de Combustible</x-h3>

                    <div class="mt-4">
                        <x-label for="fecha" value="Maquinaria" />
                        @if ($maquinaria)
                            <x-h3>
                                {{ $maquinaria->nombre }}
                            </x-h3>
                        @endif
                    </div>

                    <div class="mt-4">
                        <x-label for="fecha" value="Fecha" />
                        <x-input type="date" wire:model="fecha" class="w-full" />
                        <x-input-error for="fecha" />
                    </div>

                    <div class="mt-4">
                        <x-label for="campo" value="Campo" />
                        <select wire:model="campo" class="w-full border-gray-300 rounded-lg">
                            <option value="">Seleccionar campo</option>
                            @foreach ($campos as $campo)
                                <option value="{{ $campo->nombre }}">{{ $campo->nombre }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="campo" />
                    </div>

                    <div class="mt-4 flex gap-4">
                        <div class="w-1/2">
                            <x-label for="horaInicio" value="Hora de inicio" />
                            <x-input type="time" wire:model="horaInicio" class="w-full" />
                            <x-input-error for="horaInicio" />
                        </div>
                        <div class="w-1/2">
                            <x-label for="horaFin" value="Hora de fin" />
                            <x-input type="time" wire:model="horaFin" class="w-full" />
                            <x-input-error for="horaFin" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <x-label for="descripcion" value="Descripción del trabajo" />
                        <x-textarea wire:model="descripcion" class="w-full"
                            placeholder="E.g., Recogida de infestadores mallita"></x-textarea>
                        <x-input-error for="descripcion" />
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <x-button wire:click="guardarDistribucion">Guardar Distribución</x-button>
                    </div>
                </div>
            </x-flex>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                Cerrar
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>
</div>
