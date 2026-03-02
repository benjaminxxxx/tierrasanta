<div>

    <x-card>
        <x-flex class="justify-between">
            <x-flex>
                <x-button variant="secondary" wire:click="fechaAnterior" class="w-full lg:w-auto">
                    <i class="fa fa-chevron-left"></i>
                </x-button>
                <x-selector-dia wire:model.live="fecha" />
                <x-button variant="secondary" wire:click="fechaPosterior" class="w-full lg:w-auto">
                    <i class="fa fa-chevron-right"></i>
                </x-button>
            </x-flex>
            <x-flex>
                <x-button @click="$wire.dispatch('abrirAgregarRegador')">
                    <i class="fa fa-plus"></i> Agregar Regador
                </x-button>
                <x-button wire:click="enviarRegistroDiarioRegadores">
                    <i class="fa fa-paper-plane"></i> Enviar Registro Diario
                </x-button>
            </x-flex>
        </x-flex>
    </x-card>
    <div class="my-4">
        @if ($consolidados && $consolidados->count() > 0)
            @foreach ($consolidados as $riego)
                <livewire:gestion-riego.reporte-diario-riego-detalle-component :resumenId="$riego->id" :fecha="$riego->fecha"
                    wire:key="horas_riego_{{ $riego->id }}_{{ $riego->fecha }}" />
            @endforeach
        @endif
    </div>
    <x-dialog-modal wire:model.live="mostrarEnvioAReporteDiario">
        <x-slot name="title">
            Enviar Resumen a Registros Diarios
        </x-slot>

        <x-slot name="content">

            <h4 class="text-lg text-card-foreground mb-3">
                Registros a enviar
            </h4>

            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th>Tipo</x-th>
                        <x-th>Trabajador</x-th>
                        <x-th>Campo</x-th>
                        <x-th>Labor</x-th>
                        <x-th>Hora Inicio</x-th>
                        <x-th>Hora Fin</x-th>
                    </x-tr>
                </x-slot>

                <x-slot name="tbody">
                    @foreach ($listaPorEnviarRegadores as $fila)
                        <x-tr>
                            <x-td class="uppercase">{{ $fila['tipo'] }}</x-td>
                            <x-td class="!text-left">{{ $fila['trabajador_name'] }}</x-td>
                            <x-td>{{ $fila['campo'] }}</x-td>
                            <x-td>{{ $fila['labor'] }}</x-td>
                            <x-td>{{ $fila['hora_inicio'] }}</x-td>
                            <x-td>{{ $fila['hora_fin'] }}</x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>

        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" @click="$wire.set('mostrarEnvioAReporteDiario',false)">
                Cerrar
            </x-button>
            <x-button wire:click="confirmarEnvio" wire:loading.attr="disabled">
                <i class="fa fa-paper-plane"></i>Confirmar Envio
            </x-button>
        </x-slot>
    </x-dialog-modal>
    <livewire:gestion-riego.reporte-diario-agregar-regadores-component :fecha="$fecha"
        wire:key="agregarregadores_{{ $fecha }}" />

    <x-loading wire:loading />

</div>
