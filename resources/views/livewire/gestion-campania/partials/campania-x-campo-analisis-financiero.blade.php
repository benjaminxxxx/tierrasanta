<x-tr>
    <x-td>COSTO $</x-td>
    <x-td class="text-right">
        {{ formatear_numero($campania->analisis_financiero_costo) }}
    </x-td>
    <x-td></x-td>
    <x-td class="bg-muted space-y-4" rowspan="100%">
        @if ($campania)
            <div>
                <a href="{{ route('campania.costos', ['campaniaId' => $campania->id]) }}" target="_blank"
                    class="bg-lime-200 py-3 px-6 border border-border rounded">Revisar detalle financiero <i
                        class="fa fa-question"></i></a>
            </div>
            <x-flex>

                <x-button type="button"
                    @click="$wire.dispatch('sincronizarInformacionInfestacion',{campaniaId:{{ $campania->id }}})">
                    <i class="fa fa-sync"></i> SINCRONIZAR DESDE CONTABILIDAD
                </x-button>
            </x-flex>

        @endif
    </x-td>
</x-tr>
<x-tr>
    <x-td>PRECIO VENTA $</x-td>
    <x-td class="text-right">
        {{ formatear_numero($campania->analisis_financiero_precio_venta) }}
    </x-td>
</x-tr>
<x-tr>
    <x-td>VENTA TOTAL $</x-td>
    <x-td class="text-right">
        {{ formatear_numero($campania->analisis_financiero_venta_total) }}
    </x-td>
</x-tr>
<x-tr>
    <x-td>UTILIDAD O PERDIDA $</x-td>
    <x-td class="text-right">
        {{ formatear_numero($campania->analisis_financiero_utilidad) }}
    </x-td>
</x-tr>
<x-tr>
    <x-td>COSTO X KG</x-td>
    <x-td class="text-right">
        {{ formatear_numero($campania->analisis_financiero_costo_x_kilo) }}
    </x-td>
</x-tr>
<x-tr>
    <x-td class="text-left">% UTILIDAD</x-td>
    <x-td class="text-right">
        {{ formatear_numero($campania->analisis_financiero_porcentaje_utilidad) }}
    </x-td>
</x-tr>