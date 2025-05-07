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
                        <i class="fa fa-edit"></i> Actualizar información
                    </x-button>
                </x-flex>
            @endif
        </x-spacing>
    </x-card>
    @if ($campania)
        <x-flex class="w-full justify-between my-5">
            <x-h3>Información General</x-h3>
            <x-secondary-button type="button" @click="$wire.dispatch('abrirCampaniaDetalle',{campaniaId:{{$campania->id}}})">
                <i class="fa fa-list mr-2"></i> Ver Detalle Completo
            </x-secondary-button>
        </x-flex>
        <x-card>

            <x-spacing>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-2 items-center">
                            <div class="font-medium text-gray-700">Lote:</div>
                            <div>{{ $campania->campo }}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 items-center">
                            <div class="font-medium text-gray-700">Variedad de tuna:</div>
                            <div>{{ $campania->variedad_tuna }}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 items-center">
                            <div class="font-medium text-gray-700">Campaña:</div>
                            <div>{{ $campania->nombre_campania }}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 items-center">
                            <div class="font-medium text-gray-700">Área:</div>
                            <div>{{ $campania->campo_model->area }}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 items-center">
                            <div class="font-medium text-gray-700">Sistema de cultivo:</div>
                            <div>{{ $campania->sistema_cultivo }}</div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-2 items-center">
                            <div class="font-medium text-gray-700">Pencas x Hectárea:</div>
                            <div>{{ $campania->pencas_x_hectarea }}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 items-center">
                            <div class="font-medium text-gray-700">T.C.:</div>
                            <div>{{ $campania->tipo_cambio }}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 items-center">
                            <div class="font-medium text-gray-700">Fecha de siembra:</div>
                            <div>{{ $campania->fecha_siembra }}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 items-center">
                            <div class="font-medium text-gray-700">Fecha de inicio de Campaña:</div>
                            <div>{{ $campania->fecha_inicio }}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 items-center">
                            <div class="font-medium text-gray-700">Fin de Campaña:</div>
                            <div>{{ $campania->fecha_fin }}</div>
                        </div>
                    </div>
                </div>
            </x-spacing>
        </x-card>

        @livewire('poblacion-plantas-por-campania-component', ['campaniaId' => $campania->id], key($campania->id))

        @livewire('evaluacion-brotes-x-piso-por-campania-component', ['campaniaId' => $campania->id],key($campania->id))

        @livewire('reporte-campo-evaluacion-brotes-form-component',['campaniaUnica' => true],key($campania->id))

        @livewire('infestacion-por-campania-component', ['campaniaId' => $campania->id],key($campania->id))
        
    @endif
</div>
