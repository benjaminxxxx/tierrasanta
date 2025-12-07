<form class="my-5">
    <x-select wire:model.live="filtroAnio" label="Año">
        <option value="">-- Seleccione Año --</option>
        @foreach($aniosDisponibles as $anio)
            <option value="{{ $anio }}">{{ $anio }}</option>
        @endforeach
    </x-select>
</form>