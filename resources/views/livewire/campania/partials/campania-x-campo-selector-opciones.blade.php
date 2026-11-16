{{-- Opciones de la campaña seleccionada: el menú común (x-campania.opciones) + reportes de esta pantalla --}}
@if ($campaniaSeleccionada && $campania)
    @php $itemReporte = 'flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-ink hover:bg-fog'; @endphp
    <x-flex class="justify-center">
        @can(\App\Constants\Permisos::CAMPAÑA_POR_CAMPO_GESTIONAR)
            @if ($campania->gasto_resumen_bdd_file)
                <x-button href="{{ Storage::disk('public')->url($campania->gasto_resumen_bdd_file) }}">
                    <i class="fa fa-file-excel"></i> Descargar Reporte BDD
                </x-button>
            @endif
        @endcan
        <button type="button" x-on:click="Livewire.dispatch('verTarjetaCampania', { campaniaId: {{ $campania->id }} })"
            class="inline-flex h-9 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25">
            <i class="fa fa-id-card text-steel"></i> Ver tarjeta
        </button>
        <x-campania.opciones :campania="$campania" trigger="boton" :detalle="false">
            @can(\App\Constants\Permisos::CAMPAÑA_POR_CAMPO_GESTIONAR)
                <button type="button" class="{{ $itemReporte }}" wire:click="generarBdd({{ $campania->id }})"
                    title="Arma el Excel con la BDD de costos tal como está ahora">
                    <i class="fa fa-file-excel w-4 text-center text-steel"></i> Generar reporte BDD
                </button>
                <button type="button" class="{{ $itemReporte }}" wire:click="generarBdd({{ $campania->id }}, true)"
                    title="Vuelve a leer las fuentes solo de este campo en el rango de la campaña, actualiza la BDD de costos y arma el Excel">
                    <i class="fa fa-rotate w-4 text-center text-steel"></i> Regenerar costos y generar BDD
                </button>
                <button type="button" class="{{ $itemReporte }}" wire:click="descargarReporteConsumo({{ $campania->id }})"
                    title="Salidas de almacén del campo en el rango de la campaña, tal como están ahora">
                    <i class="fa fa-boxes-stacked w-4 text-center text-steel"></i> Descargar reporte de consumo
                </button>
            @endcan
        </x-campania.opciones>
    </x-flex>
@endif
