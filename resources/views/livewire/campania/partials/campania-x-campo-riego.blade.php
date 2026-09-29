<x-tr>
    <x-td>Inicio de Riego</x-td>
    <x-td class="text-right">{{ $campania->riego_inicio }}</x-td>
    <x-td></x-td>
    <x-td class="bg-gray-100 dark:bg-gray-600" rowspan="11">
        @if ($campania)
            <div class="space-y-2">
                <x-button type="button" class="w-full"
                    @click="$wire.dispatch('editarRiegoCampania',{campaniaId:{{ $campania->id }}})">
                    <i class="fa fa-edit"></i> EDITAR DESCARGA POR HECTÁREA
                </x-button>
                <x-button type="button" class="w-full" wire:click="sincronizarRiegos">
                    <i class="fa fa-sync"></i> SINCRONIZAR DESDE RIEGOS
                </x-button>
            </div>

        @endif
    </x-td>
</x-tr>
<x-tr>
    <x-td>Fin de Riego</x-td>
    <x-td class="text-right">{{ $campania->riego_fin }}</x-td>
</x-tr>
<x-tr>
    <x-td>Descarga por hectárea (m3/há/hora)</x-td>
    <x-td></x-td>
    <x-td class="text-right">{{ $campania->riego_descarga_ha_hora }}</x-td>
</x-tr>
<x-tr>
    <x-td>Horas de riego de inicio a infestación</x-td>
    <x-td></x-td>
    <x-td class="text-right">{{ $campania->riego_hrs_ini_infest }}</x-td>
</x-tr>
<x-tr>
    <x-td>Metros cúbicos de inicio a infestación</x-td>
    <x-td></x-td>
    <x-td class="text-right">{{ $campania->riego_m3_ini_infest }}</x-td>
</x-tr>
<x-tr>
    <x-td>Horas de riego de infestación a reinfestación</x-td>
    <x-td></x-td>
    <x-td class="text-right">{{ $campania->riego_hrs_infest_reinf }}</x-td>
</x-tr>
<x-tr>
    <x-td>Metros cúbicos de infestación a reinfestación</x-td>
    <x-td></x-td>
    <x-td class="text-right">{{ $campania->riego_m3_infest_reinf }}</x-td>
</x-tr>
<x-tr>
    <x-td>Horas de riego de infestación o reinfestación a cosecha</x-td>
    <x-td></x-td>
    <x-td class="text-right">{{ $campania->riego_hrs_reinf_cosecha }}</x-td>
</x-tr>
<x-tr>
    <x-td>Metros cúbicos de infestación o reinfestación a cosecha</x-td>
    <x-td></x-td>
    <x-td class="text-right">{{ $campania->riego_m3_reinf_cosecha }}</x-td>
</x-tr>
<x-tr>
    <x-td>Horas de riego acumuladas</x-td>
    <x-td></x-td>
    <x-td class="text-right">{{ $campania->riego_hrs_acumuladas }}</x-td>
</x-tr>
<x-tr>
    <x-td>Acumulado x Há (m3)</x-td>
    <x-td></x-td>
    <x-td class="text-right">{{ $campania->riego_m3_acum_ha }}</x-td>
</x-tr>