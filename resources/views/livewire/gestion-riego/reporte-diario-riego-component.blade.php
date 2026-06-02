<div class="space-y-4">
    <x-flex class="justify-between">
        <div>
            <x-title>
                Registro Diario de Riego
            </x-title>

        </div>

        <div class="ms-3 relative">
            <x-dropdown align="right" width="60">
                <x-slot name="trigger">
                    <span class="inline-flex rounded-md">
                        <button type="button"
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-card-foreground bg-card transition ease-in-out duration-150">
                            Opciones

                            <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                            </svg>
                        </button>
                    </span>
                </x-slot>

                <x-slot name="content">
                    <div class="w-60">
                        <!-- Team Management -->
                        <div class="block px-4 py-2 text-xs text-gray-400">
                            Opciones
                        </div>

                        <!-- Team Settings -->
                        <x-dropdown-link wire:click="verResumenSemanalRiego">
                            Ver Resumen Semanal
                        </x-dropdown-link>
                        @can(\App\Constants\Permisos::CAMPO_RIEGO_REPORTE_GESTIONAR)
                            <x-dropdown-link @click="$wire.dispatch('abrirAgregarRegador')">
                                Agregar Regador
                            </x-dropdown-link>
                            <x-dropdown-link wire:click="enviarRegistroDiarioRegadores">
                                Enviar Registro Diario
                            </x-dropdown-link>
                        @endcan

                    </div>
                </x-slot>
            </x-dropdown>
        </div>

    </x-flex>
    <x-card>
        <x-flex class="justify-between">
            @include('comun.selector-dia-base')
            <x-input type="number" wire:model.live="limiteHorasDiarias" label="Limite de Horas" />
        </x-flex>
    </x-card>
    <div class="my-4">
        @can(\App\Constants\Permisos::CAMPO_RIEGO_REPORTE_VER)
            @if ($consolidados && $consolidados->count() > 0)
                @foreach ($consolidados as $riego)
                    <livewire:gestion-riego.reporte-diario-riego-detalle-component :resumenId="$riego->id" :fecha="$riego->fecha"
                        wire:key="horas_riego_{{ $riego->id }}_{{ $riego->fecha }}" />
                @endforeach
            @endif
        @else
            <x-danger>
                No tienes permisos para ver el reporte diario de riego. Por favor, contacta al administrador.
            </x-danger>
        @endcan
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