<x-card>
    <x-table noScroll>
        <x-slot name="thead">
            <x-tr :level="1">
                <x-th rowspan="2">Nº</x-th>
                <x-th rowspan="2">NOMBRES</x-th>
                <x-th rowspan="2">SPP o SNP</x-th>

                <x-th colspan="4">SUELDO BRUTO</x-th>
                <x-th rowspan="2">SUELDO BRUTO</x-th>
                <x-th rowspan="2">DSCTO. A.F.P. (Prima de Seguro)</x-th>
                <x-th rowspan="2">SUELDO NETO</x-th>

                <x-th colspan="4">BENEFICIOS SOCIALES PROYECTADOS</x-th>

                <x-th colspan="4">APORTES DEL EMPLEADOR</x-th>

                <x-th rowspan="2">SUELDO NETO + BENF. SOCI. PROYEC.</x-th>
                <x-th rowspan="2">SUELDO BRUTO + BENF. SOCI. PROYEC. + APORT. EMPLE.</x-th>
                <x-th rowspan="2">JORNAL DIARIO</x-th>
                <x-th rowspan="2">COSTO HORA</x-th>

                <x-th rowspan="2"></x-th>

                <x-th rowspan="2">Nº</x-th>
                <x-th rowspan="2">NOMBRES</x-th>
                <x-th rowspan="2">DIFERENCIA O BONIFICACION</x-th>
                <x-th rowspan="2">SUELDO NETO TOTAL</x-th>
                <x-th rowspan="2">SUELDO BRUTO NEGRO</x-th>
                <x-th rowspan="2">SUELDO POR DIA</x-th>
                <x-th rowspan="2">SUELDO POR HORA</x-th>

                <x-th rowspan="2"></x-th>

                <x-th rowspan="2">Diferencia por h.</x-th>
                <x-th rowspan="2">Diferencia real</x-th>
            </x-tr>

            <x-tr :level="2">
                {{-- SUELDO BRUTO --}}
                <x-th>REMUNERACIÓN BÁSICA</x-th>
                <x-th>BONIF.</x-th>
                <x-th>ASIGNACIÓN FAMILIAR</x-th>
                <x-th>COMPEN.</x-th>

                {{-- BENEFICIOS SOCIALES PROYECTADOS --}}
                <x-th>CTS</x-th>
                <x-th>GRATIFICACIONES</x-th>
                <x-th>ESSALUD GRATIFICACIONES</x-th>
                <x-th>BETA 30 %</x-th>

                {{-- APORTES DEL EMPLEADOR --}}
                <x-th>ESSALUD</x-th>
                <x-th>VIDA LEY</x-th>
                <x-th>PENSIÓN SCTR</x-th>
                <x-th>ESSALUD EPS</x-th>
            </x-tr>
        </x-slot>

        <x-slot name="tbody">
            @foreach ($empleados as $index => $empleado)
                <x-tr>
                    {{-- SUELDO --}}
                    <x-td>{{ $index + 1 }}</x-td>

                    <x-td>
                        {{ $empleado->nombres }}
                    </x-td>

                    <x-td class="text-center">
                        {{ $empleado->sistema_pension }}
                    </x-td>

                    <x-td class="text-right">
                        {{ number_format($empleado->remuneracion_basica, 2) }}
                    </x-td>

                    <x-td class="text-right">
                        {{ number_format($empleado->bonificacion, 2) }}
                    </x-td>

                    <x-td class="text-right">
                        {{ number_format($empleado->asignacion_familiar, 2) }}
                    </x-td>

                    <x-td class="text-right">
                        {{ number_format($empleado->compensacion_vacacional, 2) }}
                    </x-td>

                    <x-td class="text-right">
                        {{ number_format($empleado->proyeccion_sueldo_bruto, 2) }}
                    </x-td>

                    <x-td>
                        {{ number_format($empleado->proyectado_dscto_afp_prima_seguro, 2) }}
                    </x-td>

                    <x-td>
                        {{ number_format($empleado->proyectado_sueldo_bruto_negro, 2) }}
                    </x-td>

                    {{-- BENEFICIOS --}}
                    <x-td>
                        {{ number_format($empleado->proyectado_cts, 2) }}
                    </x-td>

                    <x-td>
                        {{ number_format($empleado->proyectado_gratificaciones, 2) }}
                    </x-td>

                    <x-td>
                        {{ number_format($empleado->proyectado_essalud_gratificaciones, 2) }}
                    </x-td>

                    <x-td>
                        {{ number_format($empleado->proyectado_beta30, 2) }}
                    </x-td>

                    {{-- APORTES EMPLEADOR --}}
                    <x-td class="text-center">
                        {{ number_format($empleado->proyectado_essalud, 2) }}
                    </x-td>

                    <x-td class="text-center">
                        {{ number_format($empleado->proyectado_vida_ley, 2) }}
                    </x-td>

                    <x-td class="text-center">
                        {{ number_format($empleado->proyectado_pension_sctr, 2) }}
                    </x-td>

                    <x-td class="text-center">
                        {{ number_format($empleado->proyectado_essalud_eps, 2) }}
                    </x-td>

                    {{-- TOTALES --}}
                    <x-td class="text-right">
                        {{ number_format($empleado->proyectado_sueldo_neto_beneficios, 2) }}
                    </x-td>

                    <x-td class="text-right">
                        {{ number_format($empleado->proyectado_sueldo_bruto_beneficios_aportes, 2) }}
                    </x-td>

                    <x-td class="text-center">
                        {{ number_format($empleado->proyectado_jornal_diario, 2) }}
                    </x-td>

                    <x-td class="text-center">
                        {{ number_format($empleado->proyectado_costo_hora, 2) }}
                    </x-td>

                    <x-td></x-td>

                    {{-- SEGUNDO BLOQUE --}}
                    <x-td class="text-center">{{ $index + 1 }}</x-td>

                    <x-td>
                        {{ $empleado->nombres }}
                    </x-td>

                    <x-td class="text-center">
                        {{ number_format($empleado->proyectado_diferencia_bonificacion, 2) }}
                    </x-td>

                    <x-td class="text-center">
                        {{ number_format($empleado->proyectado_sueldo_neto_total, 2) }}
                    </x-td>

                    <x-td class="text-center">
                        {{ number_format($empleado->proyectado_sueldo_bruto_negro, 2) }}
                    </x-td>

                    <x-td class="text-center">
                        {{ number_format($empleado->proyectado_sueldo_por_dia, 2) }}
                    </x-td>

                    <x-td class="text-center">
                        {{ number_format($empleado->proyectado_sueldo_por_hora, 2) }}
                    </x-td>

                    <x-td></x-td>

                    <x-td>
                        {{ number_format($empleado->diferencia_por_hora, 2) }}
                    </x-td>

                    <x-td>
                        {{ number_format($empleado->diferencia_real, 2) }}
                    </x-td>
                </x-tr>
            @endforeach
        </x-slot>
    </x-table>
</x-card>