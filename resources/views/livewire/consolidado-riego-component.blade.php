<div>
    <x-card>
        <x-spacing>
            <div class="flex items-center justify-between mb-4">
                <!-- Botón para fecha anterior -->
                <x-button wire:click="fechaAnterior">
                    <i class="fa fa-chevron-left"></i> Fecha Anterior
                </x-button>

                <!-- Input para seleccionar la fecha -->
                <x-input type="date" wire:model.live="fecha" class="text-center mx-2 !w-auto" />

                <!-- Botón para fecha posterior -->
                <x-button wire:click="fechaPosterior">
                    Fecha Posterior <i class="fa fa-chevron-right"></i>
                </x-button>
            </div>

            <x-table class="mt-5">
                <x-slot name="thead">
                    <tr>
                        <x-th value="N°" class="text-center" />
                        <x-th value="Documento del Regador" />
                        <x-th value="Nombre del Regador" />
                        <x-th value="Fecha" />
                        <x-th value="Hora de Inicio" />
                        <x-th value="Hora de Fin" />
                        <x-th value="Horas Riego" />
                        <x-th value="Horas Obs." />
                        <x-th value="Horas Acum. Usadas" />
                        <x-th value="Total Horas Jornal" />
                        <x-th value="Estado" />
                        <x-th value="Acciones" class="text-center" />
                    </tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($consolidado_riegos as $indice => $consolidado)
                        <x-tr>
                            <x-th value="{{ $indice + 1 }}" class="text-center" />
                            <x-td value="{{ $consolidado->regador_documento }}" class="text-center" />
                            <x-td value="{{ $consolidado->regador_nombre }}" />
                            <x-td value="{{ $consolidado->fecha }}" class="text-center" />
                            <x-td value="{{ $consolidado->hora_inicio }}" class="text-center" />
                            <x-td value="{{ $consolidado->hora_fin }}" class="text-center" />
                            <x-td value="{{ $consolidado->total_horas_riego }}" class="text-center" />
                            <x-td value="{{ $consolidado->total_horas_observaciones }}" class="text-center" />
                            <x-td value="{{ $consolidado->total_horas_acumuladas }}" class="text-center" />
                            <x-td value="{{ $consolidado->total_horas_jornal }}" class="text-center" />
                            <x-td value="{{ $consolidado->estado == 'noconsolidado' ? 'No Consolidado' : 'Consolidado' }}"
                                class="text-center" />
                            <x-td>
                                @if ($consolidado->estado == 'noconsolidado')
                                    <x-button wire:click="consolidarRegistro">
                                        Consolidar
                                    </x-button>
                                @endif
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>
</div>
