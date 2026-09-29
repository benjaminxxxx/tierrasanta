<div>
    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
            Distribución de Cosecha de Mama
        </x-slot>

        <x-slot name="content">
            <div>
                <div x-data="{
                    dias: @entangle('diasPosterioresCosecha'),
                    actualizar() {
                        $wire.set('diasPosterioresCosecha', this.dias);
                    }
                }" class="my-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Días posteriores a la cosecha: <strong
                            x-text="dias"></strong></label>

                    <input type="range" min="0" max="30" step="1" x-model="dias" @change="actualizar"
                        class="w-full">
                </div>
                @if($cosecha)
                <div class="my-4">
                    <x-success>
                        <p>
                            Esta cosecha se realizó en la fecha {{ formatear_fecha($cosecha->fecha) }}, y se están buscando las infestaciones o reinfestaciones posteriores a esta fecha de hasta {{ $diasPosterioresCosecha }} días después, puede modificar este rango desde 0 a 30 días
                        </p>
                    </x-success>
                </div>
                @endif
                
                @if ($infestaciones && $infestaciones->count())
                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th>Fecha</x-th>
                                <x-th>Tipo</x-th>
                                <x-th>Campo Origen</x-th>
                                <x-th>Campo Destino</x-th>
                                <x-th>Kg Madres</x-th>
                                <x-th>Método</x-th>
                            </x-tr>
                        </x-slot>

                        <x-slot name="tbody">
                            @foreach ($infestaciones as $infestacion)
                                <x-tr>
                                    <x-td>{{ $infestacion->fecha }}</x-td>
                                    <x-td>{{ ucfirst($infestacion->tipo_infestacion) }}</x-td>
                                    <x-td>{{ $infestacion->campo_origen_nombre }}</x-td>
                                    <x-td>{{ $infestacion->campo_nombre }}</x-td>
                                    <x-td>{{ number_format($infestacion->kg_madres, 2) }}</x-td>
                                    <x-td>{{ ucfirst($infestacion->metodo) }}</x-td>
                                </x-tr>
                            @endforeach
                        </x-slot>
                    </x-table>
                    <div class="my-4">
                         <div>
                    <x-h3 class="mb-3">Destino fresco</x-h3>
                
                        <x-input type="text" label="Campos para infestador cartón (separe por guiones - )"
                            wire:model="cosch_destino_carton" />
                   
                        <x-input type="text" label="Campos para infestador tubo (separe por guiones - )"
                            wire:model="cosch_destino_tubo" />
              
                        <x-input type="text" label="Campos para infestador malla (separe por guiones - )"
                            wire:model="cosch_destino_malla" />
                </div>
                    </div>
                @else
                    <x-warning>No hay infestaciones relacionadas</x-warning>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-flex>
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Guardar
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>
