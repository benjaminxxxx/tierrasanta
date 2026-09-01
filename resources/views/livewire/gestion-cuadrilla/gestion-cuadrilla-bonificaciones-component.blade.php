<div class="space-y-4">
    <x-flex class="w-full justify-between">
        <x-flex class="my-3">
            <a href="{{ route('cuadrilleros.gestion') }}" class="font-bold text-lg">
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

    @if ($actividadSeleccionada)

        <livewire:gestion-cuadrilla.gestion-cuadrilla-bonificaciones-detalle-component
            :actividadSeleccionada="$actividadSeleccionada" wire:key="actividad_{{ $actividadSeleccionada }}" />
    @else
        <x-card>
            <div class="w-full text-center">
                <x-label>Ninguna actividad seleccionada</x-label>
            </div>
        </x-card>
    @endif

    {{-- Gestionar errores de bonificacion --}}
    <x-card class="space-y-3">
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
                            <tr>
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
                                <tr wire:key="inconsistencia-{{ $item['tipo'] }}-{{ $item['registro_diario_id'] }}"
                                    class="{{ $item['corregido'] ? 'opacity-50' : '' }}">
                                    <td class="px-2 py-2">
                                        <input type="checkbox" wire:model="inconsistencias.{{ $index }}.seleccionado"
                                            @disabled($item['corregido']) />
                                    </td>
                                    <td class="px-2 py-2">{{ $item['tipo'] }}</td>
                                    <td class="px-2 py-2">{{ $item['nombre'] }}</td>
                                    <td class="px-2 py-2">{{ $item['fecha'] }}</td>
                                    <td class="px-2 py-2 text-right">{{ number_format($item['total_actual'], 2) }}</td>
                                    <td class="px-2 py-2 text-right">{{ number_format($item['total_correcto'], 2) }}</td>
                                    <td
                                        class="px-2 py-2 text-right font-bold {{ $item['diferencia'] < 0 ? 'text-red-500' : 'text-orange-500' }}">
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