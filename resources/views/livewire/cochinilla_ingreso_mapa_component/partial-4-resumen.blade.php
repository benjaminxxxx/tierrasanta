<div class="overflow-x-auto space-y-10">
    {{-- Tabla resumen por etapa --}}
    <x-table>
        <x-slot:thead>
            <x-tr>
                <x-th class="w-52">Etapa</x-th>
                <x-th class="text-right">Kilos</x-th>
                <x-th class="text-right">% del Total</x-th>
                <x-th class="text-right">Merma</x-th>
                <x-th class="text-right">% Merma</x-th>
            </x-tr>
        </x-slot:thead>
        <x-slot:tbody>
            <x-tr>
                <x-td class="font-medium">Ingreso Total</x-td>
                <x-td class="text-right">{{ number_format($resumen->total_kilos, 2) }} KI</x-td>
                <x-td class="text-right">100.00%</x-td>
                <x-td class="text-right">-</x-td>
                <x-td class="text-right">-</x-td>
            </x-tr>
            <x-tr>
                <x-td class="font-medium">Venteado</x-td>
                <x-td class="text-right">{{ number_format($resumen->total_venteado_kilos_ingresados, 2) }} KI</x-td>
                <x-td
                    class="text-right">{{ number_format($resumen->total_venteado_kilos_ingresados_porcentaje, 2) }}%</x-td>
                <x-td class="text-right text-red-500">{{ number_format($resumen->merma_ingreso_venteado, 2) }} KI</x-td>
                <x-td
                    class="text-right text-red-500">{{ number_format($resumen->merma_ingreso_venteado_porcentaje, 2) }}%</x-td>
            </x-tr>
            <x-tr>
                <x-td class="font-medium">Filtrado</x-td>
                <x-td class="text-right">{{ number_format($resumen->total_filtrado_kilos_ingresados, 2) }} KI</x-td>
                <x-td
                    class="text-right">{{ number_format($resumen->total_filtrado_kilos_ingresados_porcentaje, 2) }}%</x-td>
                <x-td class="text-right text-red-500">{{ number_format($resumen->merma_venteado_filtrado, 2) }}
                    KI</x-td>
                <x-td
                    class="text-right text-red-500">{{ number_format($resumen->merma_venteado_filtrado_porcentaje, 2) }}%</x-td>
            </x-tr>
            <x-tr class="bg-muted/50">
                <x-td class="font-medium">Merma Total (Ingreso → Filtrado)</x-td>
                <x-td class="text-right">-</x-td>
                <x-td class="text-right">-</x-td>
                <x-td class="text-right font-bold text-red-500">{{ number_format($resumen->merma_ingreso_filtrado, 2) }}
                    KI</x-td>
                <x-td
                    class="text-right font-bold text-red-500">{{ number_format($resumen->merma_ingreso_filtrado_porcentaje, 2) }}%</x-td>
            </x-tr>
        </x-slot:tbody>
    </x-table>

    {{-- Tabla de material útil --}}
    <h3 class="text-lg font-semibold mt-5">Material Útil</h3>
    <x-table>
        <x-slot:thead>
            <x-tr>
                <x-th>Etapa</x-th>
                <x-th class="text-right">Material Útil</x-th>
                <x-th class="text-right">% del Total</x-th>
                <x-th class="text-right">Composición</x-th>
            </x-tr>
        </x-slot:thead>
        <x-slot:tbody>
            <x-tr>
                <x-td class="font-medium">Venteado</x-td>
                <x-td
                    class="text-right text-emerald-600 font-semibold">{{ number_format($resumen->material_util_venteado, 2) }}
                    KI</x-td>
                <x-td class="text-right">{{ number_format($resumen->material_util_venteado_porcentaje, 2) }}%</x-td>
                <x-td class="text-right">Limpia + Polvillo</x-td>
            </x-tr>
            <x-tr>
                <x-td class="font-medium">Filtrado</x-td>
                <x-td
                    class="text-right text-emerald-600 font-semibold">{{ number_format($resumen->material_util_filtrado, 2) }}
                    KI</x-td>
                <x-td class="text-right">{{ number_format($resumen->material_util_filtrado_porcentaje, 2) }}%</x-td>
                <x-td class="text-right">1ra + 2da + 3ra</x-td>
            </x-tr>
        </x-slot:tbody>
    </x-table>

    {{-- Tabla de desglose por categorías --}}
    <h3 class="text-lg font-semibold mt-5">Desglose por Categorías</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Venteado --}}
        <div>
            <h4 class="text-md font-medium mb-2">Venteado</h4>
            <x-table>
                <x-slot:thead>
                    <x-tr>
                        <x-th>Categoría</x-th>
                        <x-th class="text-right">Kilos</x-th>
                        <x-th class="text-right">Porcentaje</x-th>
                        <x-th class="text-right">Utilidad</x-th>
                    </x-tr>
                </x-slot:thead>
                <x-slot:tbody>
                    <x-tr>
                        <x-td>Limpia</x-td>
                        <x-td class="text-right">{{ number_format($resumen->total_venteado_limpia, 2) }} KI</x-td>
                        <x-td
                            class="text-right">{{ number_format($resumen->porcentaje_venteado_limpia, 2) }}%</x-td>
                        <x-td class="text-right">
                            <span class="text-green-600 font-medium">Útil</span>
                        </x-td>
                    </x-tr>
                    <x-tr>
                        <x-td>Polvillo</x-td>
                        <x-td class="text-right">{{ number_format($resumen->total_venteado_polvillo, 2) }} KI</x-td>
                        <x-td
                            class="text-right">{{ number_format($resumen->porcentaje_venteado_polvillo, 2) }}%</x-td>
                        <x-td class="text-right">
                            <span class="text-green-600 font-medium">Útil</span>
                        </x-td>
                    </x-tr>
                    <x-tr>
                        <x-td>Basura</x-td>
                        <x-td class="text-right">{{ number_format($resumen->total_venteado_basura, 2) }} KI</x-td>
                        <x-td
                            class="text-right">{{ number_format($resumen->porcentaje_venteado_basura, 2) }}%</x-td>
                        <x-td class="text-right">
                            <span class="text-red-500 font-medium">No útil</span>
                        </x-td>
                    </x-tr>
                </x-slot:tbody>
            </x-table>
        </div>

        {{-- Filtrado --}}
        <div>
            <h4 class="text-md font-medium mb-2">Filtrado</h4>
            <x-table>
                <x-slot:thead>
                    <x-tr>
                        <x-th>Categoría</x-th>
                        <x-th class="text-right">Kilos</x-th>
                        <x-th class="text-right">Porcentaje</x-th>
                        <x-th class="text-right">Utilidad</x-th>
                    </x-tr>
                </x-slot:thead>
                <x-slot:tbody>
                    <x-tr>
                        <x-td>1ra</x-td>
                        <x-td class="text-right">{{ number_format($resumen->total_filtrado_primera, 2) }} KI</x-td>
                        <x-td
                            class="text-right">{{ number_format($resumen->porcentaje_filtrado_primera, 2) }}%</x-td>
                        <x-td class="text-right">
                            <span class="text-green-600 font-medium">Útil</span>
                        </x-td>
                    </x-tr>
                    <x-tr>
                        <x-td>2da</x-td>
                        <x-td class="text-right">{{ number_format($resumen->total_filtrado_segunda, 2) }} KI</x-td>
                        <x-td
                            class="text-right">{{ number_format($resumen->porcentaje_filtrado_segunda, 2) }}%</x-td>
                        <x-td class="text-right">
                            <span class="text-green-600 font-medium">Útil</span>
                        </x-td>
                    </x-tr>
                    <x-tr>
                        <x-td>3ra</x-td>
                        <x-td class="text-right">{{ number_format($resumen->total_filtrado_tercera, 2) }} KI</x-td>
                        <x-td
                            class="text-right">{{ number_format($resumen->porcentaje_filtrado_tercera, 2) }}%</x-td>
                        <x-td class="text-right">
                            <span class="text-green-600 font-medium">Útil</span>
                        </x-td>
                    </x-tr>
                    <x-tr>
                        <x-td>Piedra</x-td>
                        <x-td class="text-right">{{ number_format($resumen->total_filtrado_piedra, 2) }} KI</x-td>
                        <x-td
                            class="text-right">{{ number_format($resumen->porcentaje_filtrado_piedra, 2) }}%</x-td>
                        <x-td class="text-right">
                            <span class="text-red-500 font-medium">No útil</span>
                        </x-td>
                    </x-tr>
                    <x-tr>
                        <x-td>Basura</x-td>
                        <x-td class="text-right">{{ number_format($resumen->total_filtrado_basura, 2) }} KI</x-td>
                        <x-td
                            class="text-right">{{ number_format($resumen->porcentaje_filtrado_basura, 2) }}%</x-td>
                        <x-td class="text-right">
                            <span class="text-red-500 font-medium">No útil</span>
                        </x-td>
                    </x-tr>
                </x-slot:tbody>
            </x-table>
        </div>
    </div>
</div>
