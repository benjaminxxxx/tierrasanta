{{-- Sección numerada de costos de producción (1.1 mano de obra, 1.2 maquinaria…). $seccion de CampaniaCostoProduccionConsulta::estructura --}}
@php $n = fn($v, $dec = 2) => $v === null ? '—' : number_format((float) $v, $dec); @endphp
<tr class="font-bold bg-muted/60">
    <td class="p-1.5 border border-border text-center">{{ $seccion['numero'] }}</td>
    <td class="p-1.5 border border-border">{{ $seccion['titulo'] }}</td>
    <td class="p-1.5 border border-border text-right">{{ $seccion['con_cantidad'] ? $n($seccion['cantidad_ha']) : '-' }}</td>
    <td class="p-1.5 border border-border text-right">{{ $n($seccion['costo_usd_ha']) }}</td>
    <td class="p-1.5 border border-border text-right">{{ $n($seccion['costo_soles']) }}</td>
</tr>
@if ($conGrupos)
    @foreach (array_values($seccion['grupos']) as $i => $grupo)
        <tr class="font-semibold">
            <td class="p-1.5 border border-border text-right text-xs">{{ $seccion['numero'] }}.{{ $i + 1 }}</td>
            <td class="p-1.5 border border-border {{ $grupo['nombre'] === \App\Services\Campania\CostoProduccion\CampaniaCostoProduccionReglas::SIN_MANO_OBRA ? 'text-amber-700 dark:text-amber-400' : '' }}">
                {{ $grupo['nombre'] }}
            </td>
            <td class="p-1.5 border border-border text-right">{{ $n($grupo['cantidad_ha']) }}</td>
            <td class="p-1.5 border border-border text-right">{{ $n($grupo['costo_usd_ha']) }}</td>
            <td class="p-1.5 border border-border text-right">{{ $n($grupo['costo_soles']) }}</td>
        </tr>
        @foreach ($grupo['items'] as $item)
            <tr>
                <td class="p-1 border border-border"></td>
                <td class="p-1 px-2 pl-6 border border-border">{{ $item['item'] }}</td>
                <td class="p-1 px-2 border border-border text-right">{{ $n($item['cantidad_ha']) }}</td>
                <td class="p-1 px-2 border border-border text-right">{{ $n($item['costo_usd_ha']) }}</td>
                <td class="p-1 px-2 border border-border text-right text-muted-foreground">{{ $n($item['costo_soles']) }}</td>
            </tr>
        @endforeach
    @endforeach
@else
    @foreach ($seccion['grupos'] as $grupo)
        @foreach ($grupo['items'] as $item)
            <tr>
                <td class="p-1 border border-border"></td>
                <td class="p-1 px-2 pl-6 border border-border">{{ $item['item'] }}</td>
                <td class="p-1 px-2 border border-border text-right">{{ $seccion['con_cantidad'] ? $n($item['cantidad_ha']) : '' }}</td>
                <td class="p-1 px-2 border border-border text-right">{{ $n($item['costo_usd_ha']) }}</td>
                <td class="p-1 px-2 border border-border text-right text-muted-foreground">{{ $n($item['costo_soles']) }}</td>
            </tr>
        @endforeach
    @endforeach
@endif
