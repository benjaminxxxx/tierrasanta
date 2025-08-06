<div>
    <x-modal wire:model.live="mostrarFormularioGastosAdicionales" maxWidth="full">
        <div x-data="gestionGastosAdicionales">
            <div class="px-6 py-4">
                <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Administración de costos adicionales durante la semana
                </div>

                <div class="mt-4 min-h-[200px] overflow-auto">
                   
                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th>Grupo</x-th>
                                <x-th>Descripción</x-th>
                                <x-th>Fecha</x-th>
                                <x-th>Monto</x-th>
                                <x-th></x-th>
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            <template x-for="(item, index) in gastos" :key="index">
                                <x-tr>
                                    <x-td>
                                        <x-select x-model="item.grupo">
                                            <option value="">SELECCIONE</option>
                                            @foreach ($grupos as $grupo)
                                                <option value="{{ $grupo }}">{{ $grupo }}</option>
                                            @endforeach
                                        </x-select>
                                    </x-td>
                                    <x-td>
                                        <x-input type="text" x-model="item.descripcion"
                                            @input="agregarFilaSiEsUltima(index)"
                                            class="form-input w-full" />
                                    </x-td>
                                    <x-td>
                                        <x-input type="date" x-model="item.fecha"
                                            class="form-input w-full" />
                                    </x-td>
                                    <x-td>
                                        <x-input type="number" step="0.01" x-model="item.monto"
                                            class="form-input w-full text-right" />
                                    </x-td>
                                    <x-td>
                                        <x-danger-button type="button" @click="eliminarFila(index)" >
                                            <i class="fa fa-trash"></i>
                                        </x-danger-button>
                                    </x-td>
                                </x-tr>
                            </template>
                        </x-slot>
                        <x-slot name="tfoot">
                            <x-tr>
                                <x-td colspan="3" class="text-right font-semibold">Total</x-td>
                                <x-td x-text="formatearMonto(totalMonto)" class="text-right"></x-td>
                                <x-td></x-td>
                            </x-tr>
                        </x-slot>
                    </x-table>
                </div>
            </div>

            <div class="flex flex-row justify-end px-6 py-4 bg-whiten dark:bg-boxdarkbase">
                <x-flex>
                    <x-secondary-button wire:click="$set('mostrarFormularioGastosAdicionales', false)"
                        wire:loading.attr="disabled">
                        Cerrar
                    </x-secondary-button>
                    <x-button @click="guardar">
                        <i class="fa fa-save"></i> Guardar cambios
                    </x-button>
                </x-flex>
            </div>
        </div>
    </x-modal>
</div>

@script
<script>
    Alpine.data('gestionGastosAdicionales', () => ({
        gastos: @entangle('gastos'),
        grupos: @js($grupos),

        agregarFilaSiEsUltima(index) {
            if (index === this.gastos.length - 1) {
                this.gastos.push({ grupo: '', descripcion: '', fecha: '', monto: '' });
            }
        },

        eliminarFila(index) {
            this.gastos.splice(index, 1);
        },

        formatearMonto(valor) {
            let num = parseFloat(valor);
            return isNaN(num) ? '0.00' : num.toFixed(2);
        },

        get totalMonto() {
            return this.gastos.reduce((sum, item) => {
                let val = parseFloat(item.monto);
                return sum + (isNaN(val) ? 0 : val);
            }, 0);
        },

        guardar() {
            const datosFiltrados = this.gastos.filter(
                item => item.grupo || item.descripcion || item.fecha || item.monto
            );
            $wire.storeTableDataGuardarDatosAdicionales(datosFiltrados);
        }
    }));
</script>
@endscript
