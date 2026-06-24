<x-tr class="">
    <x-th colspan="3" class="bg-gray-50 text-xs text-gray-700 uppercase  dark:bg-gray-700 dark:text-gray-400">INFORMACIÓN GENERAL</x-th>
    <x-td class="bg-gray-100 dark:bg-gray-600 text-xs text-gray-700 uppercase">
        <x-button @click="$wire.dispatch('editarCampania',{campaniaId:{{ $campania->id }},tab:'general'})">
            <i class="fa fa-edit"></i> Editar
        </x-button>
    </x-td>
</x-tr>
<x-tr>
    <x-td>Lote:</x-td>
    <x-td>{{ $campania->campo }}</x-td>
    <x-td></x-td>
    <x-td class="bg-gray-100 dark:bg-gray-600" rowspan="10"></x-td>
</x-tr>

<x-tr>
    <x-td>Variedad de tuna:</x-td>
    <x-td>{{ $campania->variedad_tuna }}</x-td>
</x-tr>

<x-tr>
    <x-td>Campaña:</x-td>
    <x-td>{{ $campania->nombre_campania }}</x-td>
</x-tr>

<x-tr>
    <x-td>Área de la Campaña:</x-td>
    <x-td>{{ $campania->area }}</x-td>
</x-tr>

<x-tr>
    <x-td>Sistema de cultivo:</x-td>
    <x-td>{{ $campania->sistema_cultivo }}</x-td>
</x-tr>

<x-tr>
    <x-td>Pencas x Hectárea:</x-td>
    <x-td>{{ $campania->pencas_x_hectarea }}</x-td>
</x-tr>

<x-tr>
    <x-td>T.C.:</x-td>
    <x-td>{{ $campania->tipo_cambio }}</x-td>
</x-tr>

<x-tr>
    <x-td>Fecha de siembra:</x-td>
    <x-td>{{ formatear_fecha($campania->fecha_siembra) }}</x-td>
</x-tr>

<x-tr>
    <x-td>Fecha de inicio de Campaña:</x-td>
    <x-td>{{ formatear_fecha($campania->fecha_inicio) }}</x-td>
</x-tr>

<x-tr>
    <x-td>Fin de Campaña:</x-td>
    <x-td>{{ formatear_fecha($campania->fecha_fin) }}</x-td>
</x-tr>