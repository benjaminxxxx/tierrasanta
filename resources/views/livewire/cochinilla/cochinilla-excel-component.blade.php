<div class="mb-4">
    {{-- Barra del Excel oficial (un archivo por año: Ingreso, Venteado, Filtrado, Infestación y hojas que se guardan) --}}
    <x-card class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[16rem]">
            <p class="text-sm font-semibold"><i class="fa fa-file-excel text-green-600"></i> Excel oficial de cochinilla</p>
            <p class="text-xs text-muted-foreground">Un archivo por año con ingreso, venteado, filtrado e infestación. Descárgalo, llénalo y súbelo: verás qué cambia antes de guardar.</p>
        </div>
        <x-select wire:model.live="anio" label="Año" class="w-auto">
            @foreach ($anios as $a)
                <option value="{{ $a }}">{{ $a }}</option>
            @endforeach
        </x-select>
        <x-button variant="secondary" wire:click="exportar"><i class="fa fa-download"></i> Descargar {{ $anio }}</x-button>
        <x-button wire:click="abrirImportar"><i class="fa fa-upload"></i> Subir Excel</x-button>
    </x-card>

    <x-dialog-modal wire:model.live="mostrarImportar" maxWidth="full">
        <x-slot name="title">Subir Excel oficial de cochinilla {{ $anio }}</x-slot>
        <x-slot name="content">
            <div class="space-y-4">
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <x-label value="Archivo del año (COCHINILLA {{ $anio }}.xlsx)" />
                        <input type="file" wire:model="archivo" accept=".xlsx" class="block text-sm" />
                        <x-input-error for="archivo" />
                    </div>
                    <x-select wire:model.live="anio" label="Año del archivo" class="w-auto">
                        @foreach ($anios as $a)
                            <option value="{{ $a }}">{{ $a }}</option>
                        @endforeach
                    </x-select>
                    <span wire:loading wire:target="archivo,anio" class="text-sm text-muted-foreground"><i class="fa fa-spinner fa-spin"></i> Leyendo y comparando con el sistema…</span>
                </div>

                @if ($analisis)
                    @php
                        $etiquetas = ['nuevo' => ['Nuevos', 'text-green-600'], 'cambia' => ['Con cambios', 'text-amber-600'], 'igual' => ['Sin cambios', 'text-muted-foreground'], 'error' => ['Con errores', 'text-red-600']];
                        $pestanias = ['ingreso' => 'Ingreso (lotes)', 'venteado' => 'Venteado', 'filtrado' => 'Filtrado', 'infestacion' => 'Infestación', 'otras' => 'Otras hojas'];
                    @endphp
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        @foreach (['ingreso', 'venteado', 'filtrado', 'infestacion'] as $h)
                            <button type="button" wire:click="$set('pestania', '{{ $h }}')"
                                class="text-left rounded-lg border p-3 bg-card hover:bg-muted {{ $pestania === $h ? 'ring-2 ring-primary' : '' }}">
                                <p class="text-xs font-semibold uppercase text-muted-foreground">{{ $pestanias[$h] }}</p>
                                <p class="text-sm">
                                    @foreach ($etiquetas as $estado => [$texto, $color])
                                        @if (!empty($analisis['resumen'][$h][$estado]))
                                            <span class="{{ $color }} mr-2"><b>{{ $analisis['resumen'][$h][$estado] }}</b> {{ mb_strtolower($texto) }}</span>
                                        @endif
                                    @endforeach
                                    @if (empty($analisis['resumen'][$h]))
                                        <span class="text-muted-foreground">sin filas</span>
                                    @endif
                                </p>
                            </button>
                        @endforeach
                    </div>
                    <div class="flex gap-2 border-b border-border">
                        @foreach ($pestanias as $clave => $texto)
                            <button type="button" wire:click="$set('pestania', '{{ $clave }}')"
                                class="px-3 py-1.5 text-sm {{ $pestania === $clave ? 'border-b-2 border-primary font-semibold' : 'text-muted-foreground' }}">{{ $texto }}</button>
                        @endforeach
                    </div>

                    <div class="max-h-[50vh] overflow-y-auto">
                        @if ($pestania === 'ingreso')
                            @php $i = $analisis['ingreso']; @endphp
                            @foreach ($i['errores_sueltos'] as $e)
                                <p class="text-xs text-red-600"><i class="fa fa-times-circle"></i> {{ $e }}</p>
                            @endforeach
                            <table class="w-full text-sm mt-2">
                                <thead class="text-xs text-muted-foreground bg-muted sticky top-0"><tr><th class="text-left px-2 py-1">Estado</th><th class="text-left">Lote</th><th class="text-left">Fecha</th><th class="text-left">Campo</th><th class="text-left">Observación</th><th class="text-right">Sublotes</th><th class="text-right px-2">Kilos</th><th class="text-left">Detalle</th></tr></thead>
                                <tbody>
                                    @foreach (collect($i['lotes'])->sortBy(fn($l) => ['error' => 0, 'cambia' => 1, 'nuevo' => 2, 'igual' => 3][$l['estado']]) as $l)
                                        <tr class="border-t border-border align-top {{ $l['estado'] === 'igual' ? 'opacity-50' : '' }}">
                                            <td class="px-2 py-1"><span class="{{ $etiquetas[$l['estado']][1] }} text-xs font-semibold">{{ $etiquetas[$l['estado']][0] }}</span></td>
                                            <td class="font-semibold">{{ $l['lote'] }}</td>
                                            <td>{{ $l['fecha'] ? formatear_fecha($l['fecha']) : '—' }}</td>
                                            <td>{{ $l['campo'] }}</td>
                                            <td class="text-xs">{{ $l['observacion_texto'] }}</td>
                                            <td class="text-right">{{ count($l['sublotes']) }}</td>
                                            <td class="text-right px-2">{{ number_format($l['total'], 2) }}</td>
                                            <td class="text-xs">
                                                @foreach ($l['errores'] as $e)<p class="text-red-600">{{ $e }}</p>@endforeach
                                                @foreach ($l['cambios'] as $c)<p class="text-amber-700 dark:text-amber-400">{{ $c }}</p>@endforeach
                                                @foreach ($l['avisos'] as $a)<p class="text-muted-foreground">{{ $a }}</p>@endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if ($i['solo_sistema'])
                                <p class="text-xs text-muted-foreground mt-2"><b>{{ count($i['solo_sistema']) }}</b> lote(s) de {{ $anio }} están en el sistema y no en el Excel (no se borran):
                                    {{ collect($i['solo_sistema'])->pluck('lote')->implode(', ') }}</p>
                            @endif
                        @elseif (in_array($pestania, ['venteado', 'filtrado']))
                            @php $p = $analisis[$pestania]; @endphp
                            @foreach ($p['errores_sueltos'] as $e)
                                <p class="text-xs text-red-600">{{ $e }}</p>
                            @endforeach
                            <table class="w-full text-sm mt-2">
                                <thead class="text-xs text-muted-foreground bg-muted sticky top-0"><tr><th class="text-left px-2 py-1">Estado</th><th class="text-left">Lote</th><th class="text-right">Filas Excel</th><th class="text-right">Filas sistema</th><th class="text-right">Kilos</th><th class="text-left px-2">Detalle</th></tr></thead>
                                <tbody>
                                    @foreach (collect($p['grupos'])->sortBy(fn($g) => ['error' => 0, 'cambia' => 1, 'nuevo' => 2, 'igual' => 3][$g['estado']]) as $g)
                                        <tr class="border-t border-border align-top {{ $g['estado'] === 'igual' ? 'opacity-50' : '' }}">
                                            <td class="px-2 py-1"><span class="{{ $etiquetas[$g['estado']][1] }} text-xs font-semibold">{{ $etiquetas[$g['estado']][0] }}</span></td>
                                            <td class="font-semibold">{{ $g['lote'] }}</td>
                                            <td class="text-right">{{ count($g['filas']) }}</td>
                                            <td class="text-right">{{ $g['sistema'] }}</td>
                                            <td class="text-right">{{ number_format(collect($g['filas'])->sum('kilos'), 2) }}</td>
                                            <td class="text-xs px-2">
                                                @foreach ($g['errores'] as $e)<p class="text-red-600">{{ $e }}</p>@endforeach
                                                @foreach ($g['avisos'] ?? [] as $a)<p class="text-muted-foreground">{{ $a }}</p>@endforeach
                                                @if ($g['estado'] === 'cambia')<p class="text-amber-700 dark:text-amber-400">Se reemplazan las {{ $g['sistema'] }} fila(s) del sistema por las {{ count($g['filas']) }} del Excel.</p>@endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if ($p['solo_sistema'])
                                <p class="text-xs text-muted-foreground mt-2">Lotes de {{ $anio }} con {{ $pestania }} solo en el sistema (no se tocan): {{ implode(', ', array_keys($p['solo_sistema'])) }}</p>
                            @endif
                        @elseif ($pestania === 'infestacion')
                            @php $p = $analisis['infestacion']; @endphp
                            <table class="w-full text-sm">
                                <thead class="text-xs text-muted-foreground bg-muted sticky top-0"><tr><th class="text-left px-2 py-1">Estado</th><th class="text-left">Fecha</th><th class="text-left">Campo</th><th class="text-right">Filas Excel</th><th class="text-right">Filas sistema</th><th class="text-right">Kg madres</th><th class="text-left px-2">Detalle</th></tr></thead>
                                <tbody>
                                    @foreach (collect($p['grupos'])->sortBy(fn($g) => ['error' => 0, 'cambia' => 1, 'nuevo' => 2, 'igual' => 3][$g['estado']]) as $g)
                                        <tr class="border-t border-border align-top {{ $g['estado'] === 'igual' ? 'opacity-50' : '' }}">
                                            <td class="px-2 py-1"><span class="{{ $etiquetas[$g['estado']][1] }} text-xs font-semibold">{{ $etiquetas[$g['estado']][0] }}</span></td>
                                            <td>{{ $g['fecha'] ? formatear_fecha($g['fecha']) : '—' }}</td>
                                            <td>{{ $g['campo'] }}</td>
                                            <td class="text-right">{{ count($g['filas']) }}</td>
                                            <td class="text-right">{{ $g['sistema'] }}</td>
                                            <td class="text-right">{{ number_format(collect($g['filas'])->sum('kg_madres'), 2) }}</td>
                                            <td class="text-xs px-2">
                                                @foreach ($g['errores'] as $e)<p class="text-red-600">{{ $e }}</p>@endforeach
                                                @if ($g['estado'] === 'cambia')<p class="text-amber-700 dark:text-amber-400">Se reemplazan las {{ $g['sistema'] }} infestación(es) de ese campo y día por las {{ count($g['filas']) }} del Excel.</p>@endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if ($p['solo_sistema'])
                                <p class="text-xs text-muted-foreground mt-2">{{ count($p['solo_sistema']) }} campo/día con infestaciones solo en el sistema (no se tocan).</p>
                            @endif
                        @else
                            <p class="text-xs text-muted-foreground mb-2">Estas hojas viajan con el archivo y se conservan para la próxima descarga; el sistema todavía no las importa.</p>
                            @foreach ($analisis['extras'] as $nombre => $e)
                                <div class="mb-4">
                                    <p class="text-sm font-semibold">{{ $nombre }} <span class="text-xs text-muted-foreground">({{ $e['filas'] }} filas)</span></p>
                                    @if ($e['filas'])
                                        <div class="overflow-x-auto">
                                            <table class="text-xs">
                                                <thead class="text-muted-foreground"><tr>@foreach ($e['encabezados'] as $enc)<th class="px-2 text-left whitespace-nowrap">{{ $enc }}</th>@endforeach</tr></thead>
                                                <tbody>
                                                    @foreach ($e['muestra'] as $fila)
                                                        <tr class="border-t border-border">@foreach ($fila as $v)<td class="px-2 whitespace-nowrap">{{ is_array($v) ? formatear_fecha($v['fecha']) : (is_float($v) ? number_format($v, 2) : $v) }}</td>@endforeach</tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endif
            </div>
        </x-slot>
        <x-slot name="footer">
            @php
                $aplicar = $analisis ? collect(['ingreso', 'venteado', 'filtrado', 'infestacion'])->sum(fn($h) => ($analisis['resumen'][$h]['nuevo'] ?? 0) + ($analisis['resumen'][$h]['cambia'] ?? 0)) : 0;
            @endphp
            <div class="flex gap-2 items-center">
                @if ($analisis)
                    <span class="text-xs text-muted-foreground mr-2">Los que tienen errores no se guardan: corrígelos en el Excel y vuelve a subirlo.</span>
                @endif
                <x-button variant="secondary" wire:click="$set('mostrarImportar', false)">Cancelar</x-button>
                @if ($analisis)
                    <x-button wire:click="importar" wire:confirm="Se guardarán {{ $aplicar }} grupo(s) nuevos o con cambios del Excel {{ $anio }}. ¿Confirmar?">
                        <i class="fa fa-check"></i> Guardar {{ $aplicar }} cambio(s) y el archivo
                    </x-button>
                @endif
            </div>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading wire:target="exportar,importar" />
</div>
