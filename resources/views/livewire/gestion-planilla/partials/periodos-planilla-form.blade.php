<x-dialog-modal wire:model="mostrarFormularioPeriodo" maxWidth="lg">
    <x-slot name="title">
        {{ $periodoId ? 'Editar Período' : 'Nuevo Período' }}
    </x-slot>

    <x-slot name="content">
        <form wire:submit.prevent="guardarPeriodo" id="frmPeriodo" class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Empleado --}}
            <x-group-field>
                {{-- los campos searchables esperan id name atributos --}}
                <x-label for="periodo.plan_empleado_id" value="Selecciona un trabajador" />
                <x-searchable-select :options="$empleados" search-placeholder="Escriba el nombre del trabajador"
                    wire:model="periodo.plan_empleado_id" />
                <x-input-error for="periodo.plan_empleado_id" />
            </x-group-field>

            {{-- Tipo de Período --}}
            <x-select label="Tipo de Período" wire:model="periodo.codigo" placeholder="Selecciona un tipo"
                fullWidth="true">
                @foreach ($tiposAsistencia as $tipoAsistencia)
                    <option value="{{ $tipoAsistencia->codigo }}">{{ $tipoAsistencia->descripcion }}</option>
                @endforeach
            </x-select>

            {{-- Fecha Inicio --}}
            <x-input type="date" label="Fecha de Inicio" wire:model="periodo.fecha_inicio" />

            {{-- Fecha Fin --}}
            <x-input type="date" label="Fecha de Fin" wire:model="periodo.fecha_fin" />

            {{-- Observaciones --}}
            <div class="col-span-2">
                <x-textarea label="Observaciones" wire:model="periodo.observaciones" rows="3"
                    placeholder="Agrega notas o comentarios adicionales..." />
            </div>
            
            @if ($periodoId)
                <div class="col-span-2">
                    <x-textarea label="Motivo de la modificación" wire:model="periodo.motivo_modificacion"
                        rows="3" placeholder="Agrega notas o comentarios adicionales..." />
                </div>
            @endif
        </form>
    </x-slot>

    <x-slot name="footer">
        <x-button type="button" variant="secondary" @click="$wire.set('mostrarFormularioPeriodo',false)">
            Cancelar
        </x-button>

        <x-button type="submit" form="frmPeriodo">
            {{ $periodoId ? 'Actualizar Período' : 'Guardar Período' }}
        </x-button>
    </x-slot>
</x-dialog-modal>
