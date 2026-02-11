<div>
    <x-flex class="w-full justify-between">
        <x-flex class="my-3">
            <a href="{{ route('cuadrilleros.gestion') }}" class="font-bold text-lg">
                Gestión de cuadrilleros
            </a>
            <span>/</span>
            <x-h3>
                Registro de Bonificaciones
            </x-h3>
        </x-flex>
        <x-flex>
            <x-selector-dia wire:model.live="fecha" label="Seleccionar Fecha" class="w-auto" />
            <x-select label="Actividades realizadas" class="w-auto" wire:model.live="actividadSeleccionada" wire:key="select_actividad_{{ $fecha }}">
                <option value="">Seleccionar Actividad</option>
                @foreach ($actividades as $actividad)
                    <option value="{{ $actividad->id }}">
                        {{ 'Campo: ' . $actividad->campo . ' - Labor: ' . $actividad->codigo_labor . ' ' . $actividad->nombre_labor }}
                    </option>
                @endforeach
            </x-select>
        </x-flex>
    </x-flex>

    <x-card class="mt-4">
        @if ($actividadSeleccionada)
            <livewire:gestion-cuadrilla.gestion-cuadrilla-bonificaciones-detalle-component :actividadSeleccionada="$actividadSeleccionada"
                wire:key="actividad_{{ $actividadSeleccionada }}" />
        @else
            <div class="w-full text-center">
                <x-label>Ninguna actividad seleccionada</x-label>
            </div>
        @endif
    </x-card>

    <x-loading wire:loading />
</div>
