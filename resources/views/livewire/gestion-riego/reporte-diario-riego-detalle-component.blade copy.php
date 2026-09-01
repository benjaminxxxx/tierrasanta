<x-dialog-modal maxWidth="lg" wire:model="mostrarHorasAcumuladasForm">
    <x-slot name="title">
        Registrar Uso de Horas Acumuladas
    </x-slot>

    <x-slot name="content">
        <div>
            <x-title value="FDM" />
        </div>
        <div class="mt-5 flex gap-5 items-start" x-data="{
                inicio: @entangle('acumulado.horaInicio'),
                fin: @entangle('acumulado.horaFin'),
                get total() {
                    if (!this.inicio || !this.fin) return '';
            
                    const [hi, mi] = this.inicio.split(':').map(Number);
                    const [hf, mf] = this.fin.split(':').map(Number);
            
                    let inicioMin = hi * 60 + mi;
                    let finMin = hf * 60 + mf;
            
                    // Si la hora final es del día siguiente
                    if (finMin < inicioMin) {
                        finMin += 24 * 60;
                    }
            
                    const diffHoras = (finMin - inicioMin) / 60;
                    return diffHoras.toFixed(2);
                }
            }">

            <x-input type="time" label="Hora de Inicio" x-model="inicio" class="w-auto" />
            <div>
                <x-input type="time" label="Hora Final" x-model="fin" class="w-auto" />
                <x-input-error for="acumulado.horaFin" />
            </div>
            <x-input type="number" label="Total Horas" readonly x-model="total" class="w-auto" />
        </div>

    </x-slot>

    <x-slot name="footer">
        <x-button variant="secondary" wire:click="$set('mostrarHorasAcumuladasForm', false)"
            wire:loading.attr="disabled">
            Cerrar
        </x-button>
        <x-button wire:click="registrarUsoHorasAcumuladas" wire:loading.attr="disabled">
            <i class="fa fa-save"></i> Registrar Uso de Horas Acumuladas
        </x-button>
    </x-slot>
</x-dialog-modal>