<x-tr class="">
    <x-th-header>DETALLE</x-th-header>
    <x-th-header class="text-right">KG</x-th-header>
    <x-th-header class="text-right">KG/HA</x-th-header>
    <x-td class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-600">
      
        @if ($campania)
            <x-button type="button" wire:click="generarResumenNutrientesCampaniasDesdeKardex">
                <i class="fa fa-sync"></i> ACTUALIZAR DESDE KARDEX
            </x-button>
        @endif
    </x-td>
</x-tr>
<x-tr>
    <x-td>Nitrógeno (N)</x-td>
    <x-td class="text-right">{{ number_format($campania->nutriente_nitrogeno_kg ?? 0, 2) }}</x-td>
    <x-td class="text-right">{{ number_format($campania->nutriente_nitrogeno_kg_ha ?? 0, 2) }}</x-td>
    <x-td class="bg-gray-100 dark:bg-gray-600" rowspan="8"></x-td>
</x-tr>
<x-tr>
    <x-td>Fósforo (P)</x-td>
    <x-td class="text-right">{{ number_format($campania->nutriente_fosforo_kg ?? 0, 2) }}</x-td>
    <x-td class="text-right">{{ number_format($campania->nutriente_fosforo_kg_ha ?? 0, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Potasio (K)</x-td>
    <x-td class="text-right">{{ number_format($campania->nutriente_potasio_kg ?? 0, 2) }}</x-td>
    <x-td class="text-right">{{ number_format($campania->nutriente_potasio_kg_ha ?? 0, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Calcio (Ca)</x-td>
    <x-td class="text-right">{{ number_format($campania->nutriente_calcio_kg ?? 0, 2) }}</x-td>
    <x-td class="text-right">{{ number_format($campania->nutriente_calcio_kg_ha ?? 0, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Magnesio (Mg)</x-td>
    <x-td class="text-right">{{ number_format($campania->nutriente_magnesio_kg ?? 0, 2) }}</x-td>
    <x-td class="text-right">{{ number_format($campania->nutriente_magnesio_kg_ha ?? 0, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Zinc (Zn)</x-td>
    <x-td class="text-right">{{ number_format($campania->nutriente_zinc_kg ?? 0, 2) }}</x-td>
    <x-td class="text-right">{{ number_format($campania->nutriente_zinc_kg_ha ?? 0, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Manganeso (Mn)</x-td>
    <x-td class="text-right">{{ number_format($campania->nutriente_manganeso_kg ?? 0, 2) }}</x-td>
    <x-td class="text-right">{{ number_format($campania->nutriente_manganeso_kg_ha ?? 0, 2) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Fierro (Fe)</x-td>
    <x-td class="text-right">{{ number_format($campania->nutriente_fierro_kg ?? 0, 2) }}</x-td>
    <x-td class="text-right">{{ number_format($campania->nutriente_fierro_kg_ha ?? 0, 2) }}</x-td>
</x-tr>
