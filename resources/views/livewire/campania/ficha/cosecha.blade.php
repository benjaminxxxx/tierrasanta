
                    {{-- ============================================================
                    FECHA DE COSECHA (DISPARA TODOS LOS CÁLCULOS)
                    ============================================================ --}}
                    <x-group-field>
                        <x-input type="date" wire:model="campania.cosch_fecha" label="Fecha de cosecha / poda"
                            error="campania.cosch_fecha" />
                        <x-label class="text-xs text-gray-500">
                            Esta fecha recalcula automáticamente todos los tiempos de cosecha.
                        </x-label>
                    </x-group-field>

                    {{-- ============================================================
                    TIEMPOS CALCULADOS (SOLO LECTURA)
                    ============================================================ --}}
                    <x-h3>Tiempos calculados</x-h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-input type="text" label="Infestación → Cosecha" wire:model="campania.cosch_tiempo_inf_cosch"
                            readonly />

                        <x-input type="text" label="Reinfestación → Cosecha"
                            wire:model="campania.cosch_tiempo_reinf_cosch" readonly />

                        <x-input type="text" label="Inicio → Cosecha" wire:model="campania.cosch_tiempo_ini_cosch"
                            readonly />
                    </div>
                    {{-- ============================================================
                    DESTINO FRESCO (DESCRIPTIVO)
                    ============================================================ --}}
                    <x-h3>Destino fresco</x-h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-input type="text" label="Campos para infestador cartón (separe por guiones -)"
                            wire:model="campania.cosch_destino_carton" placeholder="Ej: Campo1 - Campo2 - Campo3" />

                        <x-input type="text" label="Campos para infestador tubo (separe por guiones -)"
                            wire:model="campania.cosch_destino_tubo" placeholder="Ej: CampoA - CampoB" />

                        <x-input type="text" label="Campos para infestador malla (separe por guiones -)"
                            wire:model="campania.cosch_destino_malla" placeholder="Ej: Sector Norte - Sector Sur" />
                    </div>

                    {{-- ============================================================
                    PRODUCCIÓN FRESCA
                    ============================================================ --}}
                    <x-h3>Producción fresca (kg)</x-h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <x-input type="number" label="Cartón" wire:model="campania.cosch_kg_fresca_carton" />
                        <x-input type="number" label="Tubo" wire:model="campania.cosch_kg_fresca_tubo" />
                        <x-input type="number" label="Malla" wire:model="campania.cosch_kg_fresca_malla" />
                        <x-input type="number" label="Losa" wire:model="campania.cosch_kg_fresca_losa" />
                    </div>

                    {{-- ============================================================
                    PRODUCCIÓN SECA
                    ============================================================ --}}
                    <x-h3>Producción seca (kg)</x-h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <x-input type="number" label="Cartón" wire:model="campania.cosch_kg_seca_carton" />
                        <x-input type="number" label="Tubo" wire:model="campania.cosch_kg_seca_tubo" />
                        <x-input type="number" label="Malla" wire:model="campania.cosch_kg_seca_malla" />
                        <x-input type="number" label="Losa" wire:model="campania.cosch_kg_seca_losa" />
                    </div>

                    {{-- ============================================================
                    FACTORES Y TOTALES (CALCULADOS)
                    ============================================================ --}}
                    <x-h3>Resultados calculados</x-h3>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <x-input type="string" label="Factor F/S Cartón" wire:model="campania.cosch_factor_fs_carton"
                            readonly />
                        <x-input type="string" label="Factor F/S Tubo" wire:model="campania.cosch_factor_fs_tubo"
                            readonly />
                        <x-input type="string" label="Factor F/S Malla" wire:model="campania.cosch_factor_fs_malla"
                            readonly />
                        <x-input type="string" label="Factor F/S Losa" wire:model="campania.cosch_factor_fs_losa"
                            readonly />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input type="string" label="Producción por hectárea" wire:model="campania.cosch_total_cosecha"
                            readonly />

                        <x-input type="string" label="Producción total de campaña"
                            wire:model="campania.cosch_total_campania" readonly />
                    </div>

