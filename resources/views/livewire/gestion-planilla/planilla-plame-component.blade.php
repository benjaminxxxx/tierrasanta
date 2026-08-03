<x-card>
    <x-table noScroll>
        <x-slot name="thead">
            <x-tr :level="1">
                <x-th rowspan="3">Nº</x-th>
                <x-th rowspan="3">NOMBRES</x-th>
                <x-th rowspan="3">SPP o SNP</x-th>

                <x-th colspan="2">INFORMACIÓN</x-th>

                <x-th colspan="8">SUSPENSIÓN PERFECTA</x-th>
                <x-th colspan="8">SUSPENSIÓN IMPERFECTA</x-th>

                <x-th colspan="3">DÍAS / HORAS</x-th>
                <x-th colspan="9">INGRESOS</x-th>
                <x-th colspan="5">DESCUENTOS</x-th>
                <x-th rowspan="3">NETO A PAGAR</x-th>
                <x-th colspan="4">APORTES DEL EMPLEADOR</x-th>
            </x-tr>

            <x-tr :level="2">
                {{-- INFORMACIÓN --}}
                <x-th rowspan="2">PENSIONISTA</x-th>
                <x-th rowspan="2">EDAD</x-th>

                {{-- SUSPENSIÓN PERFECTA: texto vertical, encabezados muy largos --}}
                <x-th class="[writing-mode:vertical-rl] rotate-180 align-bottom h-40">01 - S.P. Sanción disciplinaria</x-th>
                <x-th class="[writing-mode:vertical-rl] rotate-180 align-bottom h-40">02 - S.P. Ejercicio del derecho de huelga</x-th>
                <x-th class="[writing-mode:vertical-rl] rotate-180 align-bottom h-40">03 - Detención del trabajador, salvo el caso de comprobarse el delito</x-th>
                <x-th class="[writing-mode:vertical-rl] rotate-180 align-bottom h-40">04 - Inhabilitación administrativa o judicial</x-th>
                <x-th class="[writing-mode:vertical-rl] rotate-180 align-bottom h-40">05 - Permiso o licencia concedidos por el empleador</x-th>
                <x-th class="[writing-mode:vertical-rl] rotate-180 align-bottom h-40">06 - Caso fortuito o fuerza mayor</x-th>
                <x-th class="[writing-mode:vertical-rl] rotate-180 align-bottom h-40">08 - Falta no justificada</x-th>
                <x-th class="[writing-mode:vertical-rl] rotate-180 align-bottom h-40">
                    08 - Por temporada o intermitente
                    {{-- ⚠️ Código duplicado con la columna anterior en la tabla origen de SUNAT.
                         El modelo no tiene un campo separado para esta columna (sp_07 no existe,
                         sp_08 ya se usó arriba). Falta confirmar el código real antes de mapear. --}}
                </x-th>

                {{-- SUSPENSIÓN IMPERFECTA: texto vertical --}}
                <x-th class="[writing-mode:vertical-rl] rotate-180 align-bottom h-40">20 - Enfermedad o accidente (primeros veinte días)</x-th>
                <x-th class="[writing-mode:vertical-rl] rotate-180 align-bottom h-40">21 - Incapacidad temporal (invalidez, enfermedad)</x-th>
                <x-th class="[writing-mode:vertical-rl] rotate-180 align-bottom h-40">22 - Maternidad durante el descanso pre y post natal</x-th>
                <x-th class="[writing-mode:vertical-rl] rotate-180 align-bottom h-40">23 - Descanso vacacional</x-th>
                <x-th class="[writing-mode:vertical-rl] rotate-180 align-bottom h-40">24 - Licencia para desempeñar cargo cívico</x-th>
                <x-th class="[writing-mode:vertical-rl] rotate-180 align-bottom h-40">25 - Permiso y licencia para el desempeño de cargo</x-th>
                <x-th class="[writing-mode:vertical-rl] rotate-180 align-bottom h-40">26 - Licencia con goce de haber</x-th>
                <x-th class="[writing-mode:vertical-rl] rotate-180 align-bottom h-40">27 - Días compensados por horas trabajadas en sobretiempo</x-th>

                {{-- DÍAS / HORAS --}}
                <x-th rowspan="2">DÍAS NO LABORADOS</x-th>
                <x-th rowspan="2">DÍAS LABORADOS</x-th>
                <x-th rowspan="2">TOTAL HORAS</x-th>

                {{-- INGRESOS --}}
                <x-th rowspan="2">0117 - COMP. VACACIONAL</x-th>
                <x-th rowspan="2">0118 - REM. VAC.</x-th>
                <x-th rowspan="2">0121 - REM. JORN. BAS.</x-th>
                <x-th rowspan="2">0201 - ASIG. FAMILIAR</x-th>
                <x-th rowspan="2">REMUNERACIÓN BRUTA</x-th>
                <x-th rowspan="2">0312 - BONIF. EXT. TEMP.</x-th>
                <x-th rowspan="2">0314 - BETA 30 %</x-th>
                <x-th rowspan="2">0406 - GRATIF. FIEST. P. NAV.</x-th>
                <x-th rowspan="2">0904 - CTS</x-th>

                {{-- DESCUENTOS --}}
                <x-th rowspan="2">0601 - COMI. AFP %</x-th>
                <x-th rowspan="2">0605 - RENTA 5TA CAT. RET.</x-th>
                <x-th rowspan="2">0606 - PRIMA DE SEG. AFP</x-th>
                <x-th rowspan="2">0607 - SNP</x-th>
                <x-th rowspan="2">0608 - SPP APORT. OBL.</x-th>

                {{-- APORTES DEL EMPLEADOR --}}
                <x-th rowspan="2">0803 - PÓLIZA</x-th>
                <x-th rowspan="2">0804 - ESSALUD</x-th>
                <x-th rowspan="2">0805 - SCTR</x-th>
                <x-th rowspan="2">0810 - EPS</x-th>
            </x-tr>
        </x-slot>

        <x-slot name="tbody">
            @foreach ($empleados as $index => $empleado)
                <x-tr>
                    <x-td class="text-center">{{ $index + 1 }}</x-td>
                    <x-td class="whitespace-nowrap">{{ $empleado->nombres }}</x-td>
                    <x-td class="text-center whitespace-nowrap">{{ $empleado->sistema_pension }}</x-td>

                    {{-- INFORMACIÓN --}}
                    <x-td class="text-center">{{ $empleado->es_pensionista ? 'SÍ' : '' }}</x-td>
                    <x-td class="text-center">{{ $empleado->edad }}</x-td>

                    {{-- SUSPENSIÓN PERFECTA --}}
                    <x-td class="text-center">{{ fmt($empleado->sp_01, 0) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->sp_02, 0) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->sp_03, 0) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->sp_04, 0) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->sp_05, 0) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->sp_06, 0) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->sp_08, 0) }}</x-td>
                    <x-td class="text-center">
                        {{-- ⚠️ Sin campo confirmado, ver comentario en el header --}}
                    </x-td>

                    {{-- SUSPENSIÓN IMPERFECTA --}}
                    <x-td class="text-center">{{ fmt($empleado->si_20, 0) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->si_21, 0) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->si_22, 0) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->si_23, 0) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->si_24, 0) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->si_25, 0) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->si_26, 0) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->si_27, 0) }}</x-td>

                    {{-- DÍAS / HORAS --}}
                    <x-td class="text-center">{{ fmt($empleado->plame_dias_no_laborados, 2) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->plame_dias_laborados, 2) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->plame_total_horas, 2) }}</x-td>

                    {{-- INGRESOS --}}
                    <x-td class="text-center">{{ fmt($empleado->plame_0117_comp_vacacional) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->plame_0118_rem_vacacional) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->plame_0121_rem_jornal_basico) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->plame_0201_asignacion_familiar) }}</x-td>
                    <x-td class="text-center font-semibold">{{ fmt($empleado->plame_remuneracion_bruta) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->plame_0312_bonif_ext_temp) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->plame_0314_beta_30) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->plame_0406_gratif_fiestas_navidad) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->plame_0904_cts) }}</x-td>

                    {{-- DESCUENTOS --}}
                    <x-td class="text-center">{{ fmt($empleado->plame_descuento_0601_comision_afp_pct) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->plame_descuento_0605_renta_5ta_retenida) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->plame_descuento_0606_prima_seguro_afp) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->plame_descuento_0607_snp) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->plame_descuento_0608_spp_aporte_obligatorio) }}</x-td>

                    {{-- NETO A PAGAR --}}
                    <x-td class="text-center font-semibold">{{ fmt($empleado->plame_neto_a_pagar) }}</x-td>

                    {{-- APORTES DEL EMPLEADOR --}}
                    <x-td class="text-center">{{ fmt($empleado->plame_aporte_empleador_0803_poliza) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->plame_aporte_empleador_0804_essalud) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->plame_aporte_empleador_0805_sctr) }}</x-td>
                    <x-td class="text-center">{{ fmt($empleado->plame_aporte_empleador_0810_eps) }}</x-td>
                </x-tr>
            @endforeach
        </x-slot>
    </x-table>
</x-card>