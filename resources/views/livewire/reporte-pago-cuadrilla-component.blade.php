<div>
    <x-loading wire:loading />
    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Reporte de Pago de Cuadrilleros
        </x-h3>
    </div>
    <x-card>
        <x-spacing>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <x-label>
                        Selecciona el rango de fecha
                    </x-label>
                    <x-input x-data x-init="flatpickr($refs.picker, {
                        mode: 'range',
                        onClose: (selectedDates, dateStr) => $wire.set('dateRange', dateStr)
                    })" x-ref="picker" type="text" />
                    <small>
                        Se necesita el rango de fecha para ver que grupos de cuadrilla han trabajado durante esos días.
                    </small>
                </div>
                @if ($dateRange)
                    <div>
                        <x-label>
                            Selecciona el grupo
                        </x-label>
                        <x-select wire:model.live="grupoSeleccionado">
                            <option value="">TODOS LOS GRUPOS</option>
                            @foreach ($gruposTrabajando as $grupoTrabajo)
                                <option value="{{ $grupoTrabajo->codigo }}">{{ $grupoTrabajo->nombre }}</option>
                            @endforeach
                        </x-select>
                        <small>
                            Si no carga ningún grupo, es porque durante el rango seleccionado no hay reporte diario de
                            cuadrilla.
                        </small>
                    </div>
                    <div>
                        <x-label>
                            &nbsp;
                        </x-label>
                        <x-button type="button" wire:click="cargarCuadrilla">
                            Cargar cuadrilla
                        </x-button>
                    </div>
                @endif
            </div>
        </x-spacing>
    </x-card>
    @if ($cuadrilleros)
        <x-card class="mt-5">
            <x-spacing>
                <x-table>
                    <x-slot name="thead">
                        <x-tr>
                            <x-th class="text-center">N°</x-th>
                            <x-th>Grupo</x-th>
                            <x-th>Cuadrillero</x-th>
                            @foreach ($fechas as $fecha)
                                <x-th class="text-center">{{ $fecha }}</x-th>
                            @endforeach
                            <x-th class="text-center">Total</x-th>
                            <x-th class="text-center">Monto Pagado</x-th>
                            <x-th class="text-center">Estado</x-th>
                            <x-th class="text-center">Acciones</x-th>
                        </x-tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @foreach ($cuadrilleros as $indice => $cuadrilla)
                        <x-tr style="background-color: {{ $cuadrilla['grupo_color'] }};">
                                <x-td class="text-center">{{ $indice + 1 }}</x-td>
                                <x-td>{{ $cuadrilla['grupo_codigo'] }}</x-td>
                                <x-td class="min-w-[240px]">{{ $cuadrilla['empleado'] }}</x-td>
                                @foreach ($fechas as $fecha)
                                    <x-td class="text-center">
                                        {{ $cuadrilla[$fecha] > 0 ? number_format($cuadrilla[$fecha], 2) : '-' }}
                                    </x-td>
                                @endforeach
                                <x-th class="text-center">{{ number_format($cuadrilla['monto_total'], 2) }}</x-th>
                                <x-th class="text-center">{{ number_format($cuadrilla['monto_pagado'], 2) }}</x-th>
                                <x-th class="text-center">{{ $cuadrilla['esta_cancelado']?'Pagado':'-' }}</x-th>
                                <x-td class="min-w-[150px] text-center">
                                    <x-button type="button" @click="$wire.dispatch('realizarPagoCuadrillero',{'cuadrilleroId':'{{$cuadrilla['cuadrillero_id']}}','fechaInicio':'{{$fechaInicio}}','fechaFin':'{{$fechaFin}}'})">
                                        <i class="fa fa-money-bill"></i> Pagos
                                    </x-button>
                                </x-td>
                            </x-tr>
                        @endforeach
                    </x-slot>
                </x-table>

            </x-spacing>
        </x-card>
        <x-card class="mt-5">
            <x-spacing>
                <x-flex class="justify-end">
                    <x-button type="button" wire:click="exportarReporte">
                        <i class="fa fa-file-excel"></i> Exportar reporte
                    </x-button>
                </x-flex>
            </x-spacing>
        </x-card>
    @endif
</div>
