<div>

    <x-flex class="w-full justify-between my-5">
        <x-h3>Evaluación de Brotes</x-h3>
        <x-flex>
            @if ($campania)
                <x-button type="button"
                    @click="$wire.dispatch('agregarEvaluacionBrote',{campaniaId:{{ $campania->id }}})">
                    <i class="fa fa-plus"></i> Agregar Evaluación
                </x-button>
            @endif

        </x-flex>
    </x-flex>

    <x-flex class="!items-start w-full">
        @if ($campania)
            <x-card class="md:w-[35rem]">
                <x-spacing>
                    <x-h3>
                        Resumen de Evaluación de Brotes
                    </x-h3>
                    <x-label>
                        Presione el boton <b>Sincronizar</b> datos para obtener la información de brotes de la
                        campaña seleccionada.
                    </x-label>
                    <x-table class="mt-3">
                        <x-slot name="thead">
                        </x-slot>
                        <x-slot name="tbody">
                            <x-tr>
                                <x-th>Fecha de evaluación</x-th>
                                <x-td>{{ formatear_fecha($campania->brotexpiso_fecha_evaluacion) }}</x-td>
                            </x-tr>
                            @if (
                                $mostrarVacios ||
                                    (!is_null($campania->brotexpiso_actual_brotes_2piso) && $campania->brotexpiso_actual_brotes_2piso != 0))
                                <x-tr>
                                    <x-th>Número actual de brotes aptos 2° piso</x-th>
                                    <x-td>{{ number_format($campania->brotexpiso_actual_brotes_2piso, 0) }}</x-td>
                                </x-tr>
                            @endif

                            @if (
                                $mostrarVacios ||
                                    (!is_null($campania->brotexpiso_brotes_2piso_n_dias) && $campania->brotexpiso_brotes_2piso_n_dias != 0))
                                <x-tr>
                                    <x-th>Número de brotes aptos 2° piso después de 60 días</x-th>
                                    <x-td>{{ number_format($campania->brotexpiso_brotes_2piso_n_dias, 0) }}</x-td>
                                </x-tr>
                            @endif

                            @if (
                                $mostrarVacios ||
                                    (!is_null($campania->brotexpiso_actual_brotes_3piso) && $campania->brotexpiso_actual_brotes_3piso != 0))
                                <x-tr>
                                    <x-th>Número actual de brotes aptos 3° piso</x-th>
                                    <x-td>{{ number_format($campania->brotexpiso_actual_brotes_3piso, 0) }}</x-td>
                                </x-tr>
                            @endif

                            @if (
                                $mostrarVacios ||
                                    (!is_null($campania->brotexpiso_brotes_3piso_n_dias) && $campania->brotexpiso_brotes_3piso_n_dias != 0))
                                <x-tr>
                                    <x-th>Número de brotes aptos 3° piso después de 60 días</x-th>
                                    <x-td>{{ number_format($campania->brotexpiso_brotes_3piso_n_dias, 0) }}</x-td>
                                </x-tr>
                            @endif

                            @if (
                                $mostrarVacios ||
                                    (!is_null($campania->brotexpiso_actual_total_brotes_2y3piso) &&
                                        $campania->brotexpiso_actual_total_brotes_2y3piso != 0))
                                <x-tr>
                                    <x-th>Número actual total de brotes aptos 2° y 3° piso</x-th>
                                    <x-td
                                        class="bg-lime-100">{{ number_format($campania->brotexpiso_actual_total_brotes_2y3piso, 0) }}</x-td>
                                </x-tr>
                            @endif

                            @if (
                                $mostrarVacios ||
                                    (!is_null($campania->brotexpiso_total_brotes_2y3piso_n_dias) &&
                                        $campania->brotexpiso_total_brotes_2y3piso_n_dias != 0))
                                <x-tr>
                                    <x-th>Número total de brotes aptos 2° y 3° piso en 60 días</x-th>
                                    <x-td>{{ number_format($campania->brotexpiso_total_brotes_2y3piso_n_dias, 0) }}</x-td>
                                </x-tr>
                            @endif


                        </x-slot>

                    </x-table>
                </x-spacing>
            </x-card>
            <div class="flex-1 overflow-auto">

                @livewire('reporte-campo-evaluacion-brotes-component', ['campaniaId' => $campania->id, 'campaniaUnica' => true], key($campania->id))

            </div>
        @endif
    </x-flex>

    <x-loading wire:loading />
</div>
