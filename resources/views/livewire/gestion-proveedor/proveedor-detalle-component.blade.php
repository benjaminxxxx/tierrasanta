<div>
    <x-dialog-modal wire:model="mostrarDetalle" maxWidth="2xl">
        <x-slot name="title">
            <x-title>Detalle del Proveedor</x-title>
        </x-slot>
        <x-slot name="content">
            @if ($proveedor)
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="font-semibold">Razón Social:</span> {{ $proveedor->razon_social }}</div>
                    <div><span class="font-semibold">Nombre Comercial:</span> {{ $proveedor->nombre_comercial ?? '-' }}</div>
                    <div><span class="font-semibold">RUC:</span> {{ $proveedor->ruc ?? '-' }}</div>
                    <div><span class="font-semibold">Contacto:</span> {{ $proveedor->contacto ?? '-' }}</div>
                    <div><span class="font-semibold">Tipo Contribuyente:</span> {{ $proveedor->tipo_contribuyente ?? '-' }}</div>
                    <div><span class="font-semibold">Condición:</span> {{ $proveedor->condicion ?? '-' }}</div>
                    <div><span class="font-semibold">Estado Contribuyente:</span> {{ $proveedor->estado_contribuyente ?? '-' }}</div>
                    <div><span class="font-semibold">Estado Domicilio:</span> {{ $proveedor->estado_domicilio ?? '-' }}</div>
                    <div><span class="font-semibold">Fecha Inscripción:</span> {{ optional($proveedor->fecha_inscripcion)->format('d/m/Y') ?? '-' }}</div>
                    <div><span class="font-semibold">Inicio Actividades:</span> {{ optional($proveedor->fecha_inicio_actividades)->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-span-2"><span class="font-semibold">Dirección Fiscal:</span> {{ $proveedor->direccion_fiscal ?? '-' }}</div>
                    <div><span class="font-semibold">Distrito:</span> {{ $proveedor->distrito ?? '-' }}</div>
                    <div><span class="font-semibold">Departamento:</span> {{ $proveedor->departamento ?? '-' }}</div>
                    <div><span class="font-semibold">CIIU:</span> {{ $proveedor->ciiu ?? '-' }}</div>
                    <div><span class="font-semibold">Comercio Exterior:</span> {{ $proveedor->actividad_comercio_exterior ?? '-' }}</div>
                    <div class="col-span-2">
                        <span class="font-semibold">Verificado:</span>
                        @if ($proveedor->verificado)
                            <span class="text-green-600 dark:text-green-400"><i class="fa fa-check-circle"></i> Sí, el {{ optional($proveedor->verificado_at)->format('d/m/Y H:i') }}</span>
                        @else
                            <span class="text-gray-400">No verificado aún</span>
                        @endif
                    </div>
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-button type="button" variant="secondary" @click="$wire.set('mostrarDetalle', false)">
                Cerrar
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>