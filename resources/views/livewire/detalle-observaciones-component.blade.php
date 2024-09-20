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
                                <th class="text-center border border-slate-400">
                                    Inicio
                                </th>
                                <th class="text-center border border-slate-400">
                                    Fin
                                </th>
                                <th class="text-center border border-slate-400">
                                    Total de Horas
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
                            @if ($observacionesArray)
                                @foreach ($observacionesArray as $indice => $observacionArray)
                                    @php
                                        $contador++;
                                    @endphp
                                    <tr x-data="{
                                        inicio: '{{ $observacionArray['hora_inicio'] }}',
                                        fin: '{{ $observacionArray['hora_fin'] }}',
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
                                        updateInicio() {
                                            this.inicio = this.formatTime(this.inicio);
                                            $wire.observacionesArray[{{ $indice }}]['hora_inicio'] = this.inicio;
                                            this.calculateTotal();
                                        },
                                        updateFin() {
                                            this.fin = this.formatTime(this.fin);
                                            $wire.observacionesArray[{{ $indice }}]['hora_fin'] = this.fin;
                                            this.calculateTotal();
                                        },
                                        calculateTotal() {
                                            if (this.inicio && this.fin) {
                                                let [startHours, startMinutes] = this.inicio.split(':');
                                                let [endHours, endMinutes] = this.fin.split(':');
                                    
                                                let startTime = new Date();
                                                let endTime = new Date();
                                    
                                                startTime.setHours(parseInt(startHours), parseInt(startMinutes));
                                                endTime.setHours(parseInt(endHours), parseInt(endMinutes));
                                    
                                                let diffMs = endTime - startTime; // Diferencia en milisegundos
                                                let diffHours = Math.floor(diffMs / 1000 / 60 / 60); // Diferencia en horas
                                                let diffMinutes = Math.floor((diffMs / 1000 / 60) % 60); // Diferencia en minutos
                                    
                                                let totalFormatted = `${String(diffHours).padStart(2, '0')}:${String(diffMinutes).padStart(2, '0')}`;
                                                $wire.observacionesArray[{{ $indice }}]['horas'] = totalFormatted;
                                                $wire.$refresh();
                                            }
                                        }
                                    }">

                                        <td class="text-center border border-slate-400 !p-0">
                                            {{ $contador }}
                                        </td>
                                        <td class="text-center border border-slate-400 !p-0">
                                            <input type="text"
                                                class="text-center w-full border-none focus:ring-0 p-2" x-model="inicio"
                                                @blur="updateInicio()" onclick="this.select()" wire:model.delay="observacionesArray.{{ $indice }}.hora_inicio" placeholder="HH:mm" />
                                        </td>
                                        <td class="text-left border border-slate-400 !p-0">
                                            <input type="text"
                                                class="text-center w-full border-none focus:ring-0 p-2" x-model="fin"
                                                @blur="updateFin()" onclick="this.select()" wire:model.delay="observacionesArray.{{ $indice }}.hora_fin" placeholder="HH:mm" />
                                        </td>
                                        <td class="text-center border border-slate-400 !p-0">
                                            <input type="text"
                                                class="text-center w-full border-none focus:ring-0  p-2"
                                                wire:model="observacionesArray.{{ $indice }}.horas" />
                                        </td>
                                        <td class="text-center border border-slate-400 !p-0">
                                            <input type="text" class="text-left w-full border-none focus:ring-0 p-2"
                                                wire:model.live="observacionesArray.{{ $indice }}.detalle_observacion" />
                                        </td>
                                        <td class="text-center border border-slate-400 p-2">
                                            <button class="text-red-500 hover:text-red-700" title="Eliminar Registro"
                                                wire:click="eliminarObservacion({{ $indice }})">
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

                                    </td>
                                    <td class="text-center border border-slate-400 p-2">

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
                    <a href="#"
                        class="text-orange-600 hover:text-orange-700 text-md font-bold mt-3 text-left inline-block float-left"
                        wire:click.prevent="agregarObservacion">[Agregar Observación]</a>

                    <div class="flex items-end justify-end text-left mt-5">

                        <div class="ml-2">
                            @if ($cambiosRealizados)
                                <x-danger-button type="button" wire:click="cancelarCambios"
                                    class="ml-2">
                                    Cancelar Cambios
                                </x-danger-button>
                                <x-button type="button" wire:click="store"
                                    class="ml-2">
                                    Registrar Cambios
                                </x-button>
                            @else
                                <x-button type="button" wire:click="store" class="bg-opacity-60" disabled>
                                    Registrar Cambios
                                </x-button>
                            @endif
                        </div>
                    </div>
                @endif

            </div>
        </x-spacing>
    </x-card>
</div>
