{{-- Costos → Mensual (DESIGN.md). El detalle del mes está en contabilidad-costos-mensuales-detalle-component. --}}
<div class="space-y-4 font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Costo mensual</h1>
            <p class="text-sm text-graphite">Costos fijos y operativos del mes y su reparto entre las campañas activas.</p>
        </div>
        @include('comun.selector-mes-compacto')
    </div>

    @can(\App\Constants\Permisos::CONTABILIDAD_COSTO_MENSUAL_VER)
        <livewire:costos.contabilidad-costos-mensuales-detalle-component :anio="$anio" :mes="$mes"
            wire:key="{{ $anio }}-{{ $mes }}" />
    @else
        <x-danger>
            No tiene permiso para ver la siguiente información
        </x-danger>
    @endcan

    <livewire:costos.costos-mensuales-distribucion-form-component />
    {{-- Modal "Cerrar campaña" (lo abre la tarjeta de cada campaña) --}}
    <livewire:campania.campania-cerrar-component />
    <x-loading wire:loading />
</div>
