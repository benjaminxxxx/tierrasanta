{{-- Historial de un parámetro de planilla: agregar o corregir valores con motivo; confirma si afecta planillas generadas --}}
@php
    $btn2 = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btnPrincipal = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $mesTexto = fn($f) => $f ? ucfirst(\Carbon\Carbon::parse($f)->translatedFormat('M Y')) : null;
    $puede = auth()->user()?->can(\App\Constants\Permisos::PLANILLA_PARAMETRO_GESTIONAR);
@endphp
<div>
    <x-dialog-modal wire:model.live="abierto" maxWidth="3xl">
        <x-slot name="title">
            <div class="font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">
                <p class="text-lg font-semibold text-ink">{{ $info['nombre'] ?? '' }}
                    @if (!empty($info['unidad']))<span class="text-sm font-normal text-pewter">({{ $info['unidad'] }})</span>@endif
                </p>
                <p class="mt-0.5 text-xs font-normal text-pewter">{{ $info['ayuda'] ?? '' }}
                    @if (!empty($info['planillas'])) · {{ implode(', ', $info['planillas']) }} @endif
                </p>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-5 font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">
                {{-- Historial --}}
                <div class="overflow-x-auto rounded-[10px] border border-mist">
                    <table class="w-full text-[13px]">
                        <thead class="bg-fog text-left text-xs text-graphite">
                            <tr>
                                <th class="px-3 py-2 font-medium">Desde</th>
                                <th class="px-3 py-2 font-medium">Hasta</th>
                                <th class="px-3 py-2 text-right font-medium">Valor</th>
                                <th class="px-3 py-2 font-medium">Motivo</th>
                                <th class="px-3 py-2 font-medium">Usado en planilla</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($historial as $h)
                                <tr class="border-t border-hairline {{ $editId === $h['id'] ? 'bg-fog' : '' }}" wire:key="h-{{ $h['id'] }}">
                                    <td class="px-3 py-2 font-medium text-ink">{{ $mesTexto($h['desde']) }}</td>
                                    <td class="px-3 py-2 text-graphite" title="Se calcula solo: el día antes del siguiente valor">
                                        {{ $h['hasta'] ? \Carbon\Carbon::parse($h['hasta'])->format('d/m/Y') : 'Vigente' }}
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums text-ink">{{ $h['texto'] }}</td>
                                    <td class="px-3 py-2 text-graphite">
                                        {{ $h['motivo'] ?? '—' }}
                                        @if ($h['usuario'])<span class="block text-xs text-pewter">{{ $h['usuario'] }} · {{ $h['registrado'] }}</span>@endif
                                    </td>
                                    <td class="px-3 py-2">
                                        @forelse ($h['usado_en'] as $m)
                                            <span class="mb-1 inline-block rounded-[6px] bg-fog px-1.5 py-0.5 text-xs font-medium text-graphite">{{ $m }}</span>
                                        @empty
                                            <span class="text-xs text-pewter">—</span>
                                        @endforelse
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2 text-right">
                                        @if ($puede)
                                            <button type="button" wire:click="editar({{ $h['id'] }})" class="px-1 text-steel hover:text-ink" title="Corregir"><i class="fa fa-pen"></i></button>
                                            <button type="button" wire:click="eliminar({{ $h['id'] }})" wire:confirm="¿Eliminar el valor desde {{ $mesTexto($h['desde']) }}?"
                                                class="px-1 text-steel hover:text-red-700" title="Eliminar"><i class="fa fa-trash"></i></button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-6 text-center text-sm text-pewter">Todavía no tiene valores.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($afectados)
                    {{-- Confirmación: el cambio toca meses con planilla generada --}}
                    <div class="rounded-[10px] border border-amber-300 bg-amber-50 p-4 dark:border-amber-400/30 dark:bg-amber-400/10">
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                            <i class="fa fa-triangle-exclamation"></i> Este cambio afecta planillas ya generadas
                        </p>
                        <p class="mt-1 text-sm text-graphite">Si continúas se guarda y se vuelven a generar estas planillas, su PLAME y su Excel (los ajustes manuales se conservan):</p>
                        <ul class="mt-2 space-y-1.5 text-sm">
                            @foreach ($afectados as $a)
                                <li>
                                    <span class="font-semibold text-ink">{{ $a['periodo'] }}</span>
                                    <span class="text-pewter">· {{ collect($a['planillas'])->map(fn($p) => \App\Services\Planilla\Parametro\PlanillaParametroReglas::PLANILLAS[$p] ?? $p)->implode(' y ') }}</span>
                                    <span class="block text-graphite">{{ implode(' · ', $a['cambios']) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-3 flex justify-end gap-2">
                            <button type="button" wire:click="cancelarConfirmacion" class="{{ $btn2 }}">Cancelar</button>
                            <button type="button" wire:click="confirmar" wire:loading.attr="disabled" class="{{ $btnPrincipal }}">
                                <i class="fa fa-rotate"></i> Sí, guardar y regenerar
                            </button>
                        </div>
                    </div>
                @elseif ($puede)
                    {{-- Nuevo valor o corrección --}}
                    <div class="rounded-[10px] border border-mist p-4">
                        <p class="mb-3 text-sm font-semibold text-ink">{{ $editId ? 'Corregir valor' : 'Agregar nuevo valor' }}</p>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <x-label value="Valor {{ !empty($info['unidad']) ? '(' . $info['unidad'] . ')' : '' }}" />
                                <x-input type="number" step="0.0001" min="0" wire:model="valor" class="mt-1 w-full" />
                                <x-input-error for="valor" />
                            </div>
                            <div>
                                <x-label value="Vigente desde (mes)" />
                                <x-input type="month" wire:model="desde" class="mt-1 w-full" />
                                <x-input-error for="desde" />
                            </div>
                            <div class="sm:col-span-3">
                                <x-label value="Motivo del cambio" />
                                <x-input type="text" wire:model="motivo" maxlength="255" class="mt-1 w-full" placeholder="Ej.: Decreto Supremo N° 006-2024-TR / Resolución N° 2342342" />
                                <x-input-error for="motivo" />
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-pewter">Rige desde el día 1 del mes elegido hasta el mes antes del siguiente valor; la fecha "hasta" se calcula sola.</p>
                        <div class="mt-3 flex justify-end gap-2">
                            @if ($editId)
                                <button type="button" wire:click="limpiar" class="{{ $btn2 }}">Cancelar corrección</button>
                            @endif
                            <button type="button" wire:click="guardar" wire:loading.attr="disabled" class="{{ $btnPrincipal }}">
                                <i class="fa fa-save"></i> {{ $editId ? 'Guardar corrección' : 'Agregar' }}
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('abierto', false)">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>
</div>
