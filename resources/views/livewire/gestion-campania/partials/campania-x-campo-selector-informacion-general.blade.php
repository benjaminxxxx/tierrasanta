<x-card2 class="grid grid-cols-1 md:grid-cols-3 space-y-4 gap-4 dark:text-gray-300 mt-4">
    <div>
        <x-label value="Lote:" />
        <div>{{ $campania->campo }}</div>
    </div>

    <div>
        <x-label value="Variedad de tuna:" />
        <div>{{ $campania->variedad_tuna }}</div>
    </div>

    <div>
        <x-label value="Campaña:" />
        <div>{{ $campania->nombre_campania }}</div>
    </div>

    <div>
        <x-label value="Área:" />
        <div>{{ $campania->campo_model->area }}</div>
    </div>

    <div>
        <x-label value="Sistema de cultivo:" />
        <div>{{ $campania->sistema_cultivo }}</div>
    </div>

    <div>
        <x-label value="Pencas x Hectárea:" />
        <div>{{ $campania->pencas_x_hectarea }}</div>
    </div>

    <div>
        <x-label value="T.C.:" />
        <div>{{ $campania->tipo_cambio }}</div>
    </div>

    <div>
        <x-label value="Fecha de siembra:" />
        <div>{{ formatear_fecha($campania->fecha_siembra) }}</div>
    </div>

    <div>
        <x-label value="Fecha de inicio de Campaña:" />
        <div>{{ formatear_fecha($campania->fecha_inicio) }}</div>
    </div>

    <div>
        <x-label value="Fin de Campaña:" />
        <div>{{ formatear_fecha($campania->fecha_fin) }}</div>
    </div>
</x-card2>
