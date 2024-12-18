<div>
    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
            Pago de cuadrillero
        </x-slot>

        <x-slot name="content">
            @if ($pagosRealizados && $pagosRealizados->count()>0)
                <x-table class="mb-5">
                    <x-slot name="thead">
                        <x-tr>
                            <x-th>
                                N°
                            </x-th>
                            <x-th>
                                Fecha de pago
                            </x-th>
                            <x-th>
                                Monto Pagado
                            </x-th>
                            <x-th>
                                Saldo pendiente
                            </x-th>
                            <x-th>
                                Fecha Contable
                            </x-th>
                            <x-th>
                                Estado de Pago
                            </x-th>
                            <x-th>
                                Acciones
                            </x-th>
                        </x-tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @foreach ($pagosRealizados as $indicePago => $pagoRealizado)
                            <x-tr>
                                <x-td>
                                    {{ $indicePago + 1 }}
                                </x-td>
                                <x-td>
                                    {{ $pagoRealizado->fecha_pago }}
                                </x-td>
                                <x-td>
                                    {{ $pagoRealizado->monto_pagado }}
                                </x-td>
                                <x-td>
                                    {{ $pagoRealizado->saldo_pendiente }}
                                </x-td>
                                <x-td>
                                    {{ $pagoRealizado->fechaContable }}
                                </x-td>
                                <x-td>
                                    {{ $pagoRealizado->estadoDetalle }}
                                </x-td>
                                <x-td>
                                    @if ($pagoRealizado->estado == 'pago_completo' || ($pagoRealizado->estado == 'pago_parcial' && !$estaCancelado))
                                        <x-danger-button type="button"
                                            wire:click="eliminarPago({{ $pagoRealizado->id }})">
                                            <i class="fa fa-trash"></i> Eliminar pago
                                        </x-danger-button>
                                    @endif
                                </x-td>
                            </x-tr>
                        @endforeach
                    </x-slot>
                </x-table>
            @endif
            @if ($cuadrillero)
                <x-table>
                    <x-slot name="thead">

                    </x-slot>
                    <x-slot name="tbody">
                        <x-tr>
                            <x-th>
                                Cuadrillero:
                            </x-th>
                            <x-td>
                                {{ $cuadrillero->nombres }}
                            </x-td>
                        </x-tr>
                        <x-tr>
                            <x-th>
                                Horas Trabajadas:
                            </x-th>
                            <x-td>
                                {{ $this->montoAPagar['total_horas'] }}
                                {{ $this->montoAPagar['total_horas'] == 1 ? 'hora' : 'horas' }}
                            </x-td>
                        </x-tr>
                        <x-tr>
                            <x-th>
                                Valor Hora:
                            </x-th>
                            <x-td>
                                @if ($this->montoAPagar['total_horas'] != 0)
                                    S/ {{ $this->montoAPagar['monto_a_pagar'] / $this->montoAPagar['total_horas'] }}
                                    soles
                                @else
                                    -
                                @endif
                            </x-td>
                        </x-tr>
                        <x-tr>
                            <x-th>
                                Monto Trabajado:
                                <small>
                                    Si observa un monto diferente a lo esperado, pueda que haya hecho un filtro por
                                    grupo, y aqui se muestra el monto total a pagar sin importar los grupos
                                </small>
                            </x-th>
                            <x-td>
                                S/ {{ $this->montoAPagar['monto_a_pagar'] }} soles
                            </x-td>
                        </x-tr>
                        @if ($estaCancelado)
                            <x-tr>
                                <x-th>
                                    Monto Pagado:
                                </x-th>
                                <x-th>
                                    S/ {{ $this->montoPagado }} soles
                                </x-th>
                            </x-tr>
                        @else
                            <x-tr>
                                <x-th>
                                    Monto Pagado contable:
                                    <small>
                                        El precio a pagar puede diferir por falta de monedas decimales
                                    </small>
                                </x-th>
                                <x-td>
                                    <x-input type="number" step="0.01" wire:model="montoAPagarContable" />
                                    <x-input-error for="montoAPagarContable" />
                                </x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>
                                    Mes y Año Contable:
                                </x-th>
                                <x-td>
                                    <x-flex>
                                        <!-- Selector de mes -->
                                        <div>
                                            <x-label value="Mes" />
                                            <x-select wire:model="mesContableSeleccionado">
                                                <option value="">Seleccione un mes</option>
                                                @foreach (['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $index => $mes)
                                                    <option value="{{ $index + 1 }}">{{ $mes }}</option>
                                                @endforeach
                                            </x-select>
                                            <x-input-error for="mesContableSeleccionado" />
                                        </div>
                                        <!-- Selector de año -->
                                        <div>
                                            <x-label value="Año" />
                                            <x-select class="ml-2" wire:model="anioContableSeleccionado">
                                                <option value="">Seleccione un año</option>
                                                @foreach ($anios as $anio)
                                                    <option value="{{ $anio }}">{{ $anio }}</option>
                                                @endforeach
                                            </x-select>
                                            <x-input-error for="anioContableSeleccionado" />
                                        </div>
                                    </x-flex>
                                </x-td>
                            </x-tr>
                            <x-tr>
                                <x-th>
                                    Estado del pago:
                                    <small>
                                        Si el pago es completo no se podrá agregar mas pagos mas adelante para este
                                        rango de
                                        fechas
                                    </small>
                                </x-th>

                                <x-td>
                                    <x-select wire:model="estadoPago">
                                        <option value="pago_completo">Pago Completo</option>
                                        <option value="pago_parcial">Adelanto</option>
                                    </x-select>
                                    <x-input-error for="estadoPago" />
                                </x-td>
                            </x-tr>
                        @endif

                    </x-slot>
                </x-table>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-flex class="justify-end">
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                @if (!$estaCancelado)
                    <x-button wire:click="realizarPago" wire:loading.attr="disabled">
                        Realizar pago
                    </x-button>
                @endif
            </x-flex>
        </x-slot>
    </x-dialog-modal>
</div>
