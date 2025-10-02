<div>
    <x-dialog-modal wire:model.live="mostrarFormularioReportePago" maxWidth="full">
        <x-slot name="title">
            <x-flex class="justify-center">
                <div class="text-center">
                    <x-h3>
                        {{ $tituloReporte }}
                    </x-h3>
                    <x-label>
                        {{ $nombreCuadrilla }}
                    </x-label>
                </div>
            </x-flex>
        </x-slot>

        <x-slot name="content">
            {{-- Added wire:key to force re-render on modal open --}}
            <div x-data="cuadroPagos" wire:key="cuadro-pagos-{{ $resumenPorTramo?->id ?? 'new' }}">
                <x-flex class="justify-between">
                    <div class="flex items-center space-x-2 mb-4 p-3 bg-gray-200 rounded-lg dark:bg-gray-700">
                        <x-toggle-switch id="pagar-bonos" x-model="pagarBonos" label="Todo Bono" />
                        <x-toggle-switch id="pagar-jornal" x-model="pagarJornal" label="Todo Jornal" />
                    </div>
                    <x-flex class="justify-end">
                        @if ($resumenPorTramo?->condicion == 'Pendiente')
                            <x-button variant="default" wire:click="generarExcel">
                                Registrar pagos <i class="fa fa-money-bill"></i>
                            </x-button>
                        @else
                            <x-button variant="danger" wire:click="generarExcel">
                                Volver a Pendiente <i class="fa-solid fa-rotate-left"></i>
                            </x-button>

                            @if ($resumenPorTramo?->excel_reporte_file)
                                <a href="{{ Storage::disk('public')->url($resumenPorTramo->excel_reporte_file) }}" target="_blank" rel="noopener noreferrer"
                                    class="ml-2">
                                    <x-button variant="success">
                                        Descargar Reporte <i class="fa fa-file-excel"></i>
                                    </x-button>
                                </a>
                            @endif
                        @endif
                    </x-flex>
                </x-flex>

                <div class="mt-4">
                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th rowspan="2">N°</x-th>
                                <x-th rowspan="2">CUADRILLERO</x-th>
                                <x-th rowspan="2">MONTO S/.</x-th>
                                <x-th rowspan="2">BONO S/.</x-th>
                                <x-th rowspan="2">TOTAL S/.</x-th>

                                @foreach ($periodo as $fechaString)
                                    @php
                                        $fechaObj = \Carbon\Carbon::parse($fechaString);
                                        $fechaInicioPagoStr = \Carbon\Carbon::parse($resumenPorTramo->fecha_acumulada)->toDateString();
                                        $clase = $fechaString < $fechaInicioPagoStr ? 'bg-red-600/50 text-white' : '';
                                    @endphp

                                    <x-th class="text-center {{ $clase }}">
                                        <div class="flex flex-col items-center">
                                            <span>{{ $fechaObj->format('d') }}</span>

                                            <input type="checkbox" class="mt-1 h-4 w-4 text-primary border-gray-300 rounded"
                                                x-ref="jornal_{{ $fechaString }}"
                                                x-bind:checked="isAllJornalSelected('{{ $fechaString }}')"
                                                x-on:change="toggleJornalDia('{{ $fechaString }}', $event.target.checked)">

                                            <input type="checkbox"
                                                class="mt-1 h-4 w-4 text-green-600 border-gray-300 rounded"
                                                x-ref="bono_{{ $fechaString }}"
                                                x-bind:checked="isAllBonoSelected('{{ $fechaString }}')"
                                                x-on:change="toggleBonosDia('{{ $fechaString }}', $event.target.checked)">
                                        </div>
                                    </x-th>
                                @endforeach
                            </x-tr>
                        </x-slot>

                        <x-slot name="tbody">
                            @php
                                $contador = 0;
                            @endphp
                            {{-- Added filter to skip entries without nombres --}}
                            @forelse ($listaPago as $cuadrilleroId => $personal)
                                @if (!empty(data_get($personal, 'nombres')))
                                    @php
                                        $contador++;
                                    @endphp
                                    {{-- Added wire:key for proper row identification --}}
                                    <x-tr wire:key="row-{{ $cuadrilleroId }}-{{ $resumenPorTramo?->id }}">
                                        <x-td>
                                            {{ $contador }}
                                        </x-td>
                                        <x-td>
                                            {{ data_get($personal, 'nombres', '-') }}
                                        </x-td>
                                        <x-td class="text-right">
                                            <span x-text="totales[{{ $cuadrilleroId }}]?.monto ?? 0"></span>
                                        </x-td>

                                        <x-td class="text-right">
                                            <span x-text="totales[{{ $cuadrilleroId }}]?.bono ?? 0"></span>
                                        </x-td>

                                        <x-td class="text-right">
                                            <span x-text="totales[{{ $cuadrilleroId }}]?.total ?? 0"></span>
                                        </x-td>
                                        @foreach ($periodo as $fechaString)
                                            @php
                                                $costoDia = data_get($personal, $fechaString . '.costo_dia');
                                                $bono = data_get($personal, $fechaString . '.total_bono');
                                            @endphp

                                            {{-- Added wire:key for proper cell identification --}}
                                            <x-td class="text-center" wire:key="cell-{{ $cuadrilleroId }}-{{ $fechaString }}">
                                                <div class="flex flex-col items-center gap-1">

                                                    {{-- Removed x-if template and :key to prevent double rendering --}}
                                                    <label
    class="cursor-pointer w-16 px-2 py-1 rounded-lg text-xs font-semibold flex items-center justify-center gap-1 transition-colors"
    :class="[
        $wire.get('listaPago.{{ $cuadrilleroId }}.{{ $fechaString }}.esta_pagado') 
            ? 'bg-green-500 text-white' 
            : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        {{ data_get($personal, $fechaString . '.bloqueado_jornal') ? "'bg-[repeating-linear-gradient(45deg,#7c7c7cff,#7c7c7cff_10px,#a2a2a2ff_10px,#a2a2a2ff_20px)] cursor-not-allowed'" : "''" }}
    ]"
>
    <input type="checkbox"
        wire:model="listaPago.{{ $cuadrilleroId }}.{{ $fechaString }}.esta_pagado"
        class="hidden"
        @if(data_get($personal, $fechaString . '.bloqueado_jornal')) disabled @endif>

    <i class="fa fa-check w-3 h-3"
        x-show="$wire.get('listaPago.{{ $cuadrilleroId }}.{{ $fechaString }}.esta_pagado')"
        style="display: none;"></i>

    <span>{{ $costoDia ? formatear_numero($costoDia) : '-' }}</span>
</label>

{{-- Bono --}}
@if ($bono && $bono > 0)
    <label
        class="cursor-pointer w-16 px-2 py-1 rounded-lg text-xs font-semibold flex items-center justify-center gap-1 transition-colors"
        :class="[
            $wire.get('listaPago.{{ $cuadrilleroId }}.{{ $fechaString }}.bono_esta_pagado') 
                ? 'bg-amber-500 text-white' 
                : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
            {{ data_get($personal, $fechaString . '.bloqueado_bono') ? "'bg-[repeating-linear-gradient(45deg,#7c7c7cff,#7c7c7cff_10px,#a2a2a2ff_10px,#a2a2a2ff_20px)] cursor-not-allowed'" : "''" }}
        ]"
    >
        <input type="checkbox"
            wire:model="listaPago.{{ $cuadrilleroId }}.{{ $fechaString }}.bono_esta_pagado"
            class="hidden"
            @if(data_get($personal, $fechaString . '.bloqueado_bono')) disabled @endif style="color: #a2a2a2ff;">

        <i class="fa fa-check w-3 h-3"
            x-show="$wire.get('listaPago.{{ $cuadrilleroId }}.{{ $fechaString }}.bono_esta_pagado')"
            style="display: none;"></i>

        <span>+{{ formatear_numero($bono) }}</span>
    </label>
@endif

                                                </div>
                                            </x-td>
                                        @endforeach
                                    </x-tr>
                                @endif
                            @empty
                                <x-tr>
                                    <x-td colspan="100%">
                                        Aún no hay datos de personal
                                    </x-td>
                                </x-tr>
                            @endforelse
                        </x-slot>
                        <x-slot name="tfoot">
                            <x-tr>
                                <x-th>-</x-th>
                                <x-th class="text-right font-bold">TOTAL</x-th>
                                <x-th class="text-right font-bold" x-text="totalesGenerales().monto"></x-th>
                                <x-th class="text-right font-bold" x-text="totalesGenerales().bono"></x-th>
                                <x-th class="text-right font-bold" x-text="totalesGenerales().total"></x-th>
                            </x-tr>
                        </x-slot>
                    </x-table>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="cerrarModal" wire:loading.attr="disabled">
                Cerrar
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>

@script
<script>
    Alpine.data('cuadroPagos', () => ({
        totales: {},
        periodo: @entangle('periodo'),
        pagarBonos: false,
        pagarJornal: false,

        init() {
            this.$watch('$wire.listaPago', () => {
                this.recalcularTodos();
                this.periodo.forEach(fecha => {
                    this.updateJornalHeader(fecha);
                    this.updateBonoHeader(fecha);
                });
            });

            this.$watch('pagarBonos', value => {
                this.toggleBonos(value);
            });

            this.$watch('pagarJornal', value => {
                this.toggleJornal(value);
            });

            this.recalcularTodos();
        },

        isAllJornalSelected(fecha) {
            const lista = this.$wire.get('listaPago') ?? {};
            let activos = 0, totales = 0;

            Object.entries(lista).forEach(([id, p]) => {
                if (!p.nombres) return;

                const f = p[fecha];
                if (!f) return;
                if (parseFloat(f.costo_dia ?? 0) > 0) {
                    totales++;
                    if (f.esta_pagado && !f.bloqueado_jornal) activos++;
                }
            });

            return totales > 0 && activos === totales;
        },

        updateJornalHeader(fecha) {
            const lista = this.$wire.get('listaPago') ?? {};
            let activos = 0, totales = 0;

            Object.entries(lista).forEach(([id, p]) => {
                if (!p.nombres) return;

                const f = p[fecha];
                if (!f) return;
                if (parseFloat(f.costo_dia ?? 0) > 0) {
                    totales++;
                    if (f.esta_pagado) activos++;
                }
            });

            const el = this.$refs[`jornal_${fecha}`];
            if (!el) return;

            el.indeterminate = (activos > 0 && activos < totales);
        },

        isAllBonoSelected(fecha) {
            const lista = this.$wire.get('listaPago') ?? {};
            let activos = 0, totales = 0;

            Object.entries(lista).forEach(([id, p]) => {
                if (!p.nombres) return;

                const f = p[fecha];
                if (!f) return;
                if (parseFloat(f.total_bono ?? 0) > 0) {
                    totales++;
                    if (f.bono_esta_pagado) activos++;
                }
            });

            return totales > 0 && activos === totales;
        },

        updateBonoHeader(fecha) {
            const lista = this.$wire.get('listaPago') ?? {};
            let activos = 0, totales = 0;

            Object.entries(lista).forEach(([id, p]) => {
                if (!p.nombres) return;

                const f = p[fecha];
                if (!f) return;
                if (parseFloat(f.total_bono ?? 0) > 0) {
                    totales++;
                    if (f.bono_esta_pagado) activos++;
                }
            });

            const el = this.$refs[`bono_${fecha}`];
            if (!el) return;

            el.indeterminate = (activos > 0 && activos < totales);
        },

        toggleBonos(value) {
            const lista = this.$wire.get('listaPago') ?? {};

            Object.entries(lista).forEach(([i, personal]) => {
                if (!personal.nombres) return;

                this.periodo.forEach(fecha => {
                    const f = personal?.[fecha];
                    if (!f) return;

                    const bonoKey = `listaPago.${i}.${fecha}.bono_esta_pagado`;
                    const current = this.$wire.get(bonoKey);
                    const totalBono = parseFloat(f.total_bono ?? 0);

                    if (value) {
                        if (totalBono > 0 && current !== true) {
                            this.$wire.set(bonoKey, true);
                        }
                    } else {
                        if (current !== false) {
                            this.$wire.set(bonoKey, false);
                        }
                    }
                });
            });

            this.recalcularTodos();
        },

        toggleJornal(value) {
            const lista = this.$wire.get('listaPago') ?? {};

            Object.entries(lista).forEach(([i, personal]) => {
                if (!personal.nombres) return;

                this.periodo.forEach(fecha => {
                    const f = personal[fecha];
                    if (!f) return;

                    const jornalKey = `listaPago.${i}.${fecha}.esta_pagado`;

                    if (value && parseFloat(f.costo_dia ?? 0) > 0) {
                        this.$wire.set(jornalKey, true);
                    } else if (!value) {
                        this.$wire.set(jornalKey, false);
                    }
                });
            });

            this.recalcularTodos();
        },

        toggleJornalDia(fecha, value) {
            const lista = this.$wire.get('listaPago') ?? {};

            Object.entries(lista).forEach(([i, personal]) => {
                if (!personal.nombres) return;

                const f = personal?.[fecha];
                if (!f) return;

                const jornalKey = `listaPago.${i}.${fecha}.esta_pagado`;
                const costo = parseFloat(f.costo_dia ?? 0);

                if (value && costo > 0) {
                    this.$wire.set(jornalKey, true);
                } else if (!value) {
                    this.$wire.set(jornalKey, false);
                }
            });

            this.recalcularTodos();
        },

        toggleBonosDia(fecha, value) {
            const lista = this.$wire.get('listaPago') ?? {};

            Object.entries(lista).forEach(([i, personal]) => {
                if (!personal.nombres) return;

                const f = personal?.[fecha];
                if (!f) return;

                const bonoKey = `listaPago.${i}.${fecha}.bono_esta_pagado`;
                const totalBono = parseFloat(f.total_bono ?? 0);

                if (value && totalBono > 0) {
                    this.$wire.set(bonoKey, true);
                } else if (!value) {
                    this.$wire.set(bonoKey, false);
                }
            });

            this.recalcularTodos();
        },

        recalcularTodos() {
            this.totales = {};
            const lista = this.$wire.get('listaPago') ?? {};

            Object.entries(lista).forEach(([cuadrilleroId, personal]) => {
                if (!personal.nombres) return;
                this.recalcular(cuadrilleroId);
            });
        },

        recalcular(cuadrilleroId) {
            const personal = this.$wire.get(`listaPago.${cuadrilleroId}`);

            if (!personal || !personal.nombres) return;

            let monto = 0, bono = 0;

            this.periodo.forEach(fecha => {
                const f = personal[fecha];
                if (!f) return;

                if (f.esta_pagado && !f.bloqueado_jornal) {
                    monto += parseFloat(f.costo_dia ?? 0);
                }
                if (f.bono_esta_pagado && !f.bloqueado_bono) {
                    bono += parseFloat(f.total_bono ?? 0);
                }
            });

            this.totales[cuadrilleroId] = {
                monto: monto.toFixed(2),
                bono: bono.toFixed(2),
                total: (monto + bono).toFixed(2)
            };
        },

        totalesGenerales() {
            let monto = 0, bono = 0;

            Object.values(this.totales).forEach(t => {
                monto += parseFloat(t?.monto ?? 0);
                bono += parseFloat(t?.bono ?? 0);
            });

            return {
                monto: monto.toFixed(2),
                bono: bono.toFixed(2),
                total: (monto + bono).toFixed(2),
            };
        }
    }));
</script>
@endscript