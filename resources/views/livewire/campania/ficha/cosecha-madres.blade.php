
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
                                    <x-input type="number" wire:model="campania.cosechamadres_infestador_tubo_campos" />
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
                                    <x-input readonly wire:model="campania.cosechamadres_conversion_fresco_seco_tubo" />
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

