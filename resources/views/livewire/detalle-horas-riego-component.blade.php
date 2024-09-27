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
                            <x-tr>
                                <x-th value="Campo" />
                                <x-th value="Inicio" />
                                <x-th value="Fin" />
                                <x-th value="Total de Horas" />
                                <x-th value="SH" title="Sin Haberes" />
                                <x-th />
                            </x-tr>
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
                                        <x-td>
                                            <x-input-inset type="text" value="{{ $campob['nombre'] }}" readonly />
                                        </x-td>
                                        <x-td>
                                            <x-input-inset type="text" x-model="inicio" @blur="updateInicio()" placeholder="HH:mm" />
                                        </x-td>
                                        <x-td>
                                            <x-input-inset type="text" x-model="fin" @blur="updateFin()" placeholder="HH:mm" />
                                        </x-td>
                                        <x-td>
                                            <x-input-inset type="text" readonly wire:model="campos.{{ $indice }}.total" />
                                        </x-td>
                                        <x-td>
                                            <x-checkbox wire:model.live="campos.{{ $indice }}.sh" />
                                        </x-td>
                                        <x-td>
                                            <button class="text-red-500 hover:text-red-700" title="Eliminar Registro"
                                                wire:click="eliminarIndice({{ $indice }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </x-td>

                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>

                    <x-link href="#" wire:click.prevent="seleccionarCampos('{{ $regador }}')">[Agregar Horas de Riego]</x-link>

                    <div class="flex mt-5 items-center justify-end">
                        
                        <div>
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
