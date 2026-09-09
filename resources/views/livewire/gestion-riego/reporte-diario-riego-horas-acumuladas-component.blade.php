<x-dialog-modal maxWidth="lg" wire:model="mostrarHorasAcumuladasForm">
    <x-slot name="title">
        Registrar Uso de Horas Acumuladas
    </x-slot>

    <x-slot name="content">
        <div class="space-y-4">

            {{-- Encabezado: campo + total disponible --}}
            <div class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-700
                        bg-gray-50 dark:bg-gray-800/60 px-4 py-3">
                <div>
                    <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Campo</span>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">FDM</p>
                </div>
                @if ($resumenRiego)
                    <div class="text-right">
                        <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Disponible</span>
                        <p class="text-lg font-bold text-green-600 dark:text-green-400">
                            {{ $resumenRiego->disponible_formateado }}
                        </p>
                    </div>
                @endif
            </div>

            {{-- Origen de las horas: contenedor con su propio scroll --}}
            @if (!empty($origenesAcumulados))
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fa fa-history text-blue-500 dark:text-blue-400 text-xs"></i>
                        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Estas horas provienen de
                        </span>
                    </div>

                    <div class="max-h-32 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700
                            divide-y divide-gray-100 dark:divide-gray-700
                            bg-white dark:bg-gray-900">
                        @foreach ($origenesAcumulados as $origen)
                            <div
                                class="flex items-center justify-between px-3 py-2 text-sm {{ $origen['disponible'] ? '' : 'bg-red-50/50 dark:bg-red-950/20' }}">

                                {{-- Fecha del origen --}}
                                <span class="text-gray-600 dark:text-gray-300">
                                    {{ \Carbon\Carbon::parse($origen['fecha'])->format('d/m/Y') }}
                                </span>

                                {{-- Horas y estado de disponibilidad --}}
                                <div class="flex items-center gap-3">
                                    <span
                                        class="font-semibold {{ $origen['disponible'] ? 'text-gray-800 dark:text-white' : 'text-red-600 dark:text-red-400 line-through' }}">
                                        {{ $origen['formateado'] }}
                                    </span>

                                    @if ($origen['disponible'])
                                        <span
                                            class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-400 px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800">
                                            <i class="fa fa-check text-[10px]"></i> Disponible
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1 text-xs font-medium text-red-600 bg-red-100 dark:bg-red-950/60 dark:text-red-400 px-2 py-0.5 rounded-full border border-red-200 dark:border-red-800">
                                            <i class="fa fa-times text-[10px]"></i> Fecha futura
                                        </span>
                                    @endif
                                </div>

                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Formulario de horas a usar --}}
            <div x-data="{
                    inicio: @entangle('acumulado.horaInicio'),
                    fin: @entangle('acumulado.horaFin'),
                    get total() {
                        if (!this.inicio || !this.fin) return '';

                        const [hi, mi] = this.inicio.split(':').map(Number);
                        const [hf, mf] = this.fin.split(':').map(Number);

                        let inicioMin = hi * 60 + mi;
                        let finMin = hf * 60 + mf;

                        if (finMin < inicioMin) {
                            finMin += 24 * 60;
                        }

                        const diffHoras = (finMin - inicioMin) / 60;
                        return diffHoras.toFixed(2);
                    }
                }" class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-900">

                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 block mb-3">
                    Horas a utilizar
                </span>

                <div class="space-y-3">
                    {{-- Grilla de inputs --}}
                    <div class="grid grid-cols-3 gap-3 items-start">
                        <div>
                            <x-input type="time" label="Hora de Inicio" x-model="inicio" class="w-full" />
                        </div>
                        <div>
                            <x-input type="time" label="Hora Final" x-model="fin" class="w-full" />
                        </div>
                        <div>
                            <x-label class="text-sm">Total Horas</x-label>
                            <div class="mt-1 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                        px-3 py-2 text-center font-bold text-lg text-gray-800 dark:text-white"
                                x-text="total || '0.00'">
                            </div>
                        </div>
                    </div>

                    {{-- Error desplegado debajo de la grilla usando x-warning --}}
                    @error('acumulado.horaFin')
                        <x-warning>
                            {{ $message }}
                        </x-warning>
                    @enderror
                </div>
            </div>
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