{{-- resources/views/livewire/planilla/apertura-modal.blade.php --}}

<x-dialog-modal wire:model.live="showingAperturaPlanilla">
    <x-slot name="title">
        Apertura de Planilla Mensual
    </x-slot>

    <x-slot name="content">
        <div x-data="{ dias: $wire.entangle('dias_laborables') }" class="space-y-6">

            {{-- Periodo: bloqueado, solo informativo --}}
            <div>
                <x-subtitle>Periodo</x-subtitle>
                <p class="mt-1 text-lg font-semibold text-zinc-800 dark:text-zinc-100 uppercase">
                    {{ \Carbon\Carbon::create()->month($mes)->translatedFormat('F') }} {{ $anio }}
                </p>
            </div>

            {{-- Días laborables (editable) + Total horas (calculado en vivo) --}}
            <div class="grid grid-cols-2 gap-4">
                <div>

                    <x-input wire:model="dias_laborables" label="Días laborables" x-model.number="dias" type="number"
                        min="1" max="31" />
                </div>

                <div>
                    <x-input x-bind:value="dias ? (dias * 8) : ''" disabled label="Total horas" />
                    <p class="mt-1 text-xs text-zinc-400">días × 8 horas</p>
                </div>
            </div>

            {{-- Sueldo base --}}
            <div>
                <x-label value="Sueldo base (remuneración básica)" />
                <div class="mt-1 flex gap-2">
                    <x-input wire:model="remuneracion_basica" type="number" step="0.01" placeholder="Aún no calculado"
                        class="flex-1" />
                    <x-button type="button" variant="secondary" wire:click="calcularSueldoBase"
                        wire:loading.attr="disabled">
                        {{ $remuneracion_basica ? 'Recalcular' : 'Calcular' }}
                    </x-button>
                </div>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    El cálculo se basa en la RMV configurada en <span class="font-medium">Parámetros</span>.
                    Si la RMV ha cambiado este mes, primero agrega la nueva configuración
                    <a href="{{ route('planilla.parametros') }}"
                        class="text-blue-600 dark:text-blue-400 underline hover:no-underline" target="_blank">aquí</a>.
                </p>
            </div>

            {{-- Excel generado: solo aparece si ya existe --}}
            @if ($excel)
                <x-button href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($excel) }}" target="_blank"
                    variant="secondary" class="w-full justify-center">
                    📄 Último Excel generado — listo para descarga
                </x-button>
            @endif

            {{-- Valores copiados de configuración: bloqueados en bloque, un solo botón para refrescarlos --}}
            <div class="border-t border-zinc-200 dark:border-zinc-800 pt-4">
                <div class="flex items-center justify-between mb-3">
                    <x-subtitle>Valores desde configuración</x-subtitle>
                    <div class="flex gap-2">
                        <x-button type="button" variant="secondary" wire:click="actualizarDesdeConfiguracion"
                            wire:loading.attr="disabled">
                            <i class="fa fa-sync"></i> Actualizar desde configuración
                        </x-button>
                        <x-button type="button" variant="secondary" href="{{ route('planilla.parametros') }}"
                            target="_blank">
                            <i class="fa fa-external-link-alt"></i> Modificar
                        </x-button>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-3">
                    <div><x-label value="RMV" /><x-input :value="$rmv" disabled
                            class="mt-1 block w-full bg-zinc-100 dark:bg-zinc-900" /></div>
                    <div><x-label value="Asig. familiar" /><x-input :value="$asignacion_familiar" disabled
                            class="mt-1 block w-full bg-zinc-100 dark:bg-zinc-900" /></div>
                    <div><x-label value="Gratificaciones" /><x-input :value="$gratificaciones" disabled
                            class="mt-1 block w-full bg-zinc-100 dark:bg-zinc-900" /></div>
                    <div><x-label value="EsSalud gratif." /><x-input :value="$essalud_gratificaciones" disabled
                            class="mt-1 block w-full bg-zinc-100 dark:bg-zinc-900" /></div>
                    <div><x-label value="BETA 30%" /><x-input :value="$beta30" disabled
                            class="mt-1 block w-full bg-zinc-100 dark:bg-zinc-900" /></div>
                    <div><x-label value="EsSalud" /><x-input :value="$essalud" disabled
                            class="mt-1 block w-full bg-zinc-100 dark:bg-zinc-900" /></div>
                    <div><x-label value="Vida ley" /><x-input :value="$vida_ley" disabled
                            class="mt-1 block w-full bg-zinc-100 dark:bg-zinc-900" /></div>
                    <div><x-label value="Pensión SCTR" /><x-input :value="$pension_sctr" disabled
                            class="mt-1 block w-full bg-zinc-100 dark:bg-zinc-900" /></div>
                    <div><x-label value="EsSalud EPS" /><x-input :value="$essalud_eps" disabled
                            class="mt-1 block w-full bg-zinc-100 dark:bg-zinc-900" /></div>
                    <div><x-label value="Rem. básica EsSalud" /><x-input :value="$rem_basica_essalud" disabled
                            class="mt-1 block w-full bg-zinc-100 dark:bg-zinc-900" /></div>
                    <div><x-label value="CTS" /><x-input :value="$cts" disabled
                            class="mt-1 block w-full bg-zinc-100 dark:bg-zinc-900" /></div>
                </div>

                @if ($configuracionPendiente)
                    <p class="mt-3 text-xs text-amber-600 dark:text-amber-400">
                        <i class="fa fa-circle-info"></i> Estos valores aún no se han guardado. Se aplicarán al presionar
                        "Generar Planilla".
                    </p>
                @endif
            </div>

            {{-- Descuentos AFP / SNP: preview del snapshot que se aplicará al generar --}}
            <div class="border-t border-zinc-200 dark:border-zinc-800 pt-4 mt-4">
                <div class="flex items-center justify-between mb-3">
                    <x-subtitle>Descuentos AFP / SNP</x-subtitle>
                    <div class="flex gap-2">
                        <x-button type="button" variant="secondary" wire:click="actualizarDesdeDescuentos"
                            wire:loading.attr="disabled">
                            <i class="fa fa-sync"></i> Actualizar desde descuentos
                        </x-button>
                        <x-button type="button" variant="secondary" href="{{ route('descuentos_afp') }}"
                            target="_blank">
                            <i class="fa fa-external-link-alt"></i> Modificar
                        </x-button>
                    </div>
                </div>

                @if ($descuentosPreview)
                    <x-table>
                        <x-slot name="thead">
                            <x-tr :level="2">
                                <x-th value="AFP" />
                                <x-th value="Com. flujo" class="text-center" />
                                <x-th value="Com. saldo" class="text-center" />
                                <x-th value="Prima seguros" class="text-center" />
                                <x-th value="Aporte oblig." class="text-center" />
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @foreach ($descuentosPreview as $d)
                                <x-tr>
                                    <x-td class="font-semibold">{{ $d['referencia'] }}</x-td>
                                    <x-td class="text-center">{{ number_format($d['comision_flujo'], 2) }}%</x-td>
                                    <x-td class="text-center">{{ number_format($d['comision_saldo'], 2) }}%</x-td>
                                    <x-td class="text-center">{{ number_format($d['prima_seguros'], 2) }}%</x-td>
                                    <x-td class="text-center">{{ number_format($d['aporte_obligatorio'], 2) }}%</x-td>
                                </x-tr>
                            @endforeach
                        </x-slot>
                    </x-table>

                    <p class="mt-3 text-xs text-amber-600 dark:text-amber-400">
                        <i class="fa fa-circle-info"></i> Este cálculo se aplicará por código de AFP/modalidad al presionar
                        "Generar Planilla".
                    </p>
                @else
                    <p
                        class="text-sm text-zinc-400 dark:text-zinc-500 py-3 text-center border border-dashed border-zinc-300 dark:border-zinc-700 rounded">
                        Presiona "Actualizar desde descuentos" para ver la vista previa de este periodo.
                    </p>
                @endif
            </div>
        </div>
    </x-slot>

    <x-slot name="footer">
        <x-button variant="secondary" wire:click="$set('showingAperturaPlanilla', false)" wire:loading.attr="disabled">
            Cancelar
        </x-button>

        <x-button wire:click="generarPlanilla" wire:loading.attr="disabled" class="ml-2">
            <i class="fa fa-check"></i> Generar Planilla
        </x-button>
    </x-slot>
</x-dialog-modal>