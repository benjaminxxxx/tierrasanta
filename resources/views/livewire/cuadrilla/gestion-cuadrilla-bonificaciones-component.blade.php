<div class="space-y-4">
    <x-flex class="w-full justify-between">
        <x-flex class="my-3">
            <a href="{{ route('cuadrilla.panel') }}" class="font-bold text-lg">
                Gestión de cuadrilleros
            </a>
            <span>/</span>
            <x-title>
                Registro de Bonificaciones
            </x-title>
        </x-flex>
        <x-flex>
            <x-selector-dia wire:model.live="fecha" label="Seleccionar Fecha" class="w-auto" />
            <x-select label="Actividades realizadas" class="w-auto" wire:model.live="actividadSeleccionada"
                wire:key="select_actividad_{{ $fecha }}">
                <option value="">Seleccionar Actividad</option>
                @foreach ($actividades as $actividad)
                    <option value="{{ $actividad->id }}">
                        {{ 'Campo: ' . $actividad->campo . ' - Labor: ' . $actividad->codigo_labor . ' ' . $actividad->nombre_labor }}
                    </option>
                @endforeach
            </x-select>
        </x-flex>
    </x-flex>

    {{-- Días con el cálculo antiguo de destajo (descuenta horas del jornal): solo informa, se corrigen a mano --}}
    @if ($destajoAntiguo->isNotEmpty())
        <div x-data="{ abierto: false }" class="rounded-[10px] border border-amber-300 bg-amber-50 text-sm text-amber-900 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-300">
            <button type="button" @click="abierto = !abierto" class="flex w-full items-center justify-between gap-2 px-4 py-2.5 text-left">
                <span><i class="fa fa-clock-rotate-left"></i> <b>{{ $destajoAntiguo->count() }} actividad(es)</b> con el cálculo antiguo de destajo
                    (las horas no cobran jornal). Ábrelas y activa "Respeta el jornal" para corregirlas.</span>
                <i class="fa" :class="abierto ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
            <div x-show="abierto" x-cloak class="max-h-72 overflow-y-auto border-t border-amber-300/60 dark:border-amber-400/20">
                <table class="w-full text-[13px]">
                    <tbody>
                        @foreach ($destajoAntiguo as $d)
                            <tr class="border-b border-amber-200/60 last:border-0 dark:border-amber-400/10">
                                <td class="px-4 py-1.5 tabular-nums">{{ \Carbon\Carbon::parse($d->fecha)->format('d/m/Y') }}</td>
                                <td class="px-2 py-1.5">Campo {{ $d->campo }}</td>
                                <td class="px-2 py-1.5">{{ $d->codigo_labor }} · {{ $d->nombre_labor }}</td>
                                <td class="px-2 py-1.5 text-right tabular-nums">{{ $d->trabajadores }} trab.</td>
                                <td class="px-2 py-1.5 text-right tabular-nums">S/ {{ number_format($d->bono, 2) }}</td>
                                <td class="px-4 py-1.5 text-right">
                                    <button type="button" wire:click="abrirActividad('{{ \Carbon\Carbon::parse($d->fecha)->toDateString() }}', {{ $d->actividad_id }})"
                                        class="font-medium underline hover:no-underline">Abrir</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($actividadSeleccionada)
        <livewire:cuadrilla.gestion-cuadrilla-bonificaciones-detalle-component
            :actividadSeleccionada="$actividadSeleccionada" wire:key="actividad_{{ $actividadSeleccionada }}" />
    @else
        <x-card>
            <div class="w-full text-center">
                <x-label>Ninguna actividad seleccionada</x-label>
            </div>
        </x-card>
    @endif

    {{-- Gestionar errores de bonificación --}}
    <x-card class="space-y-3 !mb-10">
        <x-flex class="justify-between items-center">
            <x-h4>Consistencia de Bonificaciones</x-h4>
            <x-button wire:click="buscarInconsistencias" wire:loading.attr="disabled"
                wire:target="buscarInconsistencias">
                <i class="fa fa-search"></i>
                <span wire:loading.remove wire:target="buscarInconsistencias">Ver inconsistencias</span>
                <span wire:loading wire:target="buscarInconsistencias">Buscando...</span>
            </x-button>
        </x-flex>

        @if ($mostrarInconsistencias)
            @if (empty($inconsistencias))
                <x-label class="text-muted-foreground">No se encontraron inconsistencias 🎉</x-label>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="px-2 py-2 w-8"></th>
                                <th class="px-2 py-2">
                                    <input type="checkbox" wire:model.live="seleccionTodos" />
                                </th>
                                <th class="px-2 py-2 text-left">Tipo</th>
                                <th class="px-2 py-2 text-left">Trabajador</th>
                                <th class="px-2 py-2 text-left">Fecha</th>
                                <th class="px-2 py-2 text-right">Bono Actual</th>
                                <th class="px-2 py-2 text-right">Bono Correcto</th>
                                <th class="px-2 py-2 text-right">Diferencia</th>
                                <th class="px-2 py-2 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($inconsistencias as $index => $item)
                                <tr wire:key="inconsistencia-{{ $item['key'] }}"
                                    class="{{ $item['corregido'] ? 'opacity-50' : '' }} border-b border-border">
                                    <td class="px-2 py-2 text-center">
                                        <button wire:click="toggleExpander('{{ $item['key'] }}')"
                                            class="text-gray-400 hover:text-white transition">
                                            <i class="fa {{ $filaExpandida === $item['key'] ? 'fa-chevron-down' : 'fa-chevron-right' }}"></i>
                                        </button>
                                    </td>
                                    <td class="px-2 py-2">
                                        <input type="checkbox" wire:model="inconsistencias.{{ $index }}.seleccionado"
                                            @disabled($item['corregido']) />
                                    </td>
                                    <td class="px-2 py-2">{{ $item['tipo'] }}</td>
                                    <td class="px-2 py-2 font-medium">{{ $item['nombre'] }}</td>
                                    <td class="px-2 py-2">{{ $item['fecha'] }}</td>
                                    <td class="px-2 py-2 text-right">{{ number_format($item['total_actual'], 2) }}</td>
                                    <td class="px-2 py-2 text-right font-semibold text-green-400">{{ number_format($item['total_correcto'], 2) }}</td>
                                    <td class="px-2 py-2 text-right font-bold {{ $item['diferencia'] < 0 ? 'text-red-500' : 'text-orange-500' }}">
                                        {{ number_format($item['diferencia'], 2) }}
                                    </td>
                                    <td class="px-2 py-2 text-center">
                                        @if ($item['corregido'])
                                            <span class="text-green-600 text-xs">Corregido</span>
                                        @else
                                            <x-button variant="success" wire:click="corregirFila({{ $index }})">
                                                <i class="fa fa-check"></i> Corregir
                                            </x-button>
                                        @endif
                                    </td>
                                </tr>

                                {{-- Fila expandida con explicación detallada --}}
                                @if ($filaExpandida === $item['key'])
                                    <tr wire:key="expander-{{ $item['key'] }}" class="bg-gray-900/80">
                                        <td colspan="9" class="p-4 border-l-4 border-amber-500">
                                            <div class="space-y-2 text-xs">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-amber-400">Labores activas en Horas del Día:</span>
                                                    <span class="bg-gray-800 px-2 py-1 rounded text-gray-200 font-mono">{{ $item['labores_horas'] }}</span>
                                                </div>

                                                <div class="mt-2">
                                                    <p class="font-bold text-gray-300 mb-1">Desglose de Bonos Registrados:</p>
                                                    <div class="space-y-1">
                                                        @foreach ($item['bonos_registrados'] as $bono)
                                                            <div class="flex items-center justify-between p-2 rounded {{ $bono['valido'] ? 'bg-green-950/40 border border-green-800/40' : 'bg-red-950/40 border border-red-800/40' }}">
                                                                <div class="flex items-center gap-2">
                                                                    <i class="fa {{ $bono['valido'] ? 'fa-check text-green-400' : 'fa-times text-red-400' }}"></i>
                                                                    <span class="font-medium text-gray-200">{{ $bono['labor'] }}</span>
                                                                    <span class="text-gray-400">({{ $bono['motivo'] }})</span>
                                                                </div>
                                                                <span class="font-mono font-bold {{ $bono['valido'] ? 'text-green-400' : 'text-red-400 opacity-60 line-through' }}">
                                                                    S/ {{ number_format($bono['monto'], 2) }}
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-flex class="justify-end">
                    <x-button variant="secondary" wire:click="corregirSeleccionados">
                        <i class="fa fa-check-double"></i> Corregir seleccionados
                    </x-button>
                </x-flex>
            @endif
        @endif
    </x-card>

    <x-loading wire:loading />
</div>