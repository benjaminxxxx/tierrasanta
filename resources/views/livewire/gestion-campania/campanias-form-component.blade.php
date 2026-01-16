<div x-data="cosechaForm">
    <x-dialog-modal wire:model="mostrarFormulario">
        <x-slot name="title">
            <x-h3>
                @if ($campaniaId)
                    Actualizar Parámetros de la Campaña
                @else
                    Registro de Campaña
                @endif
            </x-h3>
        </x-slot>
        <x-slot name="content">
            @if ($campaniaId)
                <x-success class="mb-4">
                    <p>
                        <b>Campo:</b> {{ $campania['campo'] ?? '-' }}
                    </p>
                    <p>
                        <b>Área original:</b> {{ $campania['campo_model']['area'] }}
                    </p>
                </x-success>
            @endif
            <ul
                class="flex flex-wrap text-sm font-medium text-center text-gray-500 border-b border-gray-200 dark:border-gray-700 dark:text-gray-400">

                <li class="me-2">
                    <a href="#" @click.prevent="tabActual = 'general'"
                        :class="tabActual === 'general'
                            ?
                            'inline-block p-4 text-blue-600 bg-gray-100 rounded-t-lg active dark:bg-gray-800 dark:text-blue-500' :
                            'inline-block p-4 rounded-t-lg hover:text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 dark:hover:text-gray-300'">
                        Información General
                    </a>
                </li>

                <li class="me-2">
                    <a href="#" @click.prevent="tabActual = 'infestacion'"
                        :class="tabActual === 'infestacion'
                            ?
                            'inline-block p-4 text-blue-600 bg-gray-100 rounded-t-lg active dark:bg-gray-800 dark:text-blue-500' :
                            'inline-block p-4 rounded-t-lg hover:text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 dark:hover:text-gray-300'">
                        Infestación
                    </a>
                </li>

                <li class="me-2">
                    <a href="#" @click.prevent="tabActual = 'reinfestacion'"
                        :class="tabActual === 'reinfestacion'
                            ?
                            'inline-block p-4 text-blue-600 bg-gray-100 rounded-t-lg active dark:bg-gray-800 dark:text-blue-500' :
                            'inline-block p-4 rounded-t-lg hover:text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 dark:hover:text-gray-300'">
                        Reinfestación
                    </a>
                </li>

                <li class="me-2">
                    <a href="#" @click.prevent="tabActual = 'cosecha-madres'"
                        :class="tabActual === 'cosecha-madres'
                            ?
                            'inline-block p-4 text-blue-600 bg-gray-100 rounded-t-lg active dark:bg-gray-800 dark:text-blue-500' :
                            'inline-block p-4 rounded-t-lg hover:text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 dark:hover:text-gray-300'">
                        Cosecha de madres
                    </a>
                </li>


                <li class="me-2">
                    <a href="#" @click.prevent="tabActual = 'cosecha'"
                        :class="tabActual === 'cosecha'
                            ?
                            'inline-block p-4 text-blue-600 bg-gray-100 rounded-t-lg active dark:bg-gray-800 dark:text-blue-500' :
                            'inline-block p-4 rounded-t-lg hover:text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 dark:hover:text-gray-300'">
                        Cosecha
                    </a>
                </li>
            </ul>
            <div>

                <div x-show="tabActual === 'general'" x-cloak class="mt-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">

                        @if (!$campaniaId)
                            <x-select-campo wire:model.live="campania.campo" error="campania.campo" label="Campo" />
                        @endif

                        <x-input type="number" wire:model="campania.area" error="campania.area" label="Área" />

                        <x-input type="date" wire:model="campania.fecha_inicio" error="campania.fecha_inicio"
                            label="Fecha de Inicio" />

                        <x-input type="text" wire:model="campania.nombre_campania" error="campania.nombre_campania"
                            label="Nombre de la Campaña" />

                        <x-input type="text" wire:model="campania.variedad_tuna" error="campania.variedad_tuna"
                            label="Variedad de Tuna" />

                        <x-input type="text" wire:model="campania.sistema_cultivo" error="campania.sistema_cultivo"
                            label="Sistema de Cultivo" />

                        <x-input type="number" wire:model="campania.pencas_x_hectarea"
                            error="campania.pencas_x_hectarea" label="Pencas por Hectárea" />

                        <x-input type="number" wire:model="campania.tipo_cambio" error="campania.tipo_cambio"
                            label="Tipo de Cambio" />

                        <x-input type="date" wire:model="campania.fecha_fin" error="campania.fecha_fin"
                            label="Fecha de cierre" />

                    </div>
                </div>

                <div x-show="tabActual === 'infestacion'" x-cloak class="mt-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <x-input type="date" label="Fecha Infestación" wire:model="campania.infestacion_fecha" />
                        <x-input type="date" label="Fecha recojo y vaciado de infestadores"
                            wire:model="campania.infestacion_fecha_recojo_vaciado_infestadores" />
                        <x-input type="date" label="Fecha colocación de malla"
                            wire:model="campania.infestacion_fecha_colocacion_malla" />
                        <x-input type="date" label="Fecha retiro de malla"
                            wire:model="campania.infestacion_fecha_retiro_malla" />

                    </div>
                </div>
                <div x-show="tabActual === 'reinfestacion'" x-cloak class="mt-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">

                        <x-input type="date" label="Fecha Reinfestación" wire:model="campania.reinfestacion_fecha" />

                        <x-input type="date" label="Fecha recojo y vaciado de infestadores"
                            wire:model="campania.reinfestacion_fecha_recojo_vaciado_infestadores" />

                        <x-input type="date" label="Fecha colocación de malla"
                            wire:model="campania.reinfestacion_fecha_colocacion_malla" />

                        <x-input type="date" label="Fecha retiro de malla"
                            wire:model="campania.reinfestacion_fecha_retiro_malla" />

                    </div>
                </div>

                <div x-show="tabActual === 'cosecha-madres'" x-cloak class="mt-4 space-y-4">

                    {{-- ============================================================
                    FECHA
                    ============================================================ --}}
                    <table class="w-full border border-gray-300 dark:border-gray-600">
                        <tbody>
                            <tr class="bg-yellow-100 dark:bg-gray-700 font-semibold">
                                <td class="p-2">Fecha de cosecha de madres</td>
                                <td class="p-2 w-48">
                                    <x-input type="date" wire:model="campania.cosechamadres_fecha_cosecha" />
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- ============================================================
                    DESTINO DE MADRES EN FRESCO
                    ============================================================ --}}
                    <table class="w-full border border-gray-300 dark:border-gray-600">
                        <tbody>
                            <tr class="bg-yellow-100 dark:bg-gray-700 font-semibold">
                                <td colspan="2" class="p-2">Destino de madres en fresco (kg)</td>
                            </tr>

                            <tr>
                                <td class="p-2">Infestador cartón – campos (kg)</td>
                                <td class="p-2 w-48">
                                    <x-input type="number"
                                        wire:model="campania.cosechamadres_infestador_carton_campos" />
                                </td>
                            </tr>

                            <tr>
                                <td class="p-2">Infestador tubo – campos (kg)</td>
                                <td class="p-2 w-48">
                                    <x-input type="number"
                                        wire:model="campania.cosechamadres_infestador_tubo_campos" />
                                </td>
                            </tr>

                            <tr>
                                <td class="p-2">Infestador mallita – campos (kg)</td>
                                <td class="p-2 w-48">
                                    <x-input type="number"
                                        wire:model="campania.cosechamadres_infestador_mallita_campos" />
                                </td>
                            </tr>

                            <tr>
                                <td class="p-2">Para secado (kg)</td>
                                <td class="p-2 w-48">
                                    <x-input type="number" wire:model="campania.cosechamadres_para_secado" />
                                </td>
                            </tr>

                            <tr>
                                <td class="p-2">Para venta en fresco (kg)</td>
                                <td class="p-2 w-48">
                                    <x-input type="number" wire:model="campania.cosechamadres_para_venta_fresco" />
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- ============================================================
                    RECUPERACIÓN MADRES EN SECO
                    ============================================================ --}}
                    <table class="w-full border border-gray-300 dark:border-gray-600">
                        <tbody>
                            <tr class="bg-yellow-100 dark:bg-gray-700 font-semibold">
                                <td colspan="2" class="p-2">Recuperación madres en seco (kg)</td>
                            </tr>

                            <tr>
                                <td class="p-2">De infestadores cartón</td>
                                <td class="p-2 w-48">
                                    <x-input type="number"
                                        wire:model="campania.cosechamadres_recuperacion_madres_seco_carton" />
                                </td>
                            </tr>

                            <tr>
                                <td class="p-2">De infestadores tubo</td>
                                <td class="p-2 w-48">
                                    <x-input type="number"
                                        wire:model="campania.cosechamadres_recuperacion_madres_seco_tubo" />
                                </td>
                            </tr>

                            <tr>
                                <td class="p-2">De infestadores mallita</td>
                                <td class="p-2 w-48">
                                    <x-input type="number"
                                        wire:model="campania.cosechamadres_recuperacion_madres_seco_mallita" />
                                </td>
                            </tr>

                            <tr>
                                <td class="p-2">De secado</td>
                                <td class="p-2 w-48">
                                    <x-input type="number"
                                        wire:model="campania.cosechamadres_recuperacion_madres_seco_secado" />
                                </td>
                            </tr>

                            <tr>
                                <td class="p-2">De venta en fresco</td>
                                <td class="p-2 w-48">
                                    <x-input type="number"
                                        wire:model="campania.cosechamadres_recuperacion_madres_seco_fresco" />
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- ============================================================
                    CONVERSIÓN FRESCO → SECO (SOLO LECTURA)
                    ============================================================ --}}
                    <table class="w-full border border-gray-300 dark:border-gray-600">
                        <tbody>
                            <tr class="bg-yellow-100 dark:bg-gray-700 font-semibold">
                                <td colspan="2" class="p-2">Conversión fresco a seco</td>
                            </tr>

                            <tr>
                                <td class="p-2">Cartón</td>
                                <td class="p-2 w-48">
                                    <x-input readonly
                                        wire:model="campania.cosechamadres_conversion_fresco_seco_carton" />
                                </td>
                            </tr>

                            <tr>
                                <td class="p-2">Tubo</td>
                                <td class="p-2 w-48">
                                    <x-input readonly
                                        wire:model="campania.cosechamadres_conversion_fresco_seco_tubo" />
                                </td>
                            </tr>

                            <tr>
                                <td class="p-2">Mallita</td>
                                <td class="p-2 w-48">
                                    <x-input readonly
                                        wire:model="campania.cosechamadres_conversion_fresco_seco_mallita" />
                                </td>
                            </tr>

                            <tr>
                                <td class="p-2">Secado</td>
                                <td class="p-2 w-48">
                                    <x-input readonly
                                        wire:model="campania.cosechamadres_conversion_fresco_seco_secado" />
                                </td>
                            </tr>

                            <tr>
                                <td class="p-2">Fresco</td>
                                <td class="p-2 w-48">
                                    <x-input readonly
                                        wire:model="campania.cosechamadres_conversion_fresco_seco_fresco" />
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </div>



                <div x-show="tabActual === 'cosecha'" x-cloak class="mt-6 space-y-6">

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
                        <x-input type="text" label="Infestación → Cosecha"
                            wire:model="campania.cosch_tiempo_inf_cosch" readonly />

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
                        <x-input type="string" label="Factor F/S Cartón"
                            wire:model="campania.cosch_factor_fs_carton" readonly />
                        <x-input type="string" label="Factor F/S Tubo" wire:model="campania.cosch_factor_fs_tubo"
                            readonly />
                        <x-input type="string" label="Factor F/S Malla" wire:model="campania.cosch_factor_fs_malla"
                            readonly />
                        <x-input type="string" label="Factor F/S Losa" wire:model="campania.cosch_factor_fs_losa"
                            readonly />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input type="string" label="Producción por hectárea"
                            wire:model="campania.cosch_total_cosecha" readonly />

                        <x-input type="string" label="Producción total de campaña"
                            wire:model="campania.cosch_total_campania" readonly />
                    </div>

                </div>

            </div>
        </x-slot>
        <x-slot name="footer">
            <!--Boton cerrar y registrar, parametros action id, si el id existe se cambia el texto a actualizar-->
            <x-form-buttons action="guardarCampania" id="{{ $campaniaId }}" />
        </x-slot>
    </x-dialog-modal>
</div>
@script
    <script>
        Alpine.data('cosechaForm', () => ({
            tabActual: @entangle('tabActual'),

            init() {
                // respaldo por si llega null o vacío desde Livewire
                if (!this.tabActual) {
                    this.tabActual = 'general'
                }
            }
        }))
    </script>
@endscript
