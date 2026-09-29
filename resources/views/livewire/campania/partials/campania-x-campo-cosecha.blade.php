<x-tr>
    <x-td>Cosecha</x-td>
    <x-td class="text-right">
        {{ formatear_fecha($campania->infestacion_fecha) }}
    </x-td>
    <x-td></x-td>
    <x-td class="bg-gray-100 dark:bg-gray-600" rowspan="100%">
        @if ($campania)
            <div class="flex flex-wrap gap-4 space-y-2">
                <x-button type="button" @click="$wire.dispatch('editarCampania',{campaniaId:{{ $campania->id }},tab:'cosecha'})">
                    <i class="fa fa-edit"></i> EDITAR
                </x-button>
                <x-button type="button"
                    @click="$wire.dispatch('sincronizarInformacionInfestacion',{campaniaId:{{ $campania->id }}})">
                    <i class="fa fa-sync"></i> SINCRONIZAR DESDE INFESTACIÓN
                </x-button>
            </div>
        @endif

    </x-td>
</x-tr>

<x-tr>
    <x-td>Fecha cosecha o poda</x-td>
    <x-td class="text-right">{{ formatear_fecha($campania->cosch_fecha) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Tiempo de infestación a cosecha (días)</x-td>
    <x-td class="text-right">{{ $campania->cosch_tiempo_inf_cosch }}</x-td>
</x-tr>
<x-tr>
    <x-td>Tiempo de re-infestación a cosecha (días)</x-td>
    <x-td class="text-right">{{ $campania->cosch_tiempo_reinf_cosch }}</x-td>
</x-tr>
<x-tr>
    <x-td>Tiempo desde el inicio hasta la cosecha (días)</x-td>
    <x-td class="text-right">{{ $campania->cosch_tiempo_ini_cosch }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg fresca (cartón {{ $campania->cosch_destino_carton }})</x-td>
    <x-td class="text-right">{{ number_format($campania->cosch_kg_fresca_carton, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg fresca (tubo {{ $campania->cosch_destino_tubo }})</x-td>
    <x-td class="text-right">{{ number_format($campania->cosch_kg_fresca_tubo, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg fresca (malla {{ $campania->cosch_destino_malla }})</x-td>
    <x-td class="text-right">{{ number_format($campania->cosch_kg_fresca_malla, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg fresca (losa)</x-td>
    <x-td class="text-right">{{ number_format($campania->cosch_kg_fresca_losa, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg seca (cartón)</x-td>
    <x-td class="text-right">{{ number_format($campania->cosch_kg_seca_carton, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg seca (tubo)</x-td>
    <x-td class="text-right">{{ number_format($campania->cosch_kg_seca_tubo, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg seca (malla)</x-td>
    <x-td class="text-right">{{ number_format($campania->cosch_kg_seca_malla, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg seca (losa)</x-td>
    <x-td class="text-right">{{ number_format($campania->cosch_kg_seca_losa, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg seca vendida como madre</x-td>
    <x-td class="text-right">{{ number_format($campania->cosch_kg_seca_venta_madre, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Factor fresca/seca (cartón)</x-td>
    <x-td class="text-right">{{ number_format($campania->cosch_factor_fs_carton, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Factor fresca/seca (tubo)</x-td>
    <x-td class="text-right">{{ number_format($campania->cosch_factor_fs_tubo, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Factor fresca/seca (malla)</x-td>
    <x-td class="text-right">{{ number_format($campania->cosch_factor_fs_malla, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Factor fresca/seca (losa)</x-td>
    <x-td class="text-right">{{ number_format($campania->cosch_factor_fs_losa, 2) }}</x-td>
</x-tr>


<x-tr>
    <x-td>Total producción en cosecha o poda</x-td>
    <x-td class="text-right">{{ number_format($campania->cosch_total_cosecha, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Total producción de la campaña</x-td>
    <x-td class="text-right">{{ number_format($campania->cosch_total_campania, 2) }}</x-td>
</x-tr>