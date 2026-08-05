<div class="space-y-6">
    <x-flex class="justify-between">
        <div>
            <x-title>
                Planilla Mensual
            </x-title>
            <x-subtitle>
                Gestión y consolidación de datos mensuales para la generación del PLAME
            </x-subtitle>
        </div>
        @include('comun.selector-mes-base')
    </x-flex>

    <x-card>
        <x-flex class="justify-between">
            <div
                class="inline-flex p-1 rounded-lg bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800">
                <button type="button" wire:click="cambiarVista('Proyectada')" class="px-4 py-2 text-sm font-semibold rounded-md transition-all duration-150
                    {{ $vista === 'Proyectada'
    ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 shadow-sm'
    : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                    <i class="fa fa-chart-line mr-1.5"></i> Proyectada
                </button>

                <button type="button" wire:click="cambiarVista('PLAME')" class="px-4 py-2 text-sm font-semibold rounded-md transition-all duration-150
                    {{ $vista === 'PLAME'
    ? 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 shadow-sm'
    : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                    <i class="fa fa-file-invoice mr-1.5"></i> PLAME
                </button>
            </div>
            <div>
                @if ($planillaMensual && $planillaMensual->excel)
                    <x-button href="{{ Storage::disk('public')->url($planillaMensual->excel) }}">
                        <i class="fa fa-file-excel"></i> Descargar Planilla
                    </x-button>
                @endif
            </div>
        </x-flex>
    </x-card>

    @if ($vista == 'Proyectada')
        <livewire:gestion-planilla.planilla-proyectada-component :mes="$mes" :anio="$anio"
            wire:key="cpm_{{ $mes }}_{{ $anio }}_{{ $vista }}" />
    @else
        <livewire:gestion-planilla.planilla-plame-component :mes="$mes" :anio="$anio"
            wire:key="cpm_{{ $mes }}_{{ $anio }}_{{ $vista }}" />
    @endif

    <livewire:gestion-planilla.apertura-planilla-modal />
    <x-inferior-derecha>
        <x-button @click="$wire.dispatch('abrir-apertura-planilla',{mes: {{ $mes }}, anio: {{ $anio }}})">
            <i class="fa fa-refresh"></i> Generar Planilla Proyectada
        </x-button>
    </x-inferior-derecha>

    <x-loading wire:loading />
</div>