<x-dialog-modal wire:model.live="mostrarInformacionContrato" maxWidth="lg">
    <x-slot name="title">
        Información del Contrato
    </x-slot>

    <x-slot name="content">

        @if ($contratoSeleccionado)
            <div class="grid grid-cols-2 gap-4 text-sm">

                <div>
                    <strong>Empleado:</strong>
                    <p>{{ $contratoSeleccionado['empleado'] }}</p>
                </div>

                <div>
                    <strong>Tipo de Planilla:</strong>
                    <p>{{ $contratoSeleccionado['tipo_planilla'] }}</p>
                </div>

                <div>
                    <strong>Tipo de Contrato:</strong>
                    <p>{{ $contratoSeleccionado['tipo_contrato'] }}</p>
                </div>

                <div>
                    <strong>Estado:</strong>
                    <p>{{ ucfirst($contratoSeleccionado['estado']) }}</p>
                </div>

                <div>
                    <strong>Fecha Inicio:</strong>
                    <p>{{ formatear_fecha($contratoSeleccionado['fecha_inicio']) }}</p>
                </div>

                <div>
                    <strong>Fecha Fin:</strong>
                    <p>{{ formatear_fecha($contratoSeleccionado['fecha_fin']) }}</p>
                </div>

                <div>
                    <strong>Fin de Periodo de Prueba:</strong>
                    <p>{{ formatear_fecha($contratoSeleccionado['fecha_fin_prueba']) }}</p>
                </div>

                <div>
                    <strong>Cargo:</strong>
                    <p>{{ $contratoSeleccionado['cargo_codigo'] }}</p>
                </div>

                <div>
                    <strong>Grupo:</strong>
                    <p>{{ $contratoSeleccionado['grupo_codigo'] }}</p>
                </div>

                <div>
                    <strong>Modalidad de Pago:</strong>
                    <p>{{ $contratoSeleccionado['modalidad_pago'] }}</p>
                </div>

                <div class="col-span-2">
                    <strong>Motivo Cese SUNAT:</strong>
                    <p>{{ $contratoSeleccionado['motivo_cese_sunat'] }}</p>
                </div>

                <div class="col-span-2">
                    <strong>Comentario Cese:</strong>
                    <p>{{ $contratoSeleccionado['comentario_cese'] }}</p>
                </div>

                <div>
                    <strong>Jubilado:</strong>
                    <p>{{ $contratoSeleccionado['esta_jubilado'] }}</p>
                </div>

                <div>
                    <strong>SP Código:</strong>
                    <p>{{ $contratoSeleccionado['plan_sp_codigo'] }}</p>
                </div>

            </div>
        @endif

    </x-slot>

    <x-slot name="footer">
        <x-button wire:click="$set('mostrarInformacionContrato', false)">
            Cerrar
        </x-button>
    </x-slot>
</x-dialog-modal>
