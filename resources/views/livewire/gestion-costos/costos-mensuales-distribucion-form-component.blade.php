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
            <div class="relative border rounded-lg overflow-hidden dark:border-gray-500">
                <div class="max-h-[70vh] overflow-y-auto">
                    <table class="min-w-full border-collapse">
                        <thead class="sticky top-0 z-20 bg-white dark:bg-gray-900">
                            <tr>
                                <th rowspan="2">Campaña</th>
                                <th rowspan="2">Inicio</th>
                                <th rowspan="2">Fin</th>
                                <th rowspan="2" class="text-center">Días</th>
                                <th rowspan="2" class="text-center">%</th>

                                <th colspan="5" class="text-center bg-gray-50 dark:bg-indigo-600 dark:text-white">
                                    COSTOS FIJOS
                                </th>

                                <th colspan="2" class="text-center bg-gray-50 dark:bg-amber-600 dark:text-white">
                                    COSTOS OPERATIVOS
                                </th>
                            </tr>

                            <tr>
                                <th class="text-right">Administrativo</th>
                                <th class="text-right">Financiero</th>
                                <th class="text-right">Gastos Oficina</th>
                                <th class="text-right">Depreciaciones</th>
                                <th class="text-right">Costo Terreno</th>

                                <th class="text-right">Servicios Fundo</th>
                                <th class="text-right">Mano Obra Ind.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(is_array($distribucionCalculada) && count($distribucionCalculada) > 0)
                                @foreach($distribucionCalculada as $fila)
                                    <tr class="{{ $fila['porcentaje'] == 0 ? 'opacity-50' : '' }}">
                                        <td>{{ $fila['nombre_campania'] }}</td>
                                        <td>{{ $fila['fecha_inicio'] }}</td>
                                        <td>{{ $fila['fecha_fin'] ?? 'Abierta' }}</td>
                                        <td class="text-center">{{ $fila['dias_activos'] }}</td>
                                        <td class="text-center">
                                            {{ number_format($fila['porcentaje'] * 100, 2) }}%
                                        </td>

                                        <td class="text-right">{{ formatear_numero($fila['monto_fijo_administrativo']) }}</td>
                                        <td class="text-right">{{ formatear_numero($fila['monto_fijo_financiero']) }}</td>
                                        <td class="text-right">{{ formatear_numero($fila['monto_fijo_gastos_oficina']) }}</td>
                                        <td class="text-right">{{ formatear_numero($fila['monto_fijo_depreciaciones']) }}</td>
                                        <td class="text-right">{{ formatear_numero($fila['monto_fijo_costo_terreno']) }}</td>

                                        <td class="text-right">{{ formatear_numero($fila['monto_operativo_servicios_fundo']) }}
                                        </td>
                                        <td class="text-right">
                                            {{ formatear_numero($fila['monto_operativo_mano_obra_indirecta']) }}
                                        </td>
                                    </tr>
                                @endforeach

                            @endif
                        </tbody>
                        @if(is_array($totalesCalculados) && count($totalesCalculados) > 0)
                            <tfoot class="sticky bottom-0 z-20 bg-gray-100 dark:bg-gray-800">
                                <tr class="font-semibold">
                                    <td colspan="5" class="text-right">TOTAL DISTRIBUIDO</td>
                                    <td class="text-right">{{ formatear_numero($totalesCalculados['fijo_administrativo']) }}
                                    </td>
                                    <td class="text-right">{{ formatear_numero($totalesCalculados['fijo_financiero']) }}
                                    </td>
                                    <td class="text-right">{{ formatear_numero($totalesCalculados['fijo_gastos_oficina']) }}
                                    </td>
                                    <td class="text-right">{{ formatear_numero($totalesCalculados['fijo_depreciaciones']) }}
                                    </td>
                                    <td class="text-right">{{ formatear_numero($totalesCalculados['fijo_costo_terreno']) }}
                                    </td>
                                    <td class="text-right">
                                        {{ formatear_numero($totalesCalculados['operativo_servicios_fundo']) }}
                                    </td>
                                    <td class="text-right">
                                        {{ formatear_numero($totalesCalculados['operativo_mano_obra_indirecta']) }}
                                    </td>
                                </tr>

                                <tr class="font-semibold">
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
                                        {{ formatear_numero($totalesReales['operativo_servicios_fundo']) }}</td>
                                    <td class="text-right">
                                        {{ formatear_numero($totalesReales['operativo_mano_obra_indirecta']) }}</td>
                                </tr>

                                <tr class="font-semibold">
                                    <td colspan="5" class="text-right">DIFERENCIA</td>
                                    @foreach($totalesDiferencia as $diff)
                                        <td class="text-right {{ $diff != 0 ? 'text-red-600' : 'text-green-600' }}">
                                            {{ formatear_numero($diff) }}
                                        </td>
                                    @endforeach
                                </tr>
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
                Aprobar distribución
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