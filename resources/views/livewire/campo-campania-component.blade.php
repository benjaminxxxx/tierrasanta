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
                    <div class="mb-2">
                        <x-button @click="$wire.dispatch('registroCampania',{campoNombre:'{{ $campoSeleccionado }}'})">
                            <i class="fa fa-plus"></i> Registrar nueva campaña
                        </x-button>
                    </div>
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
                    <x-button type="button" @click="$wire.dispatch('editarCampania',{campaniaId:{{ $campania->id }}})">
                        <i class="fa fa-edit"></i> Actualizar Campaña
                    </x-button>
                </x-flex>
            @endif
        </x-spacing>
    </x-card>
    @if ($campania)
        <x-flex class="w-full justify-end my-5">
            <x-secondary-button type="button"
                @click="$wire.dispatch('abrirCampaniaDetalle',{campaniaId:{{ $campania->id }}})">
                <i class="fa fa-list mr-2"></i> Ver Detalle Completo
            </x-secondary-button>
        </x-flex>

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
            </x-tabs>
        </div>
    @endif
</div>
