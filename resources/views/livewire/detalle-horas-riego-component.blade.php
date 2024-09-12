<div>
    <x-card>
        <x-spacing>
            <x-h3>
                Asignación de Horas de Riego
            </x-h3>
            <div>
                <x-label for="activar_copiar_excel{{ $regador }}" class="mt-4">
                    <x-checkbox id="activar_copiar_excel{{ $regador }}" wire:model.live="activarCopiarExcel"
                        class="mr-2" />
                    {{ $activarCopiarExcel == false ? 'Activar' : 'Desactivar' }} Copiar desde Excel
                </x-label>
                <x-label for="activar_descontar_hora_almuerzo{{ $regador }}" class="mt-4">
                    <x-checkbox id="activar_descontar_hora_almuerzo{{ $regador }}"
                        wire:model.live="noDescontarHoraAlmuerzo" class="mr-2" />
                    No Descontar Hora de Almuerzo
                </x-label>
                @if ($activarCopiarExcel == true)
                    <x-textarea rows="8" class="mt-6 mb-2"
                        placeholder="Copie los datos de la tabla de de Excel y péguelos aquí"
                        wire:model="informacionExcel"></x-textarea>
                @endif
                @if ($regador && $activarCopiarExcel == false)
                    <table class="mt-5 w-full border-collapse border border-slate-400" cellpadding="0" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="text-center border border-slate-400">
                                    Campo
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
                                <th class="border border-slate-400">
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (is_array($campos) && count($campos) > 0)
                                @foreach ($campos as $indice => $campob)
                                    <tr x-data="{
                                        inicio: '{{ $campob['inicio'] }}',
                                        fin: '{{ $campob['fin'] }}',
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
                                            $wire.campos[{{ $indice }}]['inicio'] = this.inicio;
                                            this.calculateTotal();
                                        },
                                        updateFin() {
                                            this.fin = this.formatTime(this.fin);
                                            $wire.campos[{{ $indice }}]['fin'] = this.fin;
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
                                                $wire.campos[{{ $indice }}]['total'] = totalFormatted;
                                            }
                                        }
                                    }">
                                        <td class="text-center border border-slate-400">
                                            <input type="text" class="text-center w-full border-none focus:ring-0"
                                                value="{{ $campob['nombre'] }}" readonly />
                                        </td>
                                        <td class="text-center border border-slate-400">
                                            <input type="text" class="text-center w-full border-none focus:ring-0"
                                                x-model="inicio" @blur="updateInicio()" placeholder="HH:mm" />
                                        </td>
                                        <td class="text-center border border-slate-400">
                                            <input type="text" class="text-center w-full border-none focus:ring-0"
                                                x-model="fin" @blur="updateFin()" placeholder="HH:mm" />
                                        </td>
                                        <td class="text-center border border-slate-400">
                                            <input type="text" class="text-center w-full border-none focus:ring-0"
                                                readonly wire:model="campos.{{ $indice }}.total" />
                                        </td>
                                        <td class="text-center border border-slate-400 p-2">
                                            <button class="text-red-500 hover:text-red-700" title="Eliminar Registro"
                                                wire:click="eliminarIndice({{ $indice }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>

                    <div class="flex mt-5 items-center justify-end">
                        <x-secondary-button type="button" wire:click="seleccionarCampos('{{ $regador }}')"
                            class="mr-2 bg-gray-200 text-black px-4 py-2 rounded">
                            Agregar Campos para Regar
                        </x-secondary-button>
                        <x-button type="button" wire:click="store" class="ml-2 px-4 py-2 rounded">
                            Guardar Horas de Riego
                        </x-button>
                    </div>
                @endif

            </div>
        </x-spacing>
    </x-card>

</div>
