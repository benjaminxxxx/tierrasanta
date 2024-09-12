<div>
    <x-card>
        <x-spacing>
            <x-h3>
                Observaciones
            </x-h3>
            <div>
                <div class="flex items-center">
                    @if ($horasAcumuladas && $horasAcumuladas->count() > 0)
                        @foreach ($horasAcumuladas as $horaAcumulada)
                            @if (!$horaAcumulada->fecha_uso)
                                <x-secondary-button class="mr-3" wire:click="usarEstafecha({{ $horaAcumulada->id }})">
                                    {{ $horaAcumulada->hora }}
                                </x-secondary-button>
                            @else
                                <x-button class="mr-3" wire:click="noUsarEstafecha({{ $horaAcumulada->id }})">
                                    {{ $horaAcumulada->hora }}
                                </x-button>
                            @endif
                        @endforeach
                    @else
                        No hay Horas acumuladas para usar
                    @endif
                </div>
                <!--<x-label for="activar_copiar_excel_obs{{ $regador }}" class="mt-4">
                    <x-checkbox id="activar_copiar_excel_obs{{ $regador }}" wire:model.live="activarCopiarExcel"
                        class="mr-2" />
                    {{ $activarCopiarExcel == false ? 'Activar' : 'Desactivar' }} Copiar desde Excel
                </x-label>-->
                @if ($activarCopiarExcel == true)
                    <x-textarea rows="8" class="mt-6 mb-2"
                        placeholder="Copie los datos de la tabla de de Excel y péguelos aquí"
                        wire:model="informacionExcel"></x-textarea>
                @endif
                @if ($regador && $activarCopiarExcel == false)
                    <table class="mt-5 w-full border-collapse border border-slate-400" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center border border-slate-400  p-2">
                                    N°
                                </th>
                                <th class="text-center border border-slate-400  p-2">
                                    Horas
                                </th>
                                <th class="text-left border border-slate-400 p-2">
                                    Observación
                                </th>
                                <th class="border border-slate-400 p-2">
                                </th>

                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $contador = 0;
                            @endphp
                            @if ($observaciones)
                                @foreach ($observaciones as $indice => $observacionArray)
                                    @php
                                        $contador++;
                                    @endphp
                                    <tr>

                                        <td class="text-center border border-slate-400 p-2">
                                            {{ $contador }}
                                        </td>
                                        <td class="text-center border border-slate-400 p-2">
                                            {{ substr($observacionArray->horas, 0, 5) }}
                                        </td>
                                        <td class="text-left border border-slate-400 p-2">
                                            {{ $observacionArray->detalle_observacion }}
                                        </td>
                                        <td class="text-center border border-slate-400 p-2">
                                            <button class="text-red-500 hover:text-red-700" title="Eliminar Registro"
                                                wire:click="eliminarObservacion({{ $observacionArray->id }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            @if ($totalHorasAcumuladas != '00:00')
                                <tr>
                                    <td class="text-center border border-slate-400 p-2">
                                        {{ $contador + 1 }}
                                    </td>
                                    <td class="text-center border border-slate-400 p-2">
                                        {{ $totalHorasAcumuladas }}
                                    </td>
                                    <td class="text-left border border-slate-400 p-2">
                                        Uso de Horas Acumuladas
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>

                    <div class="flex items-end justify-end text-left mt-5">
                        <div x-data="{
                            inicio: '',
                            formatTime(time) {
                                // Formatear cuando tiene solo la hora
                                if (/^\d{1,2}$/.test(time)) {
                                    return time.padStart(2, '0') + ':00';
                                }
                                // Formatear hora y minutos (sin ceros iniciales)
                                if (/^\d{1,2}:\d{1,2}$/.test(time)) {
                                    let [hours, minutes] = time.split(':');
                                    hours = hours.padStart(2, '0');
                                    minutes = minutes.padStart(2, '0');
                                    return `${hours}:${minutes}`;
                                }
                                return time;
                            },
                            updateTotal() {
                                this.inicio = this.formatTime(this.inicio);
                                $wire.horas = this.inicio;
                            }
                        }">
                            <x-label>Total Horas</x-label>
                            <x-input type="text" class="!w-24" wire:model.defer="horas" x-model="inicio" @blur="updateTotal()"
                                placeholder="HH:mm" />
                            <x-input-error for="horas" />
                        </div>
                        <div class="ml-2">
                            <x-label>Observación</x-label>
                            <x-input type="text" class="!w-full" wire:model="observacion" />
                            <x-input-error for="observacion" />
                        </div>
                        <div class="ml-2">
                            <x-button type="button" wire:click="store" class="ml-2 px-4 py-2 rounded">
                                Agregar Observaciones
                            </x-button>
                        </div>
                    </div>
                @endif

            </div>
        </x-spacing>
    </x-card>
</div>
