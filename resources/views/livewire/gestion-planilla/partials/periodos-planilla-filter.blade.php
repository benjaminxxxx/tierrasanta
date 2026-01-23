<x-card class="my-4">

    {{-- Header --}}
    <div class="flex items-center gap-2 mb-4">
        <i class="fa-solid fa-filter text-indigo-600 text-sm"></i>
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
            Filtros
        </h3>
    </div>

    <x-flex class="!items-end">

        {{-- Empleado --}}
        <x-group-field>
            <x-label value="Empleado" />

            <x-searchable-select :options="$empleados" search-placeholder="Buscar trabajador..."
                wire:model.live="filtros.plan_empleado_id" />

            <x-input-error for="filtros.plan_empleado_id" />
        </x-group-field>

        {{-- Año --}}
        <x-select wire:model.live="filtros.anio" placeholder="Selecciona un año" label="Año" >
            @for ($year = now()->year; $year >= 2014; $year--)
                <option value="{{ $year }}">{{ $year }}</option>
            @endfor
        </x-select>
    </x-flex>
</x-card>
