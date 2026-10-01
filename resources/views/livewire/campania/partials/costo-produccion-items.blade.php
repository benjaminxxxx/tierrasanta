{{-- Partidas de costo operativo / costo fijo (sin subtotal de sección; $prefijo = '3.' numera 3.1, 3.2…) --}}
@php $n = fn($v, $dec = 2) => $v === null ? '—' : number_format((float) $v, $dec); $i = 0; @endphp
@foreach ($seccion['grupos'] as $grupo)
    @foreach ($grupo['items'] as $item)
        @php $i++; @endphp
        <tr>
            <td class="p-1 border border-border text-center text-xs">{{ $prefijo ? $prefijo . $i : '' }}</td>
            <td class="p-1 px-2 border border-border">{{ mb_strtoupper($item['item']) }}</td>
            <td class="p-1 px-2 border border-border"></td>
            <td class="p-1 px-2 border border-border text-right">{{ $n($item['costo_usd_ha']) }}</td>
            <td class="p-1 px-2 border border-border text-right text-muted-foreground">{{ $n($item['costo_soles']) }}</td>
        </tr>
    @endforeach
@endforeach
@if (empty($seccion['grupos']) && $seccion['titulo'] !== 'OTROS COSTOS')
    <tr>
        <td class="p-1 border border-border"></td>
        <td colspan="4" class="p-1 px-2 border border-border text-muted-foreground italic">Sin registros en la BDD de costos.</td>
    </tr>
@endif
