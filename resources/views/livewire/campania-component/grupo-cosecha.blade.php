<div>
    <x-flex class="w-full justify-between my-5">
        <x-h3>
            Cosecha
        </x-h3>
        <x-flex>
            <!--<x-button type="button" wire:click="sincronizarInformacionParcial('evaluacion_cosecha')">
                <i class="fa fa-sync"></i> Sincronizar datos
            </x-button>-->
        </x-flex>
    </x-flex>
    <x-flex class="!items-start w-full">
        @if ($campania)
            <x-card class="md:w-[35rem]">
                <x-spacing>
                    <x-h3>Resumen de cosecha</x-h3>

                    @if ($campania)
                        <x-table class="mt-3">
                            <x-slot name="thead">
                            </x-slot>
                            <x-slot name="tbody">
                                <x-tr>
                                    <x-td><b>Fecha cosecha o poda</b></x-td>
                                    <x-td>{{ $campania->cosch_fecha }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Tiempo de infestación a cosecha (días)</b></x-td>
                                    <x-td>{{ $campania->cosch_tiempo_inf_cosch }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Tiempo de re-infestación a cosecha (días)</b></x-td>
                                    <x-td>{{ $campania->cosch_tiempo_reinf_cosch }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Tiempo desde el inicio hasta la cosecha (días)</b></x-td>
                                    <x-td>{{ $campania->cosch_tiempo_ini_cosch }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Kg fresca (cartón {{$campania->cosch_destino_carton}})</b></x-td>
                                    <x-td>{{ number_format($campania->cosch_kg_fresca_carton, 2) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Kg fresca (tubo {{$campania->cosch_destino_tubo}})</b></x-td>
                                    <x-td>{{ number_format($campania->cosch_kg_fresca_tubo, 2) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Kg fresca (malla {{$campania->cosch_destino_malla}})</b></x-td>
                                    <x-td>{{ number_format($campania->cosch_kg_fresca_malla, 2) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Kg fresca (losa)</b></x-td>
                                    <x-td>{{ number_format($campania->cosch_kg_fresca_losa, 2) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Kg seca (cartón)</b></x-td>
                                    <x-td>{{ number_format($campania->cosch_kg_seca_carton, 2) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Kg seca (tubo)</b></x-td>
                                    <x-td>{{ number_format($campania->cosch_kg_seca_tubo, 2) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Kg seca (malla)</b></x-td>
                                    <x-td>{{ number_format($campania->cosch_kg_seca_malla, 2) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Kg seca (losa)</b></x-td>
                                    <x-td>{{ number_format($campania->cosch_kg_seca_losa, 2) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Kg seca vendida como madre</b></x-td>
                                    <x-td>{{ number_format($campania->cosch_kg_seca_venta_madre, 2) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Factor fresca/seca (cartón)</b></x-td>
                                    <x-td>{{ number_format($campania->cosch_factor_fs_carton, 2) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Factor fresca/seca (tubo)</b></x-td>
                                    <x-td>{{ number_format($campania->cosch_factor_fs_tubo, 2) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Factor fresca/seca (malla)</b></x-td>
                                    <x-td>{{ number_format($campania->cosch_factor_fs_malla, 2) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Factor fresca/seca (losa)</b></x-td>
                                    <x-td>{{ number_format($campania->cosch_factor_fs_losa, 2) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Total producción en cosecha o poda</b></x-td>
                                    <x-td>{{ number_format($campania->cosch_total_cosecha, 2) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Total producción de la campaña</b></x-td>
                                    <x-td>{{ number_format($campania->cosch_total_campania, 2) }}</x-td>
                                </x-tr>
                            </x-slot>

                        </x-table>
                    @endif

                </x-spacing>

            </x-card>
            <div class="flex-1 overflow-auto">
                <livewire:cosecha-por-campania-form-component campaniaId="{{ $campania->id }}"
                    wire:key="grupo_cosecha.{{ $campania->id }}" />
            </div>
        @endif
    </x-flex>
</div>
