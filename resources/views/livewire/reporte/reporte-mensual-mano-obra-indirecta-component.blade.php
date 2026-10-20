<div>
    <x-card>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <x-icon><i class="fa-solid fa-people-carry-box"></i></x-icon>
                <div>
                    <x-title>Mano de obra indirecta (FDM)</x-title>
                    <x-subtitle>
                        {{ ucfirst(\Carbon\Carbon::createFromDate($anio, $mes, 1)->isoFormat('MMMM [de] YYYY')) }} · todo lo gastado en FDM
                    </x-subtitle>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if ($datos['origen'] === 'guardado')
                    <x-badge :color="$datos['concluyente'] ? 'green' : 'red'">{{ $datos['concluyente'] ? 'Concluyente' : 'No concluyente' }}</x-badge>
                @else
                    <x-badge color="yellow">Sin calcular · vista previa</x-badge>
                @endif
                <x-button variant="secondary" href="{{ route('costos.mano_obra_indirecta', ['anio' => $anio, 'mes' => $mes]) }}">
                    <i class="fa-solid fa-magnifying-glass-dollar"></i> Ver de dónde sale
                </x-button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 rounded-xl overflow-hidden mb-4">
            <x-rda-metric label="Blanco" value-class="text-card-foreground" :pct="$datos['total'] > 0 ? round($datos['blanco'] / $datos['total'] * 100, 1) : null">
                S/ {{ number_format($datos['blanco'], 2) }}
            </x-rda-metric>
            <x-rda-metric label="Negro" value-class="text-card-foreground" :pct="$datos['total'] > 0 ? round($datos['negro'] / $datos['total'] * 100, 1) : null">
                S/ {{ number_format($datos['negro'], 2) }}
            </x-rda-metric>
            <x-rda-metric label="Total" value-class="text-primary">
                S/ {{ number_format($datos['total'], 2) }}
            </x-rda-metric>
        </div>

        <div class="space-y-2">
            @foreach ($datos['secciones'] as $s)
                @continue(!$s['total'])
                @php $pct = $datos['total'] > 0 ? $s['total'] / $datos['total'] * 100 : 0; @endphp
                <div>
                    <div class="flex justify-between text-sm">
                        <span>{{ $s['titulo'] }}</span>
                        <span class="font-semibold">S/ {{ number_format($s['total'], 2) }} <span class="text-xs text-muted-foreground">({{ number_format($pct, 1) }} %)</span></span>
                    </div>
                    <div class="h-2 rounded-full bg-muted overflow-hidden flex" title="Blanco S/ {{ number_format($s['blanco'], 2) }} · Negro S/ {{ number_format($s['negro'], 2) }}">
                        <div class="h-full bg-slate-400" style="width: {{ $datos['total'] > 0 ? $s['blanco'] / $datos['total'] * 100 : 0 }}%"></div>
                        <div class="h-full bg-slate-800 dark:bg-slate-200" style="width: {{ $datos['total'] > 0 ? $s['negro'] / $datos['total'] * 100 : 0 }}%"></div>
                    </div>
                </div>
            @endforeach
            <p class="text-xs text-muted-foreground"><span class="inline-block w-2 h-2 bg-slate-400"></span> blanco · <span class="inline-block w-2 h-2 bg-slate-800 dark:bg-slate-200"></span> negro</p>
        </div>

        @php $pendientes = collect($datos['avisos'])->where('no_concluyente', true); @endphp
        @if ($pendientes->isNotEmpty())
            <div class="mt-4 rounded-lg border border-red-300 bg-red-50 dark:bg-red-900/20 p-3 text-xs">
                <p class="font-semibold mb-1"><i class="fa-solid fa-triangle-exclamation"></i> Falta para que sea concluyente</p>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($pendientes as $a)
                        <li>{{ $a['texto'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($guardado?->moi_calculado_en)
            <p class="mt-3 text-xs text-muted-foreground">Calculado el {{ $guardado->moi_calculado_en->format('d/m/Y H:i') }} por {{ $guardado->moiCalculadoPor?->name ?? '—' }}.</p>
        @endif
    </x-card>
</div>
