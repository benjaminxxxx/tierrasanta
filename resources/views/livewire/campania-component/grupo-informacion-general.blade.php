<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-2 items-center">
            <div class="font-medium text-gray-700">Lote:</div>
            <div>{{ $campania->campo }}</div>
        </div>
        <div class="grid grid-cols-2 gap-2 items-center">
            <div class="font-medium text-gray-700">Variedad de tuna:</div>
            <div>{{ $campania->variedad_tuna }}</div>
        </div>
        <div class="grid grid-cols-2 gap-2 items-center">
            <div class="font-medium text-gray-700">Campaña:</div>
            <div>{{ $campania->nombre_campania }}</div>
        </div>
        <div class="grid grid-cols-2 gap-2 items-center">
            <div class="font-medium text-gray-700">Área:</div>
            <div>{{ $campania->campo_model->area }}</div>
        </div>
        <div class="grid grid-cols-2 gap-2 items-center">
            <div class="font-medium text-gray-700">Sistema de cultivo:</div>
            <div>{{ $campania->sistema_cultivo }}</div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-2 items-center">
            <div class="font-medium text-gray-700">Pencas x Hectárea:</div>
            <div>{{ $campania->pencas_x_hectarea }}</div>
        </div>
        <div class="grid grid-cols-2 gap-2 items-center">
            <div class="font-medium text-gray-700">T.C.:</div>
            <div>{{ $campania->tipo_cambio }}</div>
        </div>
        <div class="grid grid-cols-2 gap-2 items-center">
            <div class="font-medium text-gray-700">Fecha de siembra:</div>
            <div>{{ formatear_fecha($campania->fecha_siembra) }}</div>
        </div>
        <div class="grid grid-cols-2 gap-2 items-center">
            <div class="font-medium text-gray-700">Fecha de inicio de Campaña:</div>
            <div>{{ formatear_fecha($campania->fecha_inicio) }}</div>
        </div>
        <div class="grid grid-cols-2 gap-2 items-center">
            <div class="font-medium text-gray-700">Fin de Campaña:</div>
            <div>{{ formatear_fecha($campania->fecha_fin) }}</div>
        </div>
    </div>
</div>
