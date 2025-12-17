<x-tr>
    <x-td>Fecha de Cosecha Madres</x-td>
    <x-td class="text-right">
        {{ formatear_fecha($campania->cosechamadres_fecha_cosecha) }}
    </x-td>
    <x-td></x-td>
    <x-td class="bg-gray-100 dark:bg-gray-600" rowspan="100%">
        @if ($campania)
            <div class="flex flex-wrap gap-4 space-y-2">
                <x-button type="button" @click="$wire.dispatch('editarCampania',{campaniaId:{{ $campania->id }},tab:'cosecha-madres'})">
                    <i class="fa fa-edit"></i> EDITAR
                </x-button>
            </div>
        @endif
    </x-td>
</x-tr>
<x-tr>
    <x-td>Tiempo de infestación a cosecha de madres</x-td>
    <x-td class="text-right">
        {{ $campania->cosechamadres_tiempo_infestacion_a_cosecha }}
    </x-td>
    <x-td></x-td>
</x-tr>
<x-tr>
    <x-td class="font-bold bg-amber-100 dark:bg-amber-600 dark:text-white">Destino total madres en fresco (kg)</x-td>
    <x-td class="text-right font-bold bg-amber-100 dark:bg-amber-600 dark:text-white">
        {{ number_format($campania->cosechamadres_destino_madres_fresco, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td>Para infestador cartón – campos</x-td>
    <x-td class="text-right">
        {{ number_format($campania->cosechamadres_infestador_carton_campos, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td>Para infestador tubo – campos</x-td>
    <x-td class="text-right">
        {{ number_format($campania->cosechamadres_infestador_tubo_campos, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td>Para infestador mallita – campos</x-td>
    <x-td class="text-right">
        {{ number_format($campania->cosechamadres_infestador_mallita_campos, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td>Para secado</x-td>
    <x-td class="text-right">
        {{ number_format($campania->cosechamadres_para_secado, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td>Para venta en fresco</x-td>
    <x-td class="text-right">
        {{ number_format($campania->cosechamadres_para_venta_fresco, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td class="font-bold bg-amber-100 dark:bg-amber-600 dark:text-white">Recuperación de madres en seco</x-td>
    <x-td class="text-right font-bold bg-amber-100 dark:bg-amber-600 dark:text-white">
        {{ number_format($campania->cosechamadres_recuperacion_madres, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td>De infestador cartón</x-td>
    <x-td class="text-right">
        {{ number_format($campania->cosechamadres_recuperacion_madres_seco_carton, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td>De infestador tubo</x-td>
    <x-td class="text-right">
        {{ number_format($campania->cosechamadres_recuperacion_madres_seco_tubo, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td>De infestador mallita</x-td>
    <x-td class="text-right">
        {{ number_format($campania->cosechamadres_recuperacion_madres_seco_mallita, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td>De secado</x-td>
    <x-td class="text-right">
        {{ number_format($campania->cosechamadres_recuperacion_madres_seco_secado, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td>De venta en fresco</x-td>
    <x-td class="text-right">
        {{ number_format($campania->cosechamadres_recuperacion_madres_seco_fresco, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td class="font-bold bg-amber-100 dark:bg-amber-600 dark:text-white">Conversión fresco - seco</x-td>
    <x-td class="text-right font-bold bg-amber-100 dark:bg-amber-600 dark:text-white">
        {{ number_format($campania->cosechamadres_conversion_fresco_seco, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td>De infestador cartón</x-td>
    <x-td class="text-right">
        {{ number_format($campania->cosechamadres_conversion_fresco_seco_carton, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td>De infestador tubo</x-td>
    <x-td class="text-right">
        {{ number_format($campania->cosechamadres_conversion_fresco_seco_tubo, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td>De infestador mallita</x-td>
    <x-td class="text-right">
        {{ number_format($campania->cosechamadres_conversion_fresco_seco_mallita, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td>De secado</x-td>
    <x-td class="text-right">
        {{ number_format($campania->cosechamadres_conversion_fresco_seco_secado, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>

<x-tr>
    <x-td>De venta en fresco</x-td>
    <x-td class="text-right">
        {{ number_format($campania->cosechamadres_conversion_fresco_seco_fresco, 2) }}
    </x-td>
    <x-td></x-td>
</x-tr>
