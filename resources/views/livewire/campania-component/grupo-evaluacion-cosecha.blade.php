<div>
    <x-flex class="w-full justify-between my-5">
        <x-h3>
            Evaluación Cosecha
        </x-h3>
        <x-flex>
            <x-button type="button" wire:click="sincronizarInformacionParcial('evaluacion_cosecha')">
                <i class="fa fa-sync"></i> Sincronizar datos
            </x-button>
        </x-flex>
    </x-flex>
    <x-flex class="!items-start w-full">
        @if ($campania)
            <x-card class="md:w-[35rem]">
                <x-spacing>
                    <x-h3>Resumen de la evaluación después de días de inefestación</x-h3>
                    <x-warning>
                        Si ve 0 días de infestación considere sincronizar los datos de infestación y reinfestación, la
                        diferencia de días se calculará de la fecha registrada en el resumen de infestación y
                        reinfestación obteniendo la mayor fecha
                    </x-warning>
                    @if ($campania->evaluacionInfestaciones)
                        <x-table class="mt-3">
                            <x-slot name="thead">
                            </x-slot>
                            <x-slot name="tbody">
                                @foreach ($campania->evaluacionInfestaciones as $evaluacionInfestacion)
                                    <x-tr>
                                        <x-th>N° individuos por planta a {{ $evaluacionInfestacion->dias }} días
                                            infestación</x-th>
                                        <x-td>{{ number_format($evaluacionInfestacion->promedio, 0) }}</x-td>
                                    </x-tr>
                                @endforeach
                                <x-tr>
                                    <x-td>
                                        <b>
                                            Proyeccion Cosecha
                                        </b>
                                        <p>
                                            Esta información proviene del módulo Evaluación Infestación Cosecha
                                        </p>
                                    </x-td>
                                    <x-td
                                        class="bg-pink-200">{{ number_format($campania->evaluacion_cosecha_proyeccion_rendimiento_ha, 0) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td>
                                        <b>
                                            Proyeccion Cosecha 2
                                        </b>
                                        <p>
                                            Esta información proviene del módulo Proyección Rendimiento Poda
                                        </p>
                                    </x-td>
                                    <x-td
                                        class="bg-pink-200">{{ number_format($campania->proj_rdto_prom_rdto_ha, 0) }}</x-td>
                                </x-tr>
                            </x-slot>
                        </x-table>
                    @else
                        <p>
                            Aún no se han realizado evaluaciones después de la infestación
                        </p>
                    @endif

                </x-spacing>

            </x-card>
            <div class="flex-1 overflow-auto">

                <livewire:evaluacion-infestacion-cosecha-component campaniaId="{{ $campania->id }}"
                    campaniaUnica="{{ true }}" wire:key="grupo_evaluacion_cosecha.{{ $campania->id }}" />

            </div>
        @endif
    </x-flex>
</div>
