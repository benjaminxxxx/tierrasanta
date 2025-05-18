<x-card>
    <x-loading wire:loading />
    <x-spacing>
        <form wire:submit.prevent="guardarInformacionCosecha">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <x-h3 class="mb-3">Fecha</x-h3>
                    <x-group-field>
                        <x-input-date label="Fecha cosecha o poda" wire:model="fecha_cosecha_poda"
                            error="fecha_cosecha_poda" />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Tiempo de infestación a cosecha" wire:model="tiempo_infestacion_a_cosecha"
                            class="!bg-gray-100" readonly />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Tiempo de re-infestación a cosecha"
                            wire:model="tiempo_reinfestacion_a_cosecha" class="!bg-gray-100" readonly />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Tiempo desde el inicio hasta la cosecha"
                            wire:model="tiempo_inicio_a_cosecha" class="!bg-gray-100" readonly />
                    </x-group-field>
                </div>
                <div>
                    <x-h3 class="mb-3">Destino fresco</x-h3>
                    <x-group-field>
                        <x-input-string label="Campos para infestador cartón (separe por guiones - )"
                            wire:model="cosch_destino_carton" />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Campos para infestador tubo (separe por guiones - )"
                            wire:model="cosch_destino_tubo" />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Campos para infestador malla (separe por guiones - )"
                            wire:model="cosch_destino_malla" />
                    </x-group-field>
                </div>
                <div>
                    <x-h3 class="mb-3">Fresco</x-h3>
                    <x-group-field>
                        <x-input-string label="Kg de cochinilla fresca para infestador cartón"
                            wire:model="kg_fresca_carton" />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Kg de cochinilla fresca para infestador tubo"
                            wire:model="kg_fresca_tubo" />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Kg de cochinilla fresca para infestador malla"
                            wire:model="kg_fresca_malla" />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Kg de cochinilla fresca para losa" wire:model="kg_fresca_losa" />
                    </x-group-field>
                </div>
                <div>
                    <x-h3 class="mb-3">Seco</x-h3>
                    <x-group-field>
                        <x-input-string label="Kg de cochinilla seca del infestador cartón"
                            wire:model="kg_seca_carton" />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Kg de cochinilla seca del infestador tubo" wire:model="kg_seca_tubo" />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Kg de cochinilla seca del infestador malla" wire:model="kg_seca_malla" />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Kg de cochinilla seca del infestador losa" wire:model="kg_seca_losa" />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Kg de cochinilla seca vendida como madre"
                            wire:model="kg_seca_venta_madre" />
                    </x-group-field>
                </div>
                <div>
                    <x-h3 class="mb-3">Factor fresca/seca</x-h3>
                    <x-group-field>
                        <x-input-string label="Factor de conversión fresca a seca (cartón)"
                            wire:model="factor_fresca_seca_carton" class="!bg-gray-100" readonly />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Factor de conversión fresca a seca (tubo)"
                            wire:model="factor_fresca_seca_tubo" class="!bg-gray-100" readonly />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Factor de conversión fresca a seca (malla)"
                            wire:model="factor_fresca_seca_malla" class="!bg-gray-100" readonly />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Factor de conversión fresca a seca (losa)"
                            wire:model="factor_fresca_seca_losa" class="!bg-gray-100" readonly />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Total de producción en cosecha o poda"
                            wire:model="total_produccion_cosecha_poda" class="!bg-gray-100" readonly />
                    </x-group-field>
                    <x-group-field>
                        <x-input-string label="Total de producción de la campaña" wire:model="total_produccion_campania"
                            class="!bg-gray-100" readonly />
                    </x-group-field>

                </div>

            </div>
            <x-flex class="w-full justify-end">
                <x-button type="submit">
                    <i class="fa fa-save"></i> Guardar información
                </x-button>
            </x-flex>
        </form>

    </x-spacing>
</x-card>
