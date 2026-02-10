<div>
    <x-flex class="w-full justify-between mb-3 lg:mb-0">
        <x-flex class="my-3">
            <x-h3>
                Registro de Bonificaciones
            </x-h3>
        </x-flex>
        <x-button-a href="{{ route('cuadrilleros.gestion') }}">
            <i class="fa fa-arrow-left"></i> Volver a gestión de cuadrilleros
        </x-button-a>
    </x-flex>

    <div class="flex items-center justify-between mb-4">
        <!-- Botón para fecha anterior -->
        <x-button wire:click="fechaAnterior">
            <i class="fa fa-chevron-left"></i> <span class="hidden lg:inline-block">Fecha Anterior</span>
        </x-button>

        <!-- Input para seleccionar la fecha -->
        <x-input type="date" wire:model.live="fecha" class="text-center mx-2 !w-auto" />

        <!-- Botón para fecha posterior -->
        <x-button wire:click="fechaPosterior">
            <span class="hidden lg:inline-block">Fecha Posterior</span> <i class="fa fa-chevron-right"></i>
        </x-button>
    </div>

    <x-card>
        <x-h3>
            Actividades realizadas
        </x-h3>
        <x-group-field>
            <x-select wire:model.live="actividadSeleccionada" wire:key="select_actividad_{{ $fecha }}">
                <option value="">Seleccionar Actividad</option>
                @foreach ($actividades as $actividad)
                    <option value="{{ $actividad->id }}">
                        {{ 'Campo: ' . $actividad->campo . ' - Labor: ' . $actividad->codigo_labor . ' ' . $actividad->nombre_labor }}
                    </option>
                @endforeach
            </x-select>
        </x-group-field>
    </x-card>

    <x-card class="mt-4">
        @if ($actividadSeleccionada)
            <livewire:gestion-cuadrilla.gestion-cuadrilla-bonificaciones-detalle-component 
                        :actividadSeleccionada="$actividadSeleccionada" 
                        wire:key="actividad_{{ $actividadSeleccionada }}"/>
        @else
            <div class="w-full text-center">
                <x-label>Ninguna actividad seleccionada</x-label>
            </div>
        @endif
    </x-card>

    <x-loading wire:loading />
</div>