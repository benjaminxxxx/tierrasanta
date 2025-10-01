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
            <div x-data="cuadroPagos">
                <x-flex class="justify-between">
                    <div class="flex items-center space-x-2 mb-4 p-3 bg-gray-200 rounded-lg dark:bg-gray-700">
                        <!-- Toggle -->
                        <x-toggle-switch id="pagar-bonos" x-model="pagarBonos" label="Todo Bono" />
                        <x-toggle-switch id="pagar-jornal" x-model="pagarJornal" label="Todo Jornal" />
                    </div>
                    <x-flex class="justify-end">
                        @if ($resumenPorTramo?->condicion == 'Pendiente')
                            <x-button variant="default" wire:click="cambiarCondicionResumen({{ $resumenPorTramo?->id }})">
                                Cambiar a Pagado <i class="fa fa-money-bill"></i>
                            </x-button>
                        @else
                            <x-button variant="danger" wire:click="cambiarCondicionResumen({{ $resumenPorTramo?->id }})">
                                Volver a Pendiente <i class="fa-solid fa-rotate-left"></i>
                            </x-button>
                        @endif

                        <x-button variant="success" wire:click="generarExcel">
                            Guardar y Descargar Excel <i class="fa fa-file-excel"></i>
                        </x-button>
                    </x-flex>
                </x-flex>

                <div class="mt-4">
                    <x-table>
                        <x-slot name="thead">
                            {{-- Primera fila --}}
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

                                            {{-- Checkbox para todos los jornales --}}
                                            <input type="checkbox" class="mt-1 h-4 w-4 text-primary border-gray-300 rounded"
                                                x-ref="jornal_{{ $fechaString }}"
                                                x-bind:checked="isAllJornalSelected('{{ $fechaString }}')"
                                                x-on:change="toggleJornalDia('{{ $fechaString }}', $event.target.checked)"
                                                x-init="$watch('$wire.listaPago', () => updateJornalHeader('{{ $fechaString }}'))">

                                            {{-- Checkbox para todos los bonos --}}
                                            <input type="checkbox"
                                                class="mt-1 h-4 w-4 text-green-600 border-gray-300 rounded"
                                                x-ref="bono_{{ $fechaString }}"
                                                x-bind:checked="isAllBonoSelected('{{ $fechaString }}')"
                                                x-on:change="toggleBonosDia('{{ $fechaString }}', $event.target.checked)"
                                                x-init="$watch('$wire.listaPago', () => updateBonoHeader('{{ $fechaString }}'))">
                                        </div>
                                    </x-th>
                                @endforeach
                            </x-tr>
                        </x-slot>

                        <x-slot name="tbody">
                            @php
                                $contador = 0;
                            @endphp
                            @forelse ($listaPago as $cuadrilleroId => $personal)
                            @php
                                $contador++;
                            @endphp
                                <x-tr>
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

                                        <x-td class="text-center">
                                            <div class="flex flex-col items-center gap-1">

                                                {{-- Toggle para costo_dia --}}
                                                <label
                                                    class="cursor-pointer w-16 px-2 py-1 rounded-lg text-xs font-semibold flex items-center justify-center gap-1 transition-colors"
                                                    :class="$wire.get('listaPago.{{ $cuadrilleroId }}.{{ $fechaString }}.esta_pagado') 
                                                                                                                                ? 'bg-green-500 text-white' 
                                                                                                                                : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300'">

                                                    <input type="checkbox"
                                                        wire:model="listaPago.{{ $cuadrilleroId }}.{{ $fechaString }}.esta_pagado"
                                                        class="hidden">

                                                    <template
                                                        x-if="$wire.get('listaPago.{{ $cuadrilleroId }}.{{ $fechaString }}.esta_pagado')"
                                                        :key="'check-jornal-{{ $cuadrilleroId }}-{{ $fechaString }}'">
                                                        <i class="fa fa-check w-3 h-3"></i>
                                                    </template>
                                                    <span>{{ $costoDia ? formatear_numero($costoDia) : '-' }}</span>
                                                </label>

                                                {{-- Toggle para bono --}}
                                                @if ($bono && $bono > 0)
                                                    <label
                                                        class="cursor-pointer w-16 px-2 py-1 rounded-lg text-xs font-semibold flex items-center justify-center gap-1 transition-colors"
                                                        :class="$wire.get('listaPago.{{ $cuadrilleroId }}.{{ $fechaString }}.bono_esta_pagado') 
                                                                                                                                                                                                ? 'bg-amber-500 text-white' 
                                                                                                                                                                                                : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300'">

                                                        <input type="checkbox"
                                                            wire:model="listaPago.{{ $cuadrilleroId }}.{{ $fechaString }}.bono_esta_pagado"
                                                            class="hidden">

                                                        <template
                                                            x-if="$wire.get('listaPago.{{ $cuadrilleroId }}.{{ $fechaString }}.bono_esta_pagado')">
                                                            <i class="fa fa-check w-3 h-3"></i>
                                                        </template>
                                                        <span>+{{ formatear_numero($bono) }}</span>
                                                    </label>
                                                @endif

                                            </div>
                                        </x-td>
                                    @endforeach


                                </x-tr>
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

                                {{-- monto --}}
                                <x-th class="text-right font-bold" x-text="totalesGenerales().monto"></x-th>


                                <x-th class="text-right font-bold" x-text="totalesGenerales().bono"></x-th>


                                {{-- gasto total --}}
                                <x-th class="text-right font-bold" x-text="totalesGenerales().total"></x-th>
                            </x-tr>
                        </x-slot>

                    </x-table>
                </div>
            </div>


        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('mostrarFormularioReportePago', false)" wire:loading.attr="disabled">
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
            // inicialización: Livewire expone listaPago
            this.$watch('$wire.listaPago', () => {
                this.recalcularTodos()
            });
            this.$watch('pagarBonos', value => {
                this.toggleBonos(value)
            });
            this.$watch('pagarJornal', value => {
                this.toggleJornal(value)
            });
            this.recalcularTodos();
        },
        isAllJornalSelected(fecha) {
            const lista = this.$wire.get('listaPago') ?? {}
            let activos = 0, totales = 0

            Object.values(lista).forEach(p => {
                const f = p[fecha]
                if (!f) return
                if (parseFloat(f.costo_dia ?? 0) > 0) {
                    totales++
                    if (f.esta_pagado) activos++
                }
            })

            return totales > 0 && activos === totales
        },

        updateJornalHeader(fecha) {
            const lista = this.$wire.get('listaPago') ?? {}
            let activos = 0, totales = 0

            Object.values(lista).forEach(p => {
                const f = p[fecha]
                if (!f) return
                if (parseFloat(f.costo_dia ?? 0) > 0) {
                    totales++
                    if (f.esta_pagado) activos++
                }
            })

            const el = this.$refs[`jornal_${fecha}`]
            if (!el) return

            el.indeterminate = (activos > 0 && activos < totales)
        },

        isAllBonoSelected(fecha) {
            const lista = this.$wire.get('listaPago') ?? {}
            let activos = 0, totales = 0

            Object.values(lista).forEach(p => {
                const f = p[fecha]
                if (!f) return
                if (parseFloat(f.total_bono ?? 0) > 0) {
                    totales++
                    if (f.bono_esta_pagado) activos++
                }
            })

            return totales > 0 && activos === totales
        },

        updateBonoHeader(fecha) {
            const lista = this.$wire.get('listaPago') ?? {}
            let activos = 0, totales = 0

            Object.values(lista).forEach(p => {
                const f = p[fecha]
                if (!f) return
                if (parseFloat(f.total_bono ?? 0) > 0) {
                    totales++
                    if (f.bono_esta_pagado) activos++
                }
            })

            const el = this.$refs[`bono_${fecha}`]
            if (!el) return

            el.indeterminate = (activos > 0 && activos < totales)
        },
        toggleBonos(value) {
            const lista = this.$wire.get('listaPago') ?? {};

            Object.keys(lista).forEach(i => {
                const personal = lista[i];

                this.periodo.forEach(fecha => {
                    const f = personal?.[fecha];
                    if (!f) return;

                    const bonoKey = `listaPago.${i}.${fecha}.bono_esta_pagado`;
                    const current = this.$wire.get(bonoKey);

                    // parsear safe
                    const totalBono = parseFloat(f.total_bono ?? 0);

                    if (value) {
                        // activar solo si hay bono > 0 y aún no está activo
                        if (totalBono > 0 && current !== true) {
                            this.$wire.set(bonoKey, true);
                        }
                    } else {
                        // cuando value === false, desactivar todos los bonos (si es necesario)
                        if (current !== false) {
                            this.$wire.set(bonoKey, false);
                        }
                    }
                });
            });

            this.recalcularTodos();
        },

        toggleJornal(value) {
            let lista = this.$wire.get('listaPago') ?? {}

            Object.keys(lista).forEach(i => {
                let personal = lista[i]

                this.periodo.forEach(fecha => {
                    let f = personal[fecha]
                    if (!f) return

                    let jornalKey = `listaPago.${i}.${fecha}.esta_pagado`

                    if (value && parseFloat(f.costo_dia ?? 0) > 0) {
                        // activar solo si hay monto > 0
                        this.$wire.set(jornalKey, true)
                    } else if (!value) {
                        // desactivar todos
                        this.$wire.set(jornalKey, false)
                    }
                })
            })

            this.recalcularTodos()
        },
        toggleJornalDia(fecha, value) {
            const lista = this.$wire.get('listaPago') ?? {}

            Object.keys(lista).forEach(i => {
                const f = lista[i]?.[fecha]
                if (!f) return

                const jornalKey = `listaPago.${i}.${fecha}.esta_pagado`
                const costo = parseFloat(f.costo_dia ?? 0)

                if (value && costo > 0) {
                    this.$wire.set(jornalKey, true)
                } else if (!value) {
                    this.$wire.set(jornalKey, false)
                }
            })

            this.recalcularTodos()
        },

        toggleBonosDia(fecha, value) {
            const lista = this.$wire.get('listaPago') ?? {}

            Object.keys(lista).forEach(i => {
                const f = lista[i]?.[fecha]
                if (!f) return

                const bonoKey = `listaPago.${i}.${fecha}.bono_esta_pagado`
                const totalBono = parseFloat(f.total_bono ?? 0)

                if (value && totalBono > 0) {
                    this.$wire.set(bonoKey, true)
                } else if (!value) {
                    this.$wire.set(bonoKey, false)
                }
            })

            this.recalcularTodos()
        },
        recalcularTodos() {
            this.totales = {}; // 👈 no []
            let lista = this.$wire.get('listaPago') ?? {};
            Object.keys(lista).forEach(cuadrilleroId => this.recalcular(cuadrilleroId));
        },

        recalcular(cuadrilleroId) {
            let personal = this.$wire.get(`listaPago.${cuadrilleroId}`);

            if (!personal) return;

            let monto = 0, bono = 0;

            // recorrer solo las fechas del período
            this.periodo.forEach(fecha => {
                let f = personal[fecha];
                if (!f) return;

                if (f.esta_pagado) monto += parseFloat(f.costo_dia ?? 0);
                if (f.bono_esta_pagado) bono += parseFloat(f.total_bono ?? 0);
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