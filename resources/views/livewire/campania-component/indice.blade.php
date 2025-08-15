<div>
    <x-loading wire:loading />

    <x-h3 class="mb-4">
        Camapañas por Campo
    </x-h3>
    <x-card>
        <x-spacing>
            <x-flex class="!items-end">
                <div>
                    <x-select wire:model.live="campoSeleccionado" label="Seleccionar el Campo">
                        <option value="">Seleccione un Campo</option>
                        @foreach ($campos as $campo)
                            <option value="{{ $campo->nombre }}">{{ $campo->nombre }}</option>
                        @endforeach
                    </x-select>
                </div>
                @if ($campoSeleccionado)

                    <x-flex class="w-full justify-end my-5">
                        <x-button @click="$wire.dispatch('registroCampania',{campoNombre:'{{ $campoSeleccionado }}'})">
                            <i class="fa fa-plus"></i> Registrar nueva campaña
                        </x-button>
                        @if ($campania)
                            <x-button type="button"
                                @click="$wire.dispatch('editarCampania',{campaniaId:{{ $campania->id }}})">
                                <i class="fa fa-edit"></i> Actualizar Campaña
                            </x-button>
                            <x-secondary-button type="button"
                                @click="$wire.dispatch('abrirCampaniaDetalle',{campaniaId:{{ $campania->id }}})">
                                <i class="fa fa-list mr-2"></i> Ver Detalle Completo
                            </x-secondary-button>
                        @endif
                    </x-flex>

                @endif

            </x-flex>
            @if ($campania)
                <x-flex class="w-full justify-between mt-5">
                    <div class="flex items-center gap-4">
                        <x-secondary-button type="button" wire:click="anteriorCampania"
                            class="{{ $hayCampaniaAnterior ? '' : 'opacity-0 invisible' }}">
                            <i class="fa fa-chevron-left"></i>
                        </x-secondary-button>

                        <x-h3>
                            Campaña {{ $campania->nombre_campania }}
                        </x-h3>

                        <x-secondary-button type="button" wire:click="siguienteCampania"
                            class="{{ $hayCampaniaPosterior ? '' : 'opacity-0 invisible' }}">
                            <i class="fa fa-chevron-right"></i>
                        </x-secondary-button>
                    </div>


                </x-flex>
            @endif
        </x-spacing>
    </x-card>
    @if ($campania)
        <div class="mt-5">
            <x-tabs default-value="informacion_general">
                <x-card2>
                    <x-tabs-list class="mb-4">
                        <x-tabs-trigger value="informacion_general">Información General</x-tabs-trigger>
                        <x-tabs-trigger value="poblacion_plantas">Población Plantas</x-tabs-trigger>
                        <x-tabs-trigger value="evaluacion_brotes">Evaluación de Brotes</x-tabs-trigger>
                        <x-tabs-trigger value="infestacion">Infestación</x-tabs-trigger>
                        <x-tabs-trigger value="reinfestacion">Re-Infestación</x-tabs-trigger>
                        <x-tabs-trigger value="cosecha_madres">Cosecha de Madres</x-tabs-trigger>
                        <x-tabs-trigger value="evaluacion_cosecha">Evaluación Cosecha</x-tabs-trigger>
                        <x-tabs-trigger value="cosecha">Cosecha</x-tabs-trigger>
                        <x-tabs-trigger value="porcentaje_acido_carminico">% ácido carmínico</x-tabs-trigger>
                        <x-tabs-trigger value="fertilizacion">Fertilización</x-tabs-trigger>
                        <x-tabs-trigger value="aplicaciones_fitosanitarias">Aplicaciones Fitosanitarias</x-tabs-trigger>
                        <x-tabs-trigger value="riego">Riego</x-tabs-trigger>
                        <x-tabs-trigger value="etapas">Etapas</x-tabs-trigger>
                        <x-tabs-trigger value="mano_obra_costos">Mano de obra - costos</x-tabs-trigger>
                    </x-tabs-list>
                    </x-card>


                    <x-tabs-content value="informacion_general">
                        <x-card2>
                            <x-card-header>
                                <x-card-title>Información General</x-card-title>
                            </x-card-header>
                            <x-card-content>
                                @include('livewire.campania-component.grupo-informacion-general')
                            </x-card-content>
                        </x-card2>
                    </x-tabs-content>

                    <x-tabs-content value="poblacion_plantas">
                        <livewire:poblacion-plantas-por-campania-component campaniaId="{{ $campania->id }}"
                            wire:key="poblacion_plantas.{{ $campania->id }}" />
                    </x-tabs-content>

                    <x-tabs-content value="evaluacion_brotes">
                        <livewire:evaluacion-brotes-x-piso-por-campania-component campaniaId="{{ $campania->id }}"
                            wire:key="brotes_x_piso.{{ $campania->id }}" />

                        <livewire:reporte-campo-evaluacion-brotes-form-component campaniaUnica="{{ true }}"
                            wire:key="reporte_brotes_form.{{ $campania->id }}" />
                    </x-tabs-content>

                    <x-tabs-content value="infestacion">
                        <livewire:infestacion-por-campania-component campaniaId="{{ $campania->id }}"
                            wire:key="infestacion.{{ $campania->id }}" />
                    </x-tabs-content>

                    <x-tabs-content value="reinfestacion">
                        <livewire:infestacion-por-campania-component campaniaId="{{ $campania->id }}"
                            tipo="reinfestacion" wire:key="reinfestacion.{{ $campania->id }}" />
                    </x-tabs-content>

                    <x-tabs-content value="cosecha_madres">
                        @include('livewire.campania-component.grupo-cosecha-madres')
                    </x-tabs-content>

                    <x-tabs-content value="evaluacion_cosecha">
                        @include('livewire.campania-component.grupo-evaluacion-cosecha')
                    </x-tabs-content>

                    <x-tabs-content value="cosecha">
                        @include('livewire.campania-component.grupo-cosecha')
                    </x-tabs-content>

                    <x-tabs-content value="porcentaje_acido_carminico">
                        @include('livewire.campania-component.grupo-porcentaje-acido-carminico')
                    </x-tabs-content>

                    <x-tabs-content value="fertilizacion">
                        @include('livewire.campania-component.grupo-fertilizacion')
                    </x-tabs-content>

                    <x-tabs-content value="aplicaciones_fitosanitarias">
                        @include('livewire.campania-component.grupo-aplicaciones-fitosanitarias')
                    </x-tabs-content>

                    <x-tabs-content value="riego">
                        @include('livewire.campania-component.grupo-riego')
                    </x-tabs-content>

                    <x-tabs-content value="etapas">
                        @include('livewire.campania-component.grupo-etapas')
                    </x-tabs-content>
                    <x-tabs-content value="mano_obra_costos">
                        <livewire:poblacion-plantas-por-campania-component campaniaId="{{ $campania->id }}"
                            wire:key="mano_obra_costos.{{ $campania->id }}" />
                    </x-tabs-content>
                    
            </x-tabs>
        </div>
    @endif
</div>
