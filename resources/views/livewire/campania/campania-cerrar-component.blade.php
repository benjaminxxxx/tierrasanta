{{-- Modal "Cerrar campaña" (DESIGN.md): de dónde sale la fecha sugerida y, si no se puede cerrar, qué labores lo impiden --}}
@php
    $f = fn($d) => $d ? formatear_fecha($d) : '—';
    $input = 'h-9 rounded-[10px] border border-mist bg-paper px-3 text-sm text-ink focus:border-carmin focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btn2 = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btnPrincipal = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25 disabled:cursor-not-allowed disabled:opacity-50';
    $chip = 'rounded-[6px] px-1.5 py-0.5 text-[11px] font-medium';
@endphp
<div>
    <x-dialog-modal wire:model="mostrar" maxWidth="2xl">
        <x-slot name="title">Cerrar campaña {{ $resumen['nombre'] ?? '' }}</x-slot>
        <x-slot name="content">
            @if ($campaniaId)
                <div class="space-y-4 text-sm">
                    <div class="space-y-1 rounded-[10px] border border-mist bg-fog px-3 py-2">
                        <p class="text-ink"><span class="font-medium">Campo {{ $resumen['campo'] }}</span> · inicio {{ $resumen['inicio'] }}</p>
                        <p class="text-graphite">
                            <span class="font-medium text-ink">Cosecha:</span>
                            {{ $resumen['cosecha'] ?? 'no hay cosecha registrada (ingresos de cochinilla ni labores de cosecha).' }}
                        </p>
                        @if ($resumen['posterior'])
                            <p class="text-amber-800 dark:text-amber-300">
                                <i class="fa fa-triangle-exclamation"></i> Después de la cosecha: {{ $resumen['posterior'] }}. Esa labor ya es de la siguiente campaña.
                            </p>
                        @endif
                    </div>

                    {{-- De dónde sale la fecha sugerida --}}
                    @if ($detalleCosecha)
                        <details class="rounded-[10px] border border-mist" @if (count($detalleCosecha) <= 6) open @endif>
                            <summary class="cursor-pointer select-none px-3 py-2 text-xs font-medium text-graphite">
                                Registros que forman la cosecha ({{ count($detalleCosecha) }}) — si alguno está mal digitado, la fecha sugerida también
                            </summary>
                            <table class="w-full border-t border-hairline text-[13px]">
                                <tbody>
                                    @foreach ($detalleCosecha as $d)
                                        <tr class="border-t border-hairline first:border-t-0">
                                            <td class="whitespace-nowrap px-3 py-1.5 tabular-nums">{{ $f($d['fecha']) }}</td>
                                            <td class="px-3 py-1.5 text-graphite">{{ $d['tipo'] }}</td>
                                            <td class="px-3 py-1.5 text-ink">{{ $d['detalle'] }}</td>
                                            <td class="px-3 py-1.5 text-right text-xs text-pewter">{{ $d['registrado_por'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </details>
                    @endif

                    <p class="text-graphite">
                        La campaña termina con la cosecha: ciérrala el último día de cosecha, antes de cualquier otra labor
                        (preparado de tierra, limpieza, fumigación…).
                    </p>

                    <div class="flex flex-wrap items-end gap-3">
                        <label class="block">
                            <span class="mb-1 block font-medium text-ink">Fecha de cierre</span>
                            <input type="date" wire:model.live="fechaCierre" class="{{ $input }}" />
                        </label>
                        @if ($resumen['sugerida'] && $fechaCierre !== $resumen['sugerida'])
                            <button type="button" class="{{ $btn2 }}" wire:click="$set('fechaCierre', '{{ $resumen['sugerida'] }}')">
                                Usar la sugerida ({{ $f($resumen['sugerida']) }})
                            </button>
                        @endif
                    </div>
                    @error('fechaCierre')
                        <p class="rounded-[6px] bg-red-50 px-3 py-2 text-red-700 dark:bg-red-400/10 dark:text-red-400"><i class="fa fa-circle-exclamation"></i> {{ $message }}</p>
                    @enderror

                    {{-- Por qué no se puede cerrar con esta fecha --}}
                    @if ($bloqueo)
                        <div class="rounded-[10px] border border-red-200 dark:border-red-400/30">
                            <div class="space-y-1 bg-red-50 px-3 py-2 text-red-800 dark:bg-red-400/10 dark:text-red-300">
                                <p class="font-medium"><i class="fa fa-circle-exclamation"></i> No se puede cerrar con esta fecha</p>
                                <p class="text-xs">
                                    @if ($bloqueo['lado'] === 'despues')
                                        {{ $bloqueo['vecina'] }} empieza el {{ $f($bloqueo['vecina_inicio']) }}. Entre el cierre y ese día quedarían
                                    @else
                                        Entre el cierre de {{ $bloqueo['vecina'] }} y el inicio de esta campaña quedarían
                                    @endif
                                    {{ collect($bloqueo['filas'])->pluck('fecha')->unique()->count() }} día(s) con labores sin campaña: sus jornales y costos no tendrían a quién cargarse.
                                </p>
                            </div>
                            <div class="max-h-56 overflow-y-auto">
                                <table class="w-full text-[13px]">
                                    <thead class="sticky top-0 bg-fog text-left text-xs text-graphite">
                                        <tr>
                                            <th class="px-3 py-1.5 font-medium">Fecha</th>
                                            <th class="px-3 py-1.5 font-medium">Labor</th>
                                            <th class="px-3 py-1.5 text-right font-medium">Registros</th>
                                            <th class="px-3 py-1.5 font-medium">Registrado por</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($bloqueo['filas'] as $r)
                                            <tr class="border-t border-hairline">
                                                <td class="whitespace-nowrap px-3 py-1.5 tabular-nums">{{ $f($r['fecha']) }}</td>
                                                <td class="px-3 py-1.5 text-ink">
                                                    {{ $r['codigo'] }} · {{ $r['labor'] }}
                                                    @if ($r['cosecha'])
                                                        <span class="{{ $chip }} bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">cosecha</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-1.5 text-right tabular-nums text-graphite">{{ $r['registros'] }}</td>
                                                <td class="px-3 py-1.5 text-xs text-pewter">{{ $r['registrado_por'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="space-y-2 border-t border-hairline px-3 py-2 text-xs text-graphite">
                                <p class="font-medium text-ink">Qué se puede hacer:</p>
                                <ul class="list-disc space-y-1 pl-5">
                                    @if ($bloqueo['cerrar_el'])
                                        <li>
                                            Si esas labores son de esta campaña (por ejemplo, cosecha o poda mamá), ciérrala el
                                            <button type="button" class="font-medium text-ink underline underline-offset-2" wire:click="$set('fechaCierre', '{{ $bloqueo['cerrar_el'] }}')">{{ $f($bloqueo['cerrar_el']) }}</button>.
                                        </li>
                                    @endif
                                    @if ($bloqueo['lado'] === 'despues')
                                        <li>
                                            Si ya son de la siguiente, adelanta el inicio de {{ $bloqueo['vecina'] }} al día siguiente del cierre:
                                            <button type="button" class="font-medium text-ink underline underline-offset-2" wire:click="corregirSiguiente">corregir fechas de {{ $bloqueo['vecina'] }}</button>.
                                        </li>
                                    @endif
                                    <li>Si alguna labor está en el campo equivocado, corrígela en su registro diario y vuelve a intentar.</li>
                                </ul>
                            </div>
                        </div>
                    @endif

                    @if ($avisos)
                        <ul class="list-disc space-y-0.5 rounded-[6px] bg-amber-50 py-2 pl-7 pr-3 text-xs text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">
                            @foreach ($avisos as $aviso)
                                <li>{{ $aviso }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="{{ $btn2 }}" wire:click="$set('mostrar', false)">Cancelar</button>
            <button type="button" class="{{ $btnPrincipal }} ml-2" wire:click="confirmar" wire:loading.attr="disabled" wire:target="confirmar"
                @disabled(!$fechaCierre || $bloqueo || $errors->has('fechaCierre'))>
                <i class="fa fa-lock"></i> Cerrar campaña
            </button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading wire:target="confirmar" />
</div>
