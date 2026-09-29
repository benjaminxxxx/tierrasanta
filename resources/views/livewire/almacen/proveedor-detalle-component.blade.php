<div>
    <x-dialog-modal wire:model="mostrarDetalle" maxWidth="2xl">
        <x-slot name="title">
            <x-title>Detalle del Proveedor</x-title>
        </x-slot>

        <x-slot name="content">
            @if ($proveedor)
                @php
                    $persona = $proveedor->persona;
                @endphp

                <div class="grid grid-cols-2 gap-4 text-sm">
                    <!-- Datos de la Persona / Empresa -->
                    <div class="col-span-2 pb-2 border-b border-gray-200 dark:border-gray-700 font-bold text-gray-700 dark:text-gray-300">
                        <i class="fa fa-building mr-1"></i> Información General
                    </div>

                    <div>
                        <span class="font-semibold">Nombre / Razón Social:</span> 
                        {{ $persona?->nombre_mostrar ?? '-' }}
                    </div>
                    <div>
                        <span class="font-semibold">Tipo Documento / N°:</span> 
                        {{ $persona?->tipo_documento ?? 'DOC' }}: {{ $persona?->numero_documento ?? '-' }}
                    </div>
                    <div>
                        <span class="font-semibold">Teléfono / Móvil:</span> 
                        {{ $persona?->telefono_movil ?? '-' }}
                    </div>
                    <div>
                        <span class="font-semibold">Correo Electrónico:</span> 
                        {{ $persona?->email ?? '-' }}
                    </div>
                    <div class="col-span-2">
                        <span class="font-semibold">Dirección Fiscal:</span> 
                        {{ $persona?->direccion ?? '-' }}
                    </div>
                    <div>
                        <span class="font-semibold">Ubicación:</span> 
                        {{ implode(', ', array_filter([$persona?->distrito, $persona?->provincia, $persona?->departamento])) ?: '-' }}
                    </div>

                    <!-- Datos del Proveedor / Contribuyente -->
                    <div class="col-span-2 pt-2 pb-2 border-b border-gray-200 dark:border-gray-700 font-bold text-gray-700 dark:text-gray-300">
                        <i class="fa fa-id-card mr-1"></i> Información de Contribuyente
                    </div>

                    <div>
                        <span class="font-semibold">Tipo Contribuyente:</span> 
                        {{ $proveedor->tipo_contribuyente ?? '-' }}
                    </div>
                    <div>
                        <span class="font-semibold">Condición:</span> 
                        {{ $proveedor->condicion ?? '-' }}
                    </div>
                    <div>
                        <span class="font-semibold">Estado Contribuyente:</span> 
                        {{ $proveedor->estado_contribuyente ?? '-' }}
                    </div>
                    <div>
                        <span class="font-semibold">Estado Domicilio:</span> 
                        {{ $proveedor->estado_domicilio ?? '-' }}
                    </div>
                    <div>
                        <span class="font-semibold">Fecha Inscripción:</span> 
                        {{ optional($proveedor->fecha_inscripcion)->format('d/m/Y') ?? '-' }}
                    </div>
                    <div>
                        <span class="font-semibold">Inicio Actividades:</span> 
                        {{ optional($proveedor->fecha_inicio_actividades)->format('d/m/Y') ?? '-' }}
                    </div>
                    <div>
                        <span class="font-semibold">CIIU:</span> 
                        {{ $proveedor->ciiu ?? '-' }}
                    </div>
                    <div>
                        <span class="font-semibold">Comercio Exterior:</span> 
                        {{ $proveedor->actividad_comercio_exterior ?? '-' }}
                    </div>

                    <div class="col-span-2 pt-2 border-t border-gray-100 dark:border-gray-800 flex gap-4">
                        <div>
                            <span class="font-semibold">Agente Retención:</span>
                            <span class="{{ $proveedor->es_agente_retencion ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $proveedor->es_agente_retencion ? 'Sí' : 'No' }}
                            </span>
                        </div>
                        <div>
                            <span class="font-semibold">Buen Contribuyente:</span>
                            <span class="{{ $proveedor->es_buen_contribuyente ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $proveedor->es_buen_contribuyente ? 'Sí' : 'No' }}
                            </span>
                        </div>
                    </div>

                    <div class="col-span-2">
                        <span class="font-semibold">Estado Verificación:</span>
                        @if ($proveedor->verificado)
                            <span class="text-green-600 dark:text-green-400 font-medium">
                                <i class="fa fa-check-circle"></i> Verificado el {{ optional($proveedor->verificado_at)->format('d/m/Y H:i') }}
                            </span>
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