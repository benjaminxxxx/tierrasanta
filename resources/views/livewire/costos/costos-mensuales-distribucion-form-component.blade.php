<div x-data="formDistribucionCostosMensuales">
    <x-dialog-modal wire:model.live="mostrarFormDistribucionCostosMensuales" maxWidth="full">

        {{-- TITLE --}}
        <x-slot name="title">
            Distribución de costos mensuales
            <x-label>
                Se ha generado un prorrateo que debe ser revisado y aprobado, una vez haga la revisión correspondiente
                dele clic en Aprobar distribución
            </x-label>
        </x-slot>

        {{-- CONTENT --}}
        <x-slot name="content">
            <div class="relative border rounded-lg overflow-hidden border-border">
                <div class="max-h-[70vh] overflow-y-auto">
                    <table class="min-w-full border-collapse">
                        <thead class="sticky top-0 z-20 bg-muted">
                            <x-tr>
                                <x-th rowspan="2">Campo</x-th>
                                <x-th rowspan="2">Campaña</x-th>
                                <x-th rowspan="2">Inicio</x-th>
                                <x-th rowspan="2">Fin</x-th>
                                <x-th rowspan="2" class="text-center">Días</x-th>
                                <x-th rowspan="2" class="text-center">%</x-th>

                                <x-th colspan="5" class="text-center bg-gray-50 dark:bg-indigo-600 dark:text-white">
                                    COSTOS FIJOS
                                </x-th>

                                <x-th colspan="2" class="text-center bg-gray-50 dark:bg-amber-600 dark:text-white">
                                    COSTOS OPERATIVOS
                                </x-th>
                            </x-tr>

                            <x-tr>
                                <x-th class="text-right">Administrativo</x-th>
                                <x-th class="text-right">Financiero</x-th>
                                <x-th class="text-right">Gastos Oficina</x-th>
                                <x-th class="text-right">Depreciaciones</x-th>
                                <x-th class="text-right">Costo Terreno</x-th>

                                <x-th class="text-right">Servicios Fundo</x-th>
                                <x-th class="text-right">Mano Obra Ind.</x-th>
                            </x-tr>
                        </thead>
                        <tbody>
                            @if(is_array($distribucionCalculada) && count($distribucionCalculada) > 0)
                                @foreach($distribucionCalculada as $fila)
                                    <x-tr class="{{ $fila['porcentaje'] == 0 ? 'opacity-50' : '' }}">
                                        <x-td class="text-center">{{ $fila['campo'] }}</x-td>
                                        <x-td>{{ $fila['nombre_campania'] }}</x-td>
                                        <x-td>{{ formatear_fecha($fila['fecha_inicio']) }}</x-td>
                                        <x-td>
                                            {{ $fila['fecha_fin'] ? formatear_fecha($fila['fecha_fin']) : 'Abierta' }}
                                        </x-td>
                                        <x-td class="text-center">{{ $fila['dias_activos'] }}</x-td>
                                        <x-td class="text-center">
                                            {{ number_format($fila['porcentaje'] * 100, 2) }}%
                                        </x-td>

                                        <x-td
                                            class="text-right">{{ formatear_numero($fila['monto_fijo_administrativo']) }}</x-td>
                                        <x-td class="text-right">{{ formatear_numero($fila['monto_fijo_financiero']) }}</x-td>
                                        <x-td
                                            class="text-right">{{ formatear_numero($fila['monto_fijo_gastos_oficina']) }}</x-td>
                                        <x-td
                                            class="text-right">{{ formatear_numero($fila['monto_fijo_depreciaciones']) }}</x-td>
                                        <x-td
                                            class="text-right">{{ formatear_numero($fila['monto_fijo_costo_terreno']) }}</x-td>

                                        <x-td
                                            class="text-right">{{ formatear_numero($fila['monto_operativo_servicios_fundo']) }}
                                        </x-td>
                                        <x-td class="text-right">
                                            {{ formatear_numero($fila['monto_operativo_mano_obra_indirecta']) }}
                                        </x-td>
                                    </x-tr>
                                @endforeach

                            @endif
                        </tbody>
                        @if(is_array($totalesCalculados) && count($totalesCalculados) > 0)
                            <tfoot class="sticky bottom-0 z-20 bg-muted">
                                <x-tr class="font-semibold">
                                    <x-td colspan="6" class="text-right">TOTAL DISTRIBUIDO</x-td>
                                    <x-td
                                        class="text-right">{{ formatear_numero($totalesCalculados['fijo_administrativo']) }}
                                    </x-td>
                                    <x-td class="text-right">{{ formatear_numero($totalesCalculados['fijo_financiero']) }}
                                    </x-td>
                                    <x-td
                                        class="text-right">{{ formatear_numero($totalesCalculados['fijo_gastos_oficina']) }}
                                    </x-td>
                                    <x-td
                                        class="text-right">{{ formatear_numero($totalesCalculados['fijo_depreciaciones']) }}
                                    </x-td>
                                    <x-td
                                        class="text-right">{{ formatear_numero($totalesCalculados['fijo_costo_terreno']) }}
                                    </x-td>
                                    <x-td class="text-right">
                                        {{ formatear_numero($totalesCalculados['operativo_servicios_fundo']) }}
                                    </x-td>
                                    <x-td class="text-right">
                                        {{ formatear_numero($totalesCalculados['operativo_mano_obra_indirecta']) }}
                                    </x-td>
                                </x-tr>

                                <x-tr class="font-semibold">
                                    <td colspan="5" class="text-right">TOTAL REAL MES</td>
                                    <td class="text-right">{{ formatear_numero($totalesReales['fijo_administrativo']) }}
                                    </td>
                                    <td class="text-right">{{ formatear_numero($totalesReales['fijo_financiero']) }}</td>
                                    <td class="text-right">{{ formatear_numero($totalesReales['fijo_gastos_oficina']) }}
                                    </td>
                                    <td class="text-right">{{ formatear_numero($totalesReales['fijo_depreciaciones']) }}
                                    </td>
                                    <td class="text-right">{{ formatear_numero($totalesReales['fijo_costo_terreno']) }}</td>
                                    <td class="text-right">
                                        {{ formatear_numero($totalesReales['operativo_servicios_fundo']) }}
                                    </td>
                                    <td class="text-right">
                                        {{ formatear_numero($totalesReales['operativo_mano_obra_indirecta']) }}
                                    </td>
                                </x-tr>

                                <x-tr class="font-semibold">
                                    <td colspan="5" class="text-right">DIFERENCIA</td>
                                    @foreach($totalesDiferencia as $diff)
                                        <td class="text-right {{ $diff != 0 ? 'text-red-600' : 'text-green-600' }}">
                                            {{ formatear_numero($diff) }}
                                        </td>
                                    @endforeach
                                </x-tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </x-slot>

        {{-- FOOTER --}}
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarFormDistribucionCostosMensuales', false)">
                Cerrar
            </x-button>

            <x-button class="ml-2" wire:click="aprobarDistribucion">
                <i class="fas fa-check"></i> Aprobar distribución
            </x-button>
        </x-slot>


    </x-dialog-modal>
    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('formDistribucionCostosMensuales', () => ({
        init() {

        }
    }));
</script>
@endscript