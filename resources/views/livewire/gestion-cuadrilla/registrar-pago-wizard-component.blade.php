{{-- resources/views/livewire/gestion-cuadrilla/registrar-pago-wizard-component.blade.php --}}
<div>

    <x-dialog-modal wire:model="mostrar" maxWidth="complete">
        <x-slot name="title">Registrar Pago</x-slot>

        <x-slot name="content">
            <div class="flex items-center gap-2 mb-6 text-sm">
                @foreach (['Tipo de pago', 'Periodo', 'Rango de fechas', 'Totalizados'] as $i => $label)
                    <div class="flex items-center gap-2">
                        <span @class([
                            'w-6 h-6 rounded-full flex items-center justify-center text-xs',
                            'bg-primary text-white' => $paso > $i + 1,
                            'bg-primary/20 text-primary border border-primary' => $paso === $i + 1,
                            'bg-base-200 text-base-400' => $paso < $i + 1,
                        ])>{{ $i + 1 }}</span>
                        <span
                            class="{{ $paso === $i + 1 ? 'font-semibold' : 'text-base-400' }}">{{ $label }}</span>
                    </div>
                    @if (!$loop->last)
                        <div class="w-6 h-px bg-base-300"></div>
                    @endif
                @endforeach
            </div>

            @if ($paso === 1)
                <x-subtitle class="mb-3">¿Qué vas a pagar?</x-subtitle>
                <div class="grid grid-cols-1 gap-3">
                    <button type="button" wire:click="seleccionarTipoPago('PAGO_CUADRILLA')"
                        class="text-left p-4 rounded-lg border border-base-300 hover:border-primary transition-colors">
                        <span class="font-semibold">Pago personal</span>
                        <p class="text-sm text-base-400">Jornales de la cuadrilla</p>
                    </button>
                    <button type="button" wire:click="seleccionarTipoPago('PAGO_BONOS_ACUMULADOS')"
                        class="text-left p-4 rounded-lg border border-base-300 hover:border-primary transition-colors">
                        <span class="font-semibold">Pago de bonos</span>
                        <p class="text-sm text-base-400">Bonos acumulados pendientes</p>
                    </button>
                    <button type="button" wire:click="seleccionarTipoPago('GASTO_ADICIONAL')"
                        class="text-left p-4 rounded-lg border border-base-300 hover:border-primary transition-colors">
                        <span class="font-semibold">Pago de gastos adicionales</span>
                        <p class="text-sm text-base-400">Comisiones, movilidad, etc.</p>
                    </button>
                    <button type="button" wire:click="seleccionarTipoPago('PAGO_CUADRILLA_EXTRAS')"
                        class="text-left p-4 rounded-lg border border-base-300 hover:border-primary transition-colors">
                        <span class="font-semibold">Pago personal - turno tarde</span>
                        <p class="text-sm text-base-400">Jornales de la cuadrilla que desearon asistir horas extras.</p>
                    </button>
                </div>
            @endif

            @if ($paso === 2)
                <x-subtitle class="mb-3">¿Cuadrilla de qué periodo?</x-subtitle>
                <div class="grid grid-cols-3 gap-3">
                    <button type="button" wire:click="seleccionarTipoPeriodo('SEMANAL')"
                        class="text-center p-4 rounded-lg border border-base-300 hover:border-primary transition-colors">Semanal</button>
                    <button type="button" wire:click="seleccionarTipoPeriodo('QUINCENAL')"
                        class="text-center p-4 rounded-lg border border-base-300 hover:border-primary transition-colors">Quincenal</button>
                    <button type="button" wire:click="seleccionarTipoPeriodo('MENSUAL')"
                        class="text-center p-4 rounded-lg border border-base-300 hover:border-primary transition-colors">Mensual</button>
                </div>
            @endif

            @if ($paso === 3)
                <x-subtitle class="mb-3">Rango de fechas</x-subtitle>

                <div class="flex gap-2 mb-4">
                    <x-button size="sm" :variant="$modoRango === 'actual' ? 'primary' : 'secondary'" wire:click="cambiarModoRango('actual')">Periodo
                        actual</x-button>
                    <x-button size="sm" :variant="$modoRango === 'personalizado' ? 'primary' : 'secondary'" wire:click="cambiarModoRango('personalizado')">Otro
                        periodo</x-button>
                </div>

                @if ($modoRango === 'actual')
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label>Desde</x-label>
                            <x-subtitle>{{ \Carbon\Carbon::parse($fechaInicio)->translatedFormat('d \d\e F Y') }}</x-subtitle>
                        </div>
                        <div>
                            <x-label>Hasta</x-label>
                            <x-subtitle>{{ \Carbon\Carbon::parse($fechaFin)->translatedFormat('d \d\e F Y') }}</x-subtitle>
                        </div>
                    </div>
                @elseif($tipoPeriodo === 'MENSUAL')
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-label>Año</x-label>
                            <x-input type="number" wire:model="anioSeleccionado" />
                            <x-input-error for="anioSeleccionado" />
                        </div>
                        <div>
                            <x-label>Mes</x-label>
                            <x-select wire:model="mesSeleccionado">
                                @foreach (range(1, 12) as $m)
                                    <option value="{{ $m }}">
                                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                    </option>
                                @endforeach
                            </x-select>
                            <x-input-error for="mesSeleccionado" />
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-selector-dia type="date" wire:model="fechaInicio" label="Fecha inicio"
                                error="fechaInicio" />
                        </div>
                        <div>
                            <x-selector-dia type="date" wire:model="fechaFin" label="Fecha fin" error="fechaFin" />
                        </div>
                    </div>
                @endif
            @endif

            @if ($paso === 4)
                <x-subtitle class="mb-1">Totalizados</x-subtitle>
                <p class="text-sm text-base-400 mb-4">
                    Tipo: <span class="font-semibold text-base-content">{{ $tipoPago }}</span> ·
                    Periodo: <span class="font-semibold text-base-content">{{ ucfirst($tipoPeriodo) }}</span> ·
                    <span class="font-semibold text-base-content">
                        {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} -
                        {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
                    </span>
                </p>
                @if (in_array($tipoPago, ['PAGO_CUADRILLA', 'PAGO_CUADRILLA_EXTRAS']))
                    @include('livewire.gestion-cuadrilla.partials.detalle-pago-cuadrilla')
                @elseif($tipoPago === 'GASTO_ADICIONAL')
                    @include('livewire.gestion-cuadrilla.partials.detalle-pagos-adicionales')
                @elseif ($tipoPago === 'PAGO_BONOS_ACUMULADOS')
                    @include('livewire.gestion-cuadrilla.partials.tabla-pago-bonos-acumulados', [
                        'matriz' => $matrizPago,
                    ])
                @endif
            @endif
        </x-slot>

        <x-slot name="footer">
            @if ($paso > 1)
                <x-button variant="secondary" wire:click="volver">Atrás</x-button>
            @endif
            <x-button variant="secondary" wire:click="cerrar">Cancelar</x-button>
            @if ($paso === 3)
                <x-button variant="primary" wire:click="confirmarRango">Continuar</x-button>
            @endif
        </x-slot>
    </x-dialog-modal>
</div>
