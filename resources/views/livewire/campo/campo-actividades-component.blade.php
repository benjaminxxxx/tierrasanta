{{-- Actividades por campo (DESIGN.md): códigos por campo y día con trabajadores, horas y lo aceptado al registrar. --}}
@php
    $dia = fn($d) => \Illuminate\Support\Carbon::parse($d)->locale('es')->isoFormat('ddd DD/MM/YYYY');
@endphp

<div class="space-y-4 font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">
    <div>
        <h1 class="text-2xl font-semibold text-ink">Actividades por campo</h1>
        <p class="text-sm text-graphite">
            Qué labores se hicieron en cada campo y día (planilla y cuadrilla). Cada campo debería tener un solo código por día:
            cuando tiene más, al registrar se pregunta si es correcto y aquí queda quién lo aceptó.
        </p>
    </div>

    {{-- Filtros --}}
    <div class="flex flex-wrap items-end gap-3 rounded-[10px] border border-mist bg-paper p-4">
        <div class="w-40"><x-fecha label="Desde" wire:model.live="desde" /></div>
        <div class="w-40"><x-fecha label="Hasta" wire:model.live="hasta" /></div>
        <div class="w-48"><x-select-campo wire:model.live="campo" label="Campo" /></div>
        <label class="flex h-9 items-center gap-2 text-sm text-ink">
            <input type="checkbox" wire:model.live="soloVarias" class="rounded border-mist text-carmin focus:ring-carmin/25">
            Solo campos con más de una actividad
        </label>
    </div>

    @if ($recortado)
        <p class="rounded-[10px] bg-amber-50 px-3 py-2 text-sm text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">
            <i class="fa fa-triangle-exclamation"></i> Se muestran como máximo {{ \App\Livewire\Campo\CampoActividadesComponent::DIAS_MAXIMO }} días:
            desde el {{ \Illuminate\Support\Carbon::parse($desdeEfectivo)->format('d/m/Y') }}.
        </p>
    @endif

    <div wire:loading.class="opacity-60" class="space-y-4">
        @forelse ($dias as $fecha => $campos)
            <div class="overflow-hidden rounded-[10px] border border-mist bg-paper">
                <div class="flex items-center justify-between border-b border-hairline bg-fog px-4 py-2">
                    <h2 class="text-sm font-semibold capitalize text-ink">{{ $dia($fecha) }}</h2>
                    <span class="text-xs text-graphite">
                        {{ $campos->count() }} campo(s) ·
                        {{ $campos->filter(fn($c) => count($c['actividades']) > 1)->count() }} con más de una actividad
                    </span>
                </div>
                <div class="divide-y divide-hairline">
                    @foreach ($campos as $c)
                        @php $varias = count($c['actividades']) > 1; @endphp
                        <div class="flex flex-col gap-2 px-4 py-2.5 md:flex-row md:items-start">
                            <div class="w-32 shrink-0">
                                <p class="text-sm font-semibold text-ink">Campo {{ $c['campo'] }}</p>
                                @if ($varias && $c['revisado'])
                                    @if (collect($c['actividades'])->every(fn($a) => $a['aceptada']))
                                        <p class="text-xs text-green-700 dark:text-green-400" title="{{ $c['aceptado_el'] ? 'Aceptado el ' . \Illuminate\Support\Carbon::parse($c['aceptado_el'])->format('d/m/Y H:i') : '' }}">
                                            <i class="fa fa-check"></i> Aceptado{{ $c['aceptado_por'] ? ' por ' . $c['aceptado_por'] : '' }}
                                        </p>
                                    @else
                                        <p class="text-xs text-amber-700 dark:text-amber-400"><i class="fa fa-triangle-exclamation"></i> Sin aceptar</p>
                                    @endif
                                @elseif ($varias)
                                    <p class="text-xs text-pewter">No se revisa</p>
                                @endif
                            </div>
                            <div class="flex flex-1 flex-wrap gap-2">
                                @foreach ($c['actividades'] as $a)
                                    <div class="min-w-[170px] rounded-[8px] border px-2.5 py-1.5 text-xs {{ $varias && $c['revisado'] && !$a['aceptada'] ? 'border-amber-200 bg-amber-50 dark:border-amber-400/30 dark:bg-amber-400/10' : 'border-hairline' }}">
                                        <p class="font-semibold text-ink">{{ $a['codigo'] }} <span class="font-normal text-graphite">· {{ \Illuminate\Support\Str::limit($a['labor'], 38) }}</span></p>
                                        <p class="mt-0.5 tabular-nums text-pewter">
                                            {{ $a['planilla'] + $a['cuadrilla'] }} trab.
                                            @if ($a['planilla']) · {{ $a['planilla'] }} planilla @endif
                                            @if ($a['cuadrilla']) · {{ $a['cuadrilla'] }} cuadrilla @endif
                                            · {{ number_format($a['horas'], 1) }} h
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-[10px] border border-mist bg-paper p-8 text-center text-sm text-graphite">
                No hay horas registradas en esas fechas{{ $campo ? " para el campo {$campo}" : '' }}.
            </div>
        @endforelse
    </div>
</div>
