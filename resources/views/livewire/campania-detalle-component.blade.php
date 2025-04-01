<div>
    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
            @if ($campania)
                Campaña {{ $campania->nombre_campania }}
            @endif
        </x-slot>

        <x-slot name="content">
            @if ($campania)
                <x-flex class="justify-end w-full mb-4">
                    <x-button type="button" wire:click="actualizarInformacionCampania">
                        <i class="fa fa-refresh"></i> Actualizar información
                    </x-button>
                </x-flex>
                <x-group-field>

                    <div>
                        <x-table>
                            <x-slot name="thead">

                            </x-slot>
                            <x-slot name="tbody">
                                <x-tr>
                                    <x-th class="!text-primary bg-gray-100" colspan="2">
                                        INFORMACIÓN GENERAL
                                    </x-th>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">Lote</p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->campo }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Variedad de tuna
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->variedad_tuna }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Campaña
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->nombre_campania }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Área
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->campo_model->area }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Sistema de cultivo
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->sistema_cultivo }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Pencas x Hectárea
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ number_format($campania->pencas_x_hectarea, 0) }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            T.C.
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->tipo_cambio }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-th class="!text-primary bg-gray-100" colspan="2">
                                        FECHA
                                    </x-th>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">Fecha de siembra</p>
                                        <p class="text-xs font-normal">
                                            La fecha de siembra se obtiene de la ultima siembra del campo<br />antes de
                                            la fecha de inicio de camapaña
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->fecha_siembra }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">Fecha de Inicio de Camapaña</p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->fecha_inicio }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Fin de Campaña
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->fecha_fin }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-th class="!text-primary bg-gray-100" colspan="2">
                                        POBLACION PLANTAS
                                    </x-th>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Fecha de evaluación día cero
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->pp_dia_cero_fecha_evaluacion }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Nª de pencas madre día cero
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->pp_dia_cero_numero_pencas_madre }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Fecha de evaluación resiembra
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->pp_resiembra_fecha_evaluacion }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Nª de pencas madre después de resiembra
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->pp_resiembra_numero_pencas_madre }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-th class="!text-primary bg-gray-100" colspan="2">
                                        EVALUACION DE BROTES
                                    </x-th>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Fecha de evaluación
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->brotexpiso_fecha_evaluacion }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Número actual de brotes aptos 2° piso 
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->brotexpiso_actual_brotes_2piso }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Número de brotes aptos 2° piso después de 60 días
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->brotexpiso_brotes_2piso_n_dias }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Número actual de brotes aptos 3° piso 
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->brotexpiso_actual_brotes_3piso }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Número de brotes aptos 3° piso después de 60 días
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->brotexpiso_brotes_3piso_n_dias }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Número actual total de brotes aptos 2° y 3° piso
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->brotexpiso_actual_total_brotes_2y3piso }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Número total de brotes aptos 2° y 3° piso en 60 días
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->brotexpiso_total_brotes_2y3piso_n_dias }}
                                    </x-td>
                                </x-tr>
                            </x-slot>
                        </x-table>
                    </div>

                </x-group-field>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                Cerrar
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>
