<div>
    <x-dialog-modal wire:model.live="mostrar" maxWidth="full">
        <x-slot name="title">
            @if ($campania)
                Historia de la campaña {{ $campania->nombre_campania }} · campo {{ $campania->campo }}
            @endif
        </x-slot>
        <x-slot name="content">
            @if ($campania && $historia)
                @php
                    $tipos = [
                        'labor' => ['Mano de obra', 'fa-users', 'text-blue-600 dark:text-blue-400'],
                        'infestacion' => ['Infestación', 'fa-bug', 'text-purple-600 dark:text-purple-400'],
                        'cosecha' => ['Cosecha', 'fa-seedling', 'text-green-700 dark:text-green-400'],
                        'mama' => ['Mamá a otros campos', 'fa-share', 'text-green-700 dark:text-green-400'],
                        'recojo' => ['Recojo de infestadores', 'fa-box', 'text-amber-700 dark:text-amber-400'],
                        'recuperacion' => ['Recuperado', 'fa-undo', 'text-amber-700 dark:text-amber-400'],
                        'riego' => ['Riego', 'fa-tint', 'text-sky-600 dark:text-sky-400'],
                        'insumo' => ['Insumos', 'fa-flask', 'text-teal-600 dark:text-teal-400'],
                        'evaluacion' => ['Evaluaciones', 'fa-clipboard-check', 'text-indigo-600 dark:text-indigo-400'],
                        'venta' => ['Ventas', 'fa-dollar-sign', 'text-emerald-700 dark:text-emerald-400'],
                        'cierre' => ['Cierre', 'fa-flag-checkered', 'text-red-600 dark:text-red-400'],
                    ];
                    $eventos = collect($historia['eventos']);
                    $presentes = $eventos->pluck('tipo')->unique();
                    $visibles = $eventos->reject(fn($e) => in_array($e['tipo'], $ocultos, true));
                    $textoPlano = implode("\n", $historia['intro']) . "\n\n"
                        . $visibles->map(fn($e) => 'Día ' . $e['dia'] . ' (' . formatear_fecha($e['fecha']) . '): ' . $e['texto'])->implode("\n")
                        . "\n\n" . implode("\n", $historia['resumen']);
                @endphp
                <div class="space-y-4" x-data="{ copiado: false }">
                    <div class="rounded-lg border border-border bg-muted/40 p-3 text-sm space-y-1">
                        @foreach ($historia['intro'] as $linea)
                            <p>{{ $linea }}</p>
                        @endforeach
                        @foreach ($historia['resumen'] as $linea)
                            <p class="text-muted-foreground">{{ $linea }}</p>
                        @endforeach
                    </div>

                    @if ($historia['faltantes'])
                        <div class="rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 p-3 text-sm">
                            <p class="font-semibold text-amber-800 dark:text-amber-300 mb-1"><i class="fa fa-exclamation-triangle"></i> Falta registrar</p>
                            <ul class="list-disc pl-5 space-y-0.5">
                                @foreach ($historia['faltantes'] as $f)
                                    <li>{{ $f }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($tipos as $tipo => [$nombre, $icono, $color])
                            @if ($presentes->contains($tipo))
                                <button type="button" wire:click="alternar('{{ $tipo }}')"
                                    class="text-xs rounded-full border px-2.5 py-1 {{ in_array($tipo, $ocultos, true) ? 'opacity-40 line-through' : 'bg-card' }}">
                                    <i class="fa {{ $icono }} {{ $color }}"></i> {{ $nombre }} ({{ $eventos->where('tipo', $tipo)->count() }})
                                </button>
                            @endif
                        @endforeach
                        <button type="button" class="ml-auto text-xs rounded-md border px-2.5 py-1 hover:bg-muted"
                            @click="navigator.clipboard.writeText(@js($textoPlano)); copiado = true; setTimeout(() => copiado = false, 2000)">
                            <i class="fa" :class="copiado ? 'fa-check' : 'fa-copy'"></i> <span x-text="copiado ? 'Copiado' : 'Copiar texto'"></span>
                        </button>
                    </div>

                    <div class="space-y-5">
                        @forelse ($visibles->groupBy(fn($e) => substr($e['fecha'], 0, 7)) as $mes => $delMes)
                            <div wire:key="historia-mes-{{ $mes }}">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground mb-2">
                                    {{ \Illuminate\Support\Carbon::parse($mes . '-01')->translatedFormat('F Y') }}
                                </p>
                                <ol class="relative border-l border-border ml-2 space-y-3">
                                    @foreach ($delMes as $e)
                                        @php [$nombre, $icono, $color] = $tipos[$e['tipo']] ?? [$e['tipo'], 'fa-circle', '']; @endphp
                                        <li class="ml-5">
                                            <span class="absolute -left-2.5 flex h-5 w-5 items-center justify-center rounded-full bg-card border border-border">
                                                <i class="fa {{ $icono }} {{ $color }} text-[10px]"></i>
                                            </span>
                                            <p class="text-xs text-muted-foreground">Día {{ $e['dia'] }} · {{ formatear_fecha($e['fecha']) }}</p>
                                            <p class="text-sm">{{ $e['texto'] }}</p>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @empty
                            <p class="text-sm text-muted-foreground text-center py-6">No hay registros para contar en esta campaña.</p>
                        @endforelse
                    </div>
                </div>
            @elseif ($mostrar)
                <p class="text-sm text-muted-foreground">La campaña ya no existe.</p>
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrar', false)">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading wire:target="abrir,alternar" />
</div>
