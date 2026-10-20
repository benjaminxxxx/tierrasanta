<div class="space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <x-title>Mano de obra indirecta (FDM)</x-title>
            <x-subtitle>
                Todo lo que se gastó en FDM en el mes, de dónde sale cada sol y cómo se separa en blanco y negro.
                Al calcular se guarda en el costo mensual (reemplaza el valor escrito a mano) y se genera el Excel con el detalle.
            </x-subtitle>
        </div>
        <x-button variant="secondary" href="{{ route('costos.mensual') }}"><i class="fa fa-arrow-left"></i> Costo mensual</x-button>
    </div>

    @include('comun.selector-mes')

    @if ($calculo)
        @php
            $difiere = $guardado?->moi_calculado_en
                && (abs((float) $guardado->operativo_mano_obra_indirecta_blanco - $calculo['blanco']) > 0.01
                    || abs((float) $guardado->operativo_mano_obra_indirecta_negro - $calculo['negro']) > 0.01);
            $agrupar = ['planilla', 'cuadrilla'];
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
            <x-card class="lg:col-span-2">
                <p class="text-sm text-muted-foreground">Cálculo con los datos de hoy</p>
                <div class="flex flex-wrap gap-6 mt-1">
                    <div><p class="text-xs text-muted-foreground">Blanco</p><p class="text-2xl font-bold">S/ {{ number_format($calculo['blanco'], 2) }}</p></div>
                    <div><p class="text-xs text-muted-foreground">Negro</p><p class="text-2xl font-bold">S/ {{ number_format($calculo['negro'], 2) }}</p></div>
                    <div><p class="text-xs text-muted-foreground">Total</p><p class="text-2xl font-bold text-primary">S/ {{ number_format($calculo['total'], 2) }}</p></div>
                    <div class="self-center">
                        @if ($calculo['concluyente'])
                            <x-badge color="green">Concluyente</x-badge>
                        @else
                            <x-badge color="red">No concluyente</x-badge>
                        @endif
                    </div>
                </div>
            </x-card>
            <x-card>
                <p class="text-sm text-muted-foreground">Guardado en el costo mensual</p>
                @if ($guardado?->moi_calculado_en)
                    <p class="text-lg font-semibold">S/ {{ number_format((float) $guardado->operativo_mano_obra_indirecta_blanco + (float) $guardado->operativo_mano_obra_indirecta_negro, 2) }}</p>
                    <p class="text-xs text-muted-foreground">
                        Calculado el {{ $guardado->moi_calculado_en->format('d/m/Y H:i') }} por {{ $guardado->moiCalculadoPor?->name ?? '—' }}
                        · {{ $guardado->moi_concluyente ? 'concluyente' : 'no concluyente' }}
                    </p>
                    @if ($difiere)
                        <p class="text-xs text-amber-700 dark:text-amber-400 mt-1"><i class="fa fa-exclamation-triangle"></i> Los datos cambiaron desde el último cálculo: recalcula.</p>
                    @endif
                @elseif ($guardado && ((float) $guardado->operativo_mano_obra_indirecta_blanco || (float) $guardado->operativo_mano_obra_indirecta_negro))
                    <p class="text-lg font-semibold">S/ {{ number_format((float) $guardado->operativo_mano_obra_indirecta_blanco + (float) $guardado->operativo_mano_obra_indirecta_negro, 2) }}</p>
                    <p class="text-xs text-muted-foreground">Escrito a mano (nunca calculado). Al calcular se reemplaza.</p>
                @else
                    <p class="text-sm">Todavía no se calcula.</p>
                @endif
                <div class="flex flex-wrap gap-2 mt-3">
                    @if ($puedeCalcular)
                        <x-button wire:click="calcular" wire:confirm="Se pondrá al día la BDD de mano de obra y los kardex del mes, y se reemplazará la mano de obra indirecta del costo mensual. ¿Continuar?">
                            <i class="fa fa-calculator"></i> {{ $guardado?->moi_calculado_en ? 'Recalcular' : 'Calcular' }} y guardar
                        </x-button>
                    @endif
                    @if ($guardado?->moi_archivo)
                        <x-button variant="secondary" wire:click="descargar"><i class="fa fa-file-excel"></i> Excel</x-button>
                    @endif
                </div>
            </x-card>
        </div>

        @if ($calculo['avisos'])
            <div class="rounded-lg border {{ $calculo['concluyente'] ? 'border-amber-300 bg-amber-50 dark:bg-amber-900/20' : 'border-red-300 bg-red-50 dark:bg-red-900/20' }} p-3 text-sm">
                <p class="font-semibold mb-1">
                    <i class="fa fa-exclamation-triangle"></i>
                    {{ $calculo['concluyente'] ? 'Revisar' : 'El cálculo no es concluyente hasta resolver esto' }}
                </p>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($calculo['avisos'] as $a)
                        <li class="{{ $a['no_concluyente'] ? '' : 'text-muted-foreground' }}">{{ $a['texto'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-card>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th>Sección</x-th>
                        <x-th class="text-right">Blanco</x-th>
                        <x-th class="text-right">Negro</x-th>
                        <x-th class="text-right">Total</x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($calculo['secciones'] as $s)
                        <x-tr>
                            <x-td><a href="#moi-{{ $s['clave'] }}" class="hover:underline">{{ $s['titulo'] }}</a></x-td>
                            <x-td class="text-right">{{ number_format($s['blanco'], 2) }}</x-td>
                            <x-td class="text-right">{{ number_format($s['negro'], 2) }}</x-td>
                            <x-td class="text-right font-semibold">{{ number_format($s['total'], 2) }}</x-td>
                        </x-tr>
                    @endforeach
                    <x-tr class="font-bold border-t-2 border-border">
                        <x-td>Total mano de obra indirecta</x-td>
                        <x-td class="text-right">{{ number_format($calculo['blanco'], 2) }}</x-td>
                        <x-td class="text-right">{{ number_format($calculo['negro'], 2) }}</x-td>
                        <x-td class="text-right">{{ number_format($calculo['total'], 2) }}</x-td>
                    </x-tr>
                </x-slot>
            </x-table>
        </x-card>

        @foreach ($calculo['secciones'] as $s)
            <x-card id="moi-{{ $s['clave'] }}" wire:key="moi-{{ $anio }}-{{ $mes }}-{{ $s['clave'] }}">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="max-w-3xl">
                        <p class="font-semibold">{{ $s['titulo'] }}</p>
                        <p class="text-xs text-muted-foreground">{{ $s['explicacion'] }}</p>
                    </div>
                    <p class="font-bold">S/ {{ number_format($s['total'], 2) }}</p>
                </div>
                @if ($s['grupos'])
                    <div class="mt-2 divide-y divide-border">
                        @foreach ($s['grupos'] as $g)
                            <div x-data="{ abierto: false }">
                                <button type="button" @click="abierto = !abierto" class="w-full flex items-center gap-3 py-1.5 text-sm hover:bg-muted/50 text-left">
                                    <i class="fa fa-chevron-right text-xs transition-transform" :class="abierto && 'rotate-90'"></i>
                                    <span class="flex-1">{{ $g['nombre'] }} <span class="text-xs text-muted-foreground">({{ count($g['filas']) }}{{ $g['horas'] ? ' · ' . number_format($g['horas'], 1) . ' h' : '' }})</span></span>
                                    <span class="w-28 text-right text-muted-foreground">{{ number_format($g['blanco'], 2) }}</span>
                                    <span class="w-28 text-right text-muted-foreground">{{ number_format($g['negro'], 2) }}</span>
                                    <span class="w-28 text-right font-semibold">{{ number_format($g['total'], 2) }}</span>
                                </button>
                                <div x-show="abierto" x-cloak class="pl-6 pb-2 overflow-x-auto">
                                    @php
                                        // Planilla y cuadrilla: una fila por trabajador (el detalle por día está en el Excel)
                                        $filas = in_array($s['clave'], $agrupar, true)
                                            ? collect($g['filas'])->groupBy('detalle')->map(fn($x, $quien) => [
                                                'fecha' => $x->count() . ' día(s)', 'detalle' => $quien, 'horas' => $x->sum('horas'), 'cantidad' => null,
                                                'pct_blanco' => $x->first()['pct_blanco'], 'blanco' => $x->sum('blanco'), 'negro' => $x->sum('negro'),
                                                'costo' => $x->sum('costo'), 'nota' => $x->pluck('nota')->filter()->unique()->implode('; '),
                                            ])->sortByDesc('costo')->values()
                                            : collect($g['filas']);
                                    @endphp
                                    <table class="w-full text-xs">
                                        <thead class="text-muted-foreground">
                                            <tr>
                                                <th class="text-left py-1">{{ in_array($s['clave'], $agrupar, true) ? 'Días' : 'Fecha' }}</th>
                                                <th class="text-left">Detalle</th>
                                                <th class="text-right">Horas</th>
                                                <th class="text-right">Cant.</th>
                                                <th class="text-right">% blanco</th>
                                                <th class="text-right">Blanco</th>
                                                <th class="text-right">Negro</th>
                                                <th class="text-right">Costo</th>
                                                <th class="text-left pl-3">Nota</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($filas as $f)
                                                <tr class="border-t border-border">
                                                    <td class="py-0.5 whitespace-nowrap">{{ in_array($s['clave'], $agrupar, true) ? $f['fecha'] : ($f['fecha'] ? formatear_fecha($f['fecha']) : '') }}</td>
                                                    <td>{{ $f['detalle'] }}</td>
                                                    <td class="text-right">{{ $f['horas'] ? number_format($f['horas'], 2) : '' }}</td>
                                                    <td class="text-right">{{ $f['cantidad'] ? number_format($f['cantidad'], 3) : '' }}</td>
                                                    <td class="text-right">{{ number_format($f['pct_blanco'], 1) }} %</td>
                                                    <td class="text-right">{{ number_format($f['blanco'], 2) }}</td>
                                                    <td class="text-right">{{ number_format($f['negro'], 2) }}</td>
                                                    <td class="text-right font-semibold">{{ number_format($f['costo'], 2) }}</td>
                                                    <td class="pl-3 text-muted-foreground">{{ $f['nota'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-muted-foreground mt-2">Nada en FDM este mes.</p>
                @endif
            </x-card>
        @endforeach
    @endif

    <x-loading wire:loading wire:target="calcular,descargar,mesAnterior,mesSiguiente,mes,anio" />
</div>
