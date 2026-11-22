{{-- Qué siembra se evalúa (DESIGN.md). Recibe: $tipoEvaluacion (poblacion|brotes), $evaluacionId, $puedeAgregar, $puedeCorregir --}}
@php
    $input = 'h-9 rounded-[10px] border border-mist bg-paper px-3 text-sm text-ink focus:border-carmin focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btn2 = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $otras = collect($siembras)->where('id', '!=', $siembraId)->values();
@endphp
<div class="rounded-[10px] border border-mist bg-paper p-3 text-sm">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="font-semibold text-ink">¿Qué siembra está evaluando?</p>
        @if ($campaniaCerrada)
            <span class="rounded-[6px] bg-fog px-2 py-1 text-xs font-medium text-graphite"><i class="fa fa-lock"></i> Campaña cerrada: solo consulta</span>
        @elseif ($puedeAgregar && !$agregandoSiembra)
            <button type="button" class="{{ $btn2 }}" wire:click="abrirAgregarSiembra"><i class="fa fa-plus"></i> Es otra siembra</button>
        @endif
    </div>

    <div class="mt-2 flex flex-wrap gap-2" role="radiogroup" aria-label="Siembra evaluada">
        @foreach ($siembras as $s)
            @php $activa = $siembraId === $s['id']; @endphp
            <button type="button" role="radio" aria-checked="{{ $activa ? 'true' : 'false' }}" wire:click="elegirSiembra({{ $s['id'] }})" wire:key="siembra-{{ $s['id'] }}"
                class="rounded-[10px] border px-3 py-1.5 text-left focus:outline-none focus:ring-[3px] focus:ring-carmin/25 {{ $activa ? 'border-carmin bg-carmin/5' : 'border-mist hover:bg-fog' }}">
                <span class="block font-medium text-ink">@if ($activa)<i class="fa fa-check text-xs text-carmin"></i> @endif{{ $s['nombre'] }}</span>
                <span class="block text-xs text-graphite">
                    {{ $s['fecha_siembra'] ? 'Sembrada el ' . formatear_fecha($s['fecha_siembra']) : 'Sin fecha de siembra' }} · hileras {{ $s['hilera_desde'] }}–{{ $s['hilera_hasta'] }}
                </span>
                <span class="block text-xs text-pewter">{{ $tipoEvaluacion === 'poblacion' ? ($s['poblacion'] ? 'Con evaluación' : 'Sin evaluación') : $s['brotes'] . ' evaluación(es)' }}</span>
            </button>
        @endforeach
    </div>

    @if (!$siembraId && count($siembras) > 1)
        <p class="mt-2 text-xs font-medium text-amber-700 dark:text-amber-400"><i class="fa fa-triangle-exclamation"></i> Esta campaña tiene {{ count($siembras) }} siembras: elige cuál vas a evaluar.</p>
    @endif

    @if ($agregandoSiembra && !$campaniaCerrada)
        <div class="mt-3 rounded-[10px] border border-hairline bg-fog p-3">
            <p class="text-sm font-medium text-ink">Agrega la siembra antes de evaluarla</p>
            <p class="text-xs text-graphite">Las hileras se reparten en partes iguales; puedes ajustarlas luego en el mapa de etapas.</p>
            <div class="mt-2 flex flex-wrap items-end gap-2">
                <label class="block"><span class="mb-1 block text-xs font-medium text-graphite">Fecha de siembra *</span>
                    <input type="date" wire:model="nuevaSiembraFecha" class="{{ $input }}" /></label>
                <label class="block min-w-[14rem] flex-1"><span class="mb-1 block text-xs font-medium text-graphite">Motivo (opcional)</span>
                    <input type="text" maxlength="200" wire:model="nuevaSiembraMotivo" placeholder="Ej. relleno a 2 m" class="{{ $input }} w-full" /></label>
                <button type="button" class="{{ $btn2 }}" wire:click="cancelarAgregarSiembra">Cancelar</button>
                <button type="button" class="{{ $btn2 }}" wire:click="guardarNuevaSiembra" wire:loading.attr="disabled"><i class="fa fa-check"></i> Agregar siembra</button>
            </div>
        </div>
    @endif

    @if ($evaluacionId && $puedeCorregir && !$campaniaCerrada && $otras->isNotEmpty())
        <div class="mt-3 flex flex-wrap items-end gap-2 border-t border-hairline pt-3">
            <label class="block"><span class="mb-1 block text-xs font-medium text-graphite">¿Esta evaluación es de otra siembra? Pásala a:</span>
                <select wire:model="siembraDestino" class="{{ $input }}">
                    <option value="">Elige la siembra…</option>
                    @foreach ($otras as $s)
                        <option value="{{ $s['id'] }}">{{ $s['etiqueta'] }}</option>
                    @endforeach
                </select></label>
            <button type="button" class="{{ $btn2 }}" wire:click="corregirSiembra('{{ $tipoEvaluacion }}', {{ (int) $evaluacionId }})"
                wire:confirm="¿Pasar esta evaluación a la siembra elegida? Los totales de la campaña se recalculan y queda en la auditoría.">
                <i class="fa fa-right-left"></i> Corregir siembra
            </button>
        </div>
    @endif
</div>
