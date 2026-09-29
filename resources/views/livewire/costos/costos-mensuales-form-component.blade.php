<div x-data="formCostosMensuales">
    <x-dialog-modal wire:model.live="mostrarFormCostosMensuales" maxWidth="lg">

        {{-- TITLE --}}
        <x-slot name="title">
            Agregar Nuevo Costo
            <p class="text-sm text-gray-500 dark:text-gray-400 font-normal">
                Complete todos los campos para registrar los costos del mes
            </p>
        </x-slot>

        {{-- CONTENT --}}
        <x-slot name="content">
            {{-- PASO 1 --}}
            <div x-show="paso === 1" class="space-y-6">

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Primero seleccione el año y el mes del registro.
                </p>

                <x-flex>
                    <x-select label="Año" wire:model="form.anio" error="form.anio" placeholder="Seleccione año">
                        @foreach ($aniosDisponibles as $anio)
                            <option value="{{ $anio }}">{{ $anio }}</option>
                        @endforeach
                    </x-select>


                    <x-select label="Mes" wire:model="form.mes" error="form.mes" placeholder="Seleccione mes">
                        @foreach ($meses as $i => $mes)
                            <option value="{{ $i + 1 }}">{{ $mes }}</option>
                        @endforeach
                    </x-select>
                </x-flex>

            </div>
            {{-- PASO 2 --}}
            <div x-show="paso === 2" x-cloak class="space-y-6">

                {{-- COSTOS FIJOS --}}
                <x-card class="bg-blue-500/10 dark:bg-blue-500/5">
                    <x-h3>Costos Fijos</x-h3>

                    @php
                        $fijos = [
                            'administrativo' => 'Administrativo',
                            'financiero' => 'Financiero',
                            'gastos_oficina' => 'Gastos de Oficina',
                            'depreciaciones' => 'Depreciaciones',
                            'costo_terreno' => 'Costo de Terreno',
                        ];
                    @endphp

                    @foreach ($fijos as $key => $label)
                        <div class="mt-4">
                            <x-label>{{ $label }}</x-label>

                            <div class="grid grid-cols-2 gap-3">
                                <x-input type="number" step="0.01" label="Blanco"
                                    wire:model.defer="form.fijo_{{ $key }}_blanco" />
                                <x-input type="number" step="0.01" label="Negro"
                                    wire:model.defer="form.fijo_{{ $key }}_negro" />
                            </div>
                        </div>
                    @endforeach
                </x-card>

                {{-- COSTOS OPERATIVOS --}}
                <x-card class="bg-purple-500/10 dark:bg-purple-500/5">
                    <x-h3>Costos Operativos</x-h3>
                    @php
                        $operativos = [
                            'servicios_fundo' => 'Servicios del Fundo',
                            'mano_obra_indirecta' => 'Mano de Obra Indirecta',
                        ];
                    @endphp

                    @foreach ($operativos as $key => $label)
                        <div class="mt-4">
                            <x-label>{{ $label }}</x-label>

                            <div class="grid grid-cols-2 gap-3">
                                <x-input type="number" step="0.01" label="Blanco"
                                    wire:model.defer="form.operativo_{{ $key }}_blanco" />
                                <x-input type="number" step="0.01" label="Negro"
                                    wire:model.defer="form.operativo_{{ $key }}_negro" />
                            </div>
                        </div>
                    @endforeach
                </x-card>

            </div>

        </x-slot>

        {{-- FOOTER --}}
        <x-slot name="footer">
            {{-- PASO 1 --}}
            <div x-show="paso === 1">
                <x-button variant="secondary" wire:click="$set('mostrarFormCostosMensuales', false)">
                    Cancelar
                </x-button>

                <x-button class="ml-2" wire:click="cargarCostoMensual">
                    Siguiente
                </x-button>
            </div>

            {{-- PASO 2 --}}
            <div x-show="paso === 2">
                <x-button variant="secondary" @click="paso = 1">
                    Anterior
                </x-button>

                <x-button class="ml-2" wire:click="guardarCostoMensual">
                    Guardar Costo
                </x-button>
            </div>
        </x-slot>

    </x-dialog-modal>
    <x-loading wire:loading/>
</div>
@script
<script>
    Alpine.data('formCostosMensuales', () => ({
        paso: @entangle('paso'),
        init() {

        }
    }));
</script>
@endscript