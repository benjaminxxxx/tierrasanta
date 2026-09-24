@php
    $fIni = \Carbon\Carbon::parse($fechaInicio)->locale('es');
    $fFin = \Carbon\Carbon::parse($fechaFin)->locale('es');
    
    $rangoFechasTexto = $fIni->isSameDay($fFin)
        ? "del {$fIni->format('d')} de " . $fFin->translatedFormat('F')
        : "del {$fIni->format('d')} al {$fFin->format('d')} de " . $fFin->translatedFormat('F');

    $esExtras = $tipoPago === 'PAGO_CUADRILLA_EXTRAS';
@endphp

<div x-data="{
    grupos: @entangle('gruposDisponibles'),
    seleccionados: @entangle('seleccionados'),
    documento: @entangle('documento'),
    descripcion: @entangle('descripcion'),
    montoTotal: 0,
    rangoTexto: @js($rangoFechasTexto),
    esExtras: @js($esExtras),

    init() {
        this.recalcular();

        // Escuchar activamente los cambios cuando Livewire actualice los grupos
        this.$watch('grupos', () => {
            this.recalcular();
        });
        this.$watch('seleccionados', () => {
            this.recalcular();
        });
    },

    recalcular() {
        let gruposFiltrados = this.grupos.filter(g => this.seleccionados.includes(g.codigo_grupo));

        // 1. Recalcular Monto Total del detalle
        this.montoTotal = gruposFiltrados.reduce((sum, g) => sum + Number(g.monto_total), 0);

        // 2. Generar Descripción Dinámica
        if (gruposFiltrados.length === 0) {
            this.descripcion = '';
        } else if (gruposFiltrados.length === 1) {
            let gUnico = gruposFiltrados[0];
            this.descripcion = this.esExtras ?
                `CUADRILLA - TARDE - ${this.rangoTexto}` :
                `${gUnico.nombre_grupo} ${this.rangoTexto}`;
        } else {
            this.descripcion = this.esExtras ?
                `CUADRILLA EXTRAS - TARDE - ${this.rangoTexto}` :
                `Cuad. semanal Santa Rita ${this.rangoTexto}`;
        }
    }
}" class="space-y-6">

    {{-- Checkboxes de Selección de Grupos --}}
    <template x-if="grupos.length > 0">
        <x-card title="Seleccione los grupos a incluir en este pago">
            <div class="flex flex-wrap gap-4 items-center">
                <template x-for="grp in grupos" :key="grp.codigo_grupo">
                    <label
                        class="inline-flex items-center gap-2 cursor-pointer bg-base-200/50 hover:bg-base-200 px-3 py-1.5 rounded-lg border border-base-300 transition-colors">
                        <input type="checkbox" :value="grp.codigo_grupo" x-model="seleccionados" @change="recalcular()"
                            class="checkbox checkbox-xs checkbox-primary" />

                        <span
                            class="inline-flex items-center justify-center px-1.5 py-0.5 rounded text-[10px] font-bold"
                            :style="`background-color: ${grp.color_grupo}; color: #fff;`" x-text="grp.codigo_grupo">
                        </span>

                        <span class="text-xs font-medium" x-text="grp.nombre_grupo"></span>
                        <span class="text-xs text-base-500 font-semibold"
                            x-text="`(S/ ${Number(grp.monto_total).toFixed(2)})`"></span>
                    </label>
                </template>
            </div>
        </x-card>
    </template>

    {{-- Tabla de Detalle Único Calculado --}}
    <x-card>
        <x-table>
            <x-slot name="thead">
                <x-tr>
                    <x-th class="w-48">N° de documento</x-th>
                    <x-th>Descripción</x-th>
                    <x-th class="text-right w-44">Monto</x-th>
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                <x-tr wire:key="detalle-grupo-unico">
                    <x-td compact>
                        <x-input type="text" x-model="documento" placeholder="Opcional..." />
                    </x-td>
                    <x-td compact>
                        <x-input type="text" x-model="descripcion" />
                    </x-td>
                    <x-td compact class="text-right font-bold text-base text-primary">
                        S/ <span x-text="montoTotal.toFixed(2)"></span>
                    </x-td>
                </x-tr>
            </x-slot>
        </x-table>

        {{-- Botón de Confirmación --}}
        <x-flex class="justify-end mt-4">
            <x-button wire:click="confirmarRegistroPago" ::disabled="seleccionados.length === 0">
                <i class="fa fa-check"></i> Confirmar registro de pago
            </x-button>
        </x-flex>
    </x-card>

    {{-- Tabla Matriz con el desglose por cuadrillero --}}
    <x-card>
        @include('livewire.gestion-cuadrilla.partials.tabla-pago-cuadrilla', [
            'matriz' => $matrizPago,
        ])

    </x-card>

</div>
