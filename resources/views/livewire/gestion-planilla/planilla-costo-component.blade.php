<x-card>
    <x-table noScroll>
        <x-slot name="thead">
            <x-tr :level="1">
                <x-th>Nº</x-th>
                <x-th>NOMBRES</x-th>
                <x-th>SUELDO PAGADO</x-th>
                <x-th>APORTES DEL TRABAJADOR</x-th>
                <x-th>APORTES DEL EMPLEADOR</x-th>
                <x-th>COSTO TOTAL EMPRESA</x-th>
            </x-tr>
        </x-slot>

        <x-slot name="tbody">
            @foreach ($empleados as $index => $empleado)
                <x-tr>
                    <x-td class="text-center">{{ $index + 1 }}</x-td>
                    <x-td class="whitespace-nowrap">{{ $empleado->nombres }}</x-td>

                    {{-- SUELDO PAGADO --}}
                    <x-td class="text-center">
                        <div class="inline-flex items-center justify-center gap-1.5">
                            <span>{{ fmt($empleado->sueldo_pagado, 2) }}</span>
                            <button type="button" wire:click="mostrarExplicacionSueldo({{ $empleado->id }})"
                                class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-gray-500 bg-gray-200 rounded-full hover:bg-gray-300 hover:text-gray-700 transition-colors focus:outline-none"
                                title="Ver detalle del sueldo">
                                ?
                            </button>
                        </div>
                    </x-td>

                    {{-- APORTES TRABAJADOR --}}
                    <x-td class="text-center">
                        <div class="inline-flex items-center justify-center gap-1.5">
                            <span>{{ fmt($empleado->aportes_trabajador, 2) }}</span>
                            <button type="button" wire:click="mostrarExplicacionAportesTrabajador({{ $empleado->id }})"
                                class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-blue-500 bg-blue-100 rounded-full hover:bg-blue-200 transition-colors focus:outline-none"
                                title="Ver retenciones de ley">
                                ?
                            </button>
                        </div>
                    </x-td>

                    {{-- APORTES EMPLEADOR --}}
                    <x-td class="text-center">
                        <div class="inline-flex items-center justify-center gap-1.5">
                            <span>{{ fmt($empleado->aportes_empleador, 2) }}</span>
                            <button type="button" wire:click="mostrarExplicacionAportesEmpleador({{ $empleado->id }})"
                                class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-orange-500 bg-orange-100 rounded-full hover:bg-orange-200 transition-colors focus:outline-none"
                                title="Ver contribuciones patronales">
                                ?
                            </button>
                        </div>
                    </x-td>

                    {{-- COSTO TOTAL REAL --}}
                    <x-td class="text-center font-bold">
                        {{ fmt($empleado->costo_total_empresa, 2) }}
                    </x-td>
                </x-tr>
            @endforeach
        </x-slot>

        <x-slot name="tfoot">
            <x-tr class="font-bold bg-indigo-100 border-t-2 border-gray-300 dark:bg-indigo-900 dark:border-gray-700">
                <x-td colspan="2" class="text-right uppercase">TOTALES GENERALES:</x-td>
                <x-td class="text-center">
                    {{ fmt($empleados->sum('sueldo_pagado'), 2) }}
                </x-td>
                <x-td class="text-center">
                    {{ fmt($empleados->sum('aportes_trabajador'), 2) }}
                </x-td>
                <x-td class="text-center">
                    {{ fmt($empleados->sum('aportes_empleador'), 2) }}
                </x-td>
                <x-td class="text-center text-green-700 dark:text-green-400 font-black">
                    {{ fmt($empleados->sum('costo_total_empresa'), 2) }}
                </x-td>
            </x-tr>
        </x-slot>
    </x-table>
</x-card>