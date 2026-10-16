{{-- Botones de un informe mensual: ver, Excel, generar o recalcular desde ese mes --}}
<div class="flex flex-wrap justify-center gap-1">
    @if ($informe)
        <x-button variant="secondary" wire:click="ver({{ $informe->id }})" title="Ver en la web"><i class="fa fa-eye"></i></x-button>
        <x-button variant="success" wire:click="descargar({{ $informe->id }})" title="Descargar Excel"><i class="fa fa-file-excel"></i></x-button>
    @endif
    @can(\App\Constants\Permisos::CAMPAÑA_INFORME_MENSUAL_GESTIONAR)
        @if ($informe)
            <x-button variant="secondary" title="Volver a calcular este mes y los siguientes ya generados"
                wire:click="recalcularDesde({{ $campaniaId }}, {{ $anio }}, {{ $mes }})"
                wire:confirm="Se recalcula {{ sprintf('%02d/%d', $mes, $anio) }} con los datos de hoy y también los meses siguientes ya generados de esta campaña (cada uno sube de versión). ¿Continuar?">
                <i class="fa fa-sync"></i> Recalcular desde aquí
            </x-button>
        @else
            <x-button wire:click="generar({{ $campaniaId }}, {{ $anio }}, {{ $mes }})"><i class="fa fa-cogs"></i> Generar</x-button>
        @endif
    @endcan
</div>
