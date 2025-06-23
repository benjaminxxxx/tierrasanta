<div>
     <x-loading wire:loading />

    <x-flex class="my-3">
        <x-h3>
            Registro de ventas 
        </x-h3>
        <x-button>
            <i class="fa fa-plus"></i> Registrar venta
        </x-button>
    </x-flex>

    <x-card>
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th colspan="5">
                            Cosecha
                        </x-th>
                        <x-th colspan="4">
                            Proceso
                        </x-th>
                        <x-th colspan="4">
                            Venta
                        </x-th>
                        <x-th colspan="4">
                            -
                        </x-th>
                    </x-tr>
                    <x-tr>
                        <x-th>
                            Fecha de ingreso
                        </x-th>
                        <x-th>
                            Campo
                        </x-th>
                        <x-th>
                            Área
                        </x-th>
                        <x-th>
                            Procedencia
                        </x-th>
                        <x-th>
                            Cantidad fresca
                        </x-th>

                        <x-th>
                            Campo
                        </x-th>
                        <x-th>
                            Fecha filtrado
                        </x-th>
                        <x-th>
                            Cantidad Seca
                        </x-th>
                        <x-th>
                            Condición
                        </x-th>

                        <x-th>
                            Fecha de venta
                        </x-th>
                        <x-th>
                            Comprador
                        </x-th>
                        <x-th>
                            Conversión fresco/seco
                        </x-th>
                        <x-th>
                            % de ácido carmínico
                        </x-th>

                        <x-th>
                            Infestadores del campo
                        </x-th>
                        <x-th>
                            Tipo de cosecha
                        </x-th>
                        <x-th>
                            Tipo de infestador
                        </x-th>
                        <x-th>
                            Observación
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">

                </x-slot>
            </x-table>
        </x-spacing>

    </x-card>
    <livewire:cochinilla_ventas.cochinilla-venta-registro-form-component/>
</div>