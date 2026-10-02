{{-- Envíos de la caja de oficina con el detalle de cada fila. $envios: CajaOficinaConsulta::envios() --}}
@php
    $acciones = [
        'nuevo' => ['Nueva', 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'],
        'modificado' => ['Modificada', 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'],
        'eliminado' => ['Eliminada', 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'],
    ];
    $valor = fn($v) => $v === null || $v === '' ? '—' : (is_bool($v) ? ($v ? 'Sí' : 'No') : (is_float($v) ? number_format($v, 2) : $v));
@endphp
<div class="space-y-4">
    @forelse ($envios as $e)
        <div class="rounded-lg border border-border" wire:key="envio-{{ $e['id'] }}">
            <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 bg-muted">
                <div class="text-sm">
                    <b>Envío #{{ $e['id'] }}</b> · {{ $e['quien'] }} · <span class="tabular-nums">{{ $e['fecha'] }}</span> · {{ $e['cambios'] }} cambio(s)
                    @if ($e['nota'])
                        <span class="block text-xs text-muted-foreground">{{ $e['nota'] }}</span>
                    @endif
                </div>
                @if ($e['estado'] === 'anexado')
                    <span class="text-xs text-green-700 dark:text-green-400"><i class="fa fa-check"></i> Anexado {{ $e['anexado'] }}</span>
                @elseif (!empty($puedeAnexar) && $loop->first)
                    <x-button size="sm" wire:click="anexarEnvio({{ $e['id'] }})" wire:loading.attr="disabled"><i class="fa fa-link"></i> Anexar</x-button>
                @else
                    <span class="text-xs text-amber-700 dark:text-amber-400"><i class="fa fa-clock"></i> Pendiente de anexar</span>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <tbody>
                        @foreach ($e['detalles'] as $d)
                            <tr class="border-t border-border align-top">
                                <td class="p-2 whitespace-nowrap">
                                    <span class="px-1.5 py-0.5 rounded font-semibold {{ $acciones[$d['accion']][1] }}">{{ $acciones[$d['accion']][0] }}</span>
                                    @if ($d['es_inverso'])
                                        <span class="px-1.5 py-0.5 rounded font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">Inverso</span>
                                    @endif
                                </td>
                                <td class="p-2 whitespace-nowrap tabular-nums">{{ $d['fecha'] }}</td>
                                <td class="p-2 whitespace-nowrap">{{ $d['numero_caja'] }}</td>
                                <td class="p-2">
                                    <span class="font-medium">{{ $d['beneficiario'] }}</span> {{ $d['descripcion'] }}
                                    @foreach ($d['cambios'] as $c)
                                        <span class="block">
                                            <span class="text-muted-foreground">{{ $c['campo'] }}:</span>
                                            <span class="line-through text-red-700 dark:text-red-400">{{ $valor($c['antes']) }}</span>
                                            → <span class="text-green-700 dark:text-green-400">{{ $valor($c['despues']) }}</span>
                                        </span>
                                    @endforeach
                                    @if ($d['motivo'])
                                        <span class="block text-muted-foreground">Motivo: {{ $d['motivo'] }}</span>
                                    @endif
                                    @if ($d['resultado'] === 'omitido')
                                        <span class="block text-amber-700 dark:text-amber-400"><i class="fa fa-info-circle"></i> No se aplicó: {{ $d['resultado_nota'] }}</span>
                                    @endif
                                </td>
                                <td class="p-2 text-right whitespace-nowrap tabular-nums font-semibold {{ $d['importe'] < 0 ? 'text-red-700 dark:text-red-400' : '' }}">
                                    {{ number_format($d['importe'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <p class="text-sm text-muted-foreground">No hay envíos.</p>
    @endforelse
</div>
