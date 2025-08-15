<div>
    <x-card2>
        <x-flex class="justify-between">
            <div>
                <x-h3>
                    Costos por campaña
                </x-h3>
                <x-label>
                    Costos realizados durante la campaña
                </x-label>
            </div>
            <x-flex>
                <x-select label="Seleccionar campo" wire:model.live="campoSeleccionado">
                    <option value="">SELECCIONE UN CAMPO</option>
                    @foreach ($campos as $campo)
                        <option value="{{ $campo->nombre }}">{{ $campo->nombre }} ({{ $campo->campanias->count() }}
                            campañas)</option>
                    @endforeach
                </x-select>
                <x-select wire:model.live="campaniaSeleccionada" label="Seleccionar campaña">
                    <option value="">TODAS LAS CAMPAÑAS</option>
                    @if ($campoSeleccionado && count($campanias) > 0)
                        @foreach ($campanias as $campania)
                            <option value="{{ $campania['id'] }}">{{ $campania['nombre_campania'] }}</option>
                        @endforeach
                    @endif
                </x-select>
            </x-flex>
        </x-flex>
    </x-card2>
    <x-card2 class="mt-4">
        @if ($campoSeleccionado && count($campanias) > 0)
            <div class="overflow-x-auto" id="tabla-costos-container">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <x-tr>
                            <x-th class="sticky left-0 !bg-gray-800 z-20">N°</x-th>
                            <x-th class="sticky left-[40px] !bg-gray-800 z-20">Mano de obra</x-th>
                            <x-th class="sticky left-[240px] !bg-gray-800 z-20">EJECT. CANT HA</x-th>
                            <x-th class="sticky left-[400px] !bg-gray-800 z-20">EJECT. COSTO $ HA</x-th>
                            @foreach ($campanias as $campaniaTh)
                                <x-th class="text-center bg-gray-700 min-w-[200px]">
                                    {{ $campaniaTh['nombre_campania'] }}<br />
                                    <x-button wire:click="detectarCostos({{ $campaniaTh['id'] }})">Detectar <i class="fa fa-sync"></i></x-button>
                                </x-th>
                            @endforeach
                        </x-tr>
                    </thead>
                    <tbody>
                        dd
                    </tbody>
                </table>
            </div>


        @else

            <x-label>
                Seleccione un campo y luego una campaña.
            </x-label>

        @endif
    </x-card2>
    <x-loading wire:loading />
</div>
@script
<script>
    document.addEventListener("DOMContentLoaded", function () {
        let container = document.getElementById("tabla-costos-container");
        container.scrollLeft = container.scrollWidth; // desplaza al final
    });
</script>
@endscript