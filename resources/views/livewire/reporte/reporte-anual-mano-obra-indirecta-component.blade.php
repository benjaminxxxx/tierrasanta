<div>
    <x-card>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <x-icon><i class="fa-solid fa-people-carry-box"></i></x-icon>
                <div>
                    <x-title>Mano de obra indirecta (FDM)</x-title>
                    <x-subtitle>{{ $anio }} · lo gastado en FDM cada mes, como quedó guardado en el costo mensual</x-subtitle>
                </div>
            </div>
            @if ($pendientes)
                <x-badge color="red">{{ $pendientes }} mes(es) terminados sin calcular o no concluyentes</x-badge>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 rounded-xl overflow-hidden mb-4">
            <x-rda-metric label="Blanco del año" value-class="text-card-foreground" :pct="$total > 0 ? round($blanco / $total * 100, 1) : null">
                S/ {{ number_format($blanco, 2) }}
            </x-rda-metric>
            <x-rda-metric label="Negro del año" value-class="text-card-foreground" :pct="$total > 0 ? round($negro / $total * 100, 1) : null">
                S/ {{ number_format($negro, 2) }}
            </x-rda-metric>
            <x-rda-metric label="Total del año" value-class="text-primary">
                S/ {{ number_format($total, 2) }}
            </x-rda-metric>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-muted-foreground">
                    <tr class="border-b border-border">
                        <th class="text-left py-1.5">Mes</th>
                        <th class="text-right">Blanco</th>
                        <th class="text-right">Negro</th>
                        <th class="text-right">Total</th>
                        <th class="w-1/3 pl-4"></th>
                        <th class="text-left">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($meses as $m)
                        <tr class="border-b border-border {{ $m['estado'] === 'futuro' ? 'opacity-40' : '' }}">
                            <td class="py-1.5">
                                @if ($m['estado'] !== 'futuro')
                                    <a href="{{ route('costos.mano_obra_indirecta', ['anio' => $anio, 'mes' => $m['mes']]) }}" class="hover:underline">{{ $m['nombre'] }}</a>
                                @else
                                    {{ $m['nombre'] }}
                                @endif
                            </td>
                            <td class="text-right">{{ $m['total'] ? number_format($m['blanco'], 2) : '—' }}</td>
                            <td class="text-right">{{ $m['total'] ? number_format($m['negro'], 2) : '—' }}</td>
                            <td class="text-right font-semibold">{{ $m['total'] ? number_format($m['total'], 2) : '—' }}</td>
                            <td class="pl-4">
                                <div class="h-2 rounded-full bg-muted overflow-hidden flex">
                                    <div class="h-full bg-slate-400" style="width: {{ $m['blanco'] / $maximo * 100 }}%"></div>
                                    <div class="h-full bg-slate-800 dark:bg-slate-200" style="width: {{ $m['negro'] / $maximo * 100 }}%"></div>
                                </div>
                            </td>
                            <td class="text-xs whitespace-nowrap">
                                @switch($m['estado'])
                                    @case('concluyente') <span class="text-green-700 dark:text-green-400"><i class="fa-solid fa-check"></i> Calculado</span> @break
                                    @case('no_concluyente') <span class="text-red-600 dark:text-red-400"><i class="fa-solid fa-triangle-exclamation"></i> No concluyente</span> @break
                                    @case('manual') <span class="text-amber-700 dark:text-amber-400">Escrito a mano</span> @break
                                    @case('sin_calcular') <span class="text-muted-foreground">Sin calcular</span> @break
                                    @default <span class="text-muted-foreground">—</span>
                                @endswitch
                            </td>
                        </tr>
                    @endforeach
                    <tr class="font-bold">
                        <td class="py-1.5">Total</td>
                        <td class="text-right">{{ number_format($blanco, 2) }}</td>
                        <td class="text-right">{{ number_format($negro, 2) }}</td>
                        <td class="text-right">{{ number_format($total, 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="mt-2 text-xs text-muted-foreground"><span class="inline-block w-2 h-2 bg-slate-400"></span> blanco · <span class="inline-block w-2 h-2 bg-slate-800 dark:bg-slate-200"></span> negro · clic en el mes para ver de dónde sale o calcularlo</p>
    </x-card>
</div>
