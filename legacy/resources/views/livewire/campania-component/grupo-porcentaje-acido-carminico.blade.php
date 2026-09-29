<div>
    <x-flex class="w-full justify-between my-5">
        <x-h3>
            Porcentaje Ácido Carmínico
        </x-h3>
    </x-flex>
    <x-flex class="!items-start w-full">
        @if ($campania)
            <x-card class="md:w-[35rem]">
                <x-spacing>
                    <x-h3>Resumen de % de ácido carmínico</x-h3>

                    @if ($campania)
                        <form wire:submit.prevent="porcentajeAcidoCarminicoGuardar">
                            <x-table class="mt-3">
                                <x-slot name="thead">
                                </x-slot>
                                <x-slot name="tbody">
                                    <x-tr>
                                        <x-td><b>Promedio ácido carmínico</b></x-td>
                                        <x-td>
                                            <x-input type="number" wire:model="porcentajeAcidoCarminicoPromedio"
                                                readonly class="!bg-gray-100" />
                                        </x-td>
                                    </x-tr>
                                    <x-tr>
                                        <x-td><b>De infestadores</b></x-td>
                                        <x-td>
                                            <x-input type="number" wire:model="porcentajeAcidoCarminicoInfestadores" />
                                        </x-td>
                                    </x-tr>
                                    <x-tr>
                                        <x-td><b>De secado</b></x-td>
                                        <x-td>
                                            <x-input type="number" wire:model="porcentajeAcidoCarminicoSecado" />
                                        </x-td>
                                    </x-tr>
                                    <x-tr>
                                        <x-td><b>De Poda Cosecha (infestador)</b></x-td>
                                        <x-td>
                                            <x-input type="number"
                                                wire:model="porcentajeAcidoCarminicoPodaCosechaInfestador" />
                                        </x-td>
                                    </x-tr>
                                    <x-tr>
                                        <x-td><b>De Poda Cosecha (losa)</b></x-td>
                                        <x-td>
                                            <x-input type="number"
                                                wire:model="porcentajeAcidoCarminicoPodaCosechaLosa" />
                                        </x-td>
                                    </x-tr>
                                    <x-tr>
                                        <x-td><b>Tamaño cochinilla (Nro individuos/gramo)</b></x-td>
                                        <x-td>
                                            <x-input type="number"
                                                wire:model="porcentajeAcidoCarminicoTamanioCochinilla" />
                                        </x-td>
                                    </x-tr>
                                </x-slot>
                            </x-table>
                            <x-flex class="justify-end mt-3 w-full">
                                <x-button type="submit">
                                    <i class="fa fa-save"></i> Guardar información
                                </x-button>
                            </x-flex>
                        </form>
                    @endif

                </x-spacing>

            </x-card>
            <div class="flex-1 overflow-auto">

            </div>
        @endif
    </x-flex>
</div>
