<div>
    <x-h3>
        Consolidado de riego
    </x-h3>
    <x-card2 class="mt-4">
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
                        <x-th value="N°"/>
                        <x-th value="Documento del Regador" />
                        <x-th value="Nombre del Regador"   class="!text-left"/>
                        <x-th value="Fecha" />
                        <x-th value="Hora de Inicio" />
                        <x-th value="Hora de Fin" />
                        <x-th value="Horas Riego" />
                        <x-th value="Horas Obs." />
                        <x-th value="Horas Acum. Usadas" />
                        <x-th value="Total Horas Jornal" />
                        <x-th value="Estado" />
                        <x-th value="Acciones"/>
                    </tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($consolidado_riegos as $indice => $consolidado)
                        <x-tr>
                            <x-th value="{{ $indice + 1 }}"/>
                            <x-td value="{{ $consolidado->regador_documento }}"/>
                            <x-td value="{{ $consolidado->regador_nombre }}"  class="!text-left"  />
                            <x-td value="{{ $consolidado->fecha }}"/>
                            <x-td value="{{ $consolidado->hora_inicio }}"/>
                            <x-td value="{{ $consolidado->hora_fin }}"/>
                            <x-td value="{{ $consolidado->total_horas_riego }}"/>
                            <x-td value="{{ $consolidado->total_horas_observaciones }}"/>
                            <x-td value="{{ $consolidado->total_horas_acumuladas }}"/>
                            <x-td value="{{ $consolidado->total_horas_jornal }}"/>
                            <x-td value="{{ $consolidado->estado == 'noconsolidado' ? 'No Consolidado' : 'Consolidado' }}"
                               />
                            <x-td>
                                @if ($consolidado->estado == 'noconsolidado')
                                    <x-button wire:click="consolidarRegistro({{$consolidado->regador_documento}})">
                                        Consolidar
                                    </x-button>
                                @endif
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
    </x-card2>
</div>
