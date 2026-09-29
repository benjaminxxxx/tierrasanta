<div class="flex gap-2">
    <button type="button" wire:click="verDesgloseDias" class="cursor-pointer">
        <x-badge class="hover:opacity-80 transition">
            Horas Semana: {{ formatear_minutos_horas($minutosSemana) }}
        </x-badge>
    </button>

    <button type="button" wire:click="verDesgloseSemanas" class="cursor-pointer">
        <x-badge class="hover:opacity-80 transition">
            Horas Mes: {{ formatear_minutos_horas($minutosMes) }}
        </x-badge>
    </button>

    {{-- Clic en "Horas Semana" → días de la semana activa, con total al final --}}
    <x-dialog-modal wire:model.live="mostrarDesgloseDias">
        <x-slot name="title">Desglose diario de la semana</x-slot>
        <x-slot name="content">
            <div class="divide-y divide-gray-100 dark:divide-gray-700 rounded-lg border border-gray-200 dark:border-gray-700">
                @foreach ($desgloseDias as $dia)
                    <div class="flex justify-between px-3 py-2 text-sm {{ $dia['es_activo'] ? 'bg-blue-50 dark:bg-blue-900/30' : '' }}">
                        <span class="text-gray-600 dark:text-gray-300">
                            {{ $dia['dia_nombre'] }} {{ $dia['fecha_formateada'] }}
                            @if ($dia['es_activo'])
                                <span class="text-xs text-blue-500 dark:text-blue-400">(hoy)</span>
                            @endif
                        </span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ $dia['formateado'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-between px-3 py-3 mt-2 rounded-lg bg-gray-100 dark:bg-gray-800 font-bold">
                <span class="text-gray-700 dark:text-gray-200">Total semana</span>
                <span class="text-gray-900 dark:text-white">{{ $totalSemanaFormateado }}</span>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarDesgloseDias', false)">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Clic en "Horas Mes" → semanas completas que tocan el mes --}}
    <x-dialog-modal wire:model.live="mostrarDesgloseSemanas">
        <x-slot name="title">Desglose semanal del mes</x-slot>
        <x-slot name="content">
            @if (empty($desgloseSemanas))
                <x-label class="text-muted-foreground">Sin registros este mes.</x-label>
            @else
                <div class="divide-y divide-gray-100 dark:divide-gray-700 rounded-lg border border-gray-200 dark:border-gray-700">
                    @foreach ($desgloseSemanas as $semana)
                        <div class="flex justify-between px-3 py-2 text-sm">
                            <span class="text-gray-600 dark:text-gray-300">
                                {{ $semana['rango'] }}
                                @if ($semana['incluye_otro_mes'])
                                    <span class="text-xs text-amber-500 dark:text-amber-400" title="Semana compartida con otro mes">*</span>
                                @endif
                            </span>
                            <span class="font-semibold text-gray-800 dark:text-white">{{ $semana['formateado'] }}</span>
                        </div>
                    @endforeach
                </div>
                @if (collect($desgloseSemanas)->contains('incluye_otro_mes', true))
                    <p class="text-xs text-muted-foreground mt-2">* Semana a caballo entre dos meses; se muestra el total completo de la semana.</p>
                @endif
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarDesgloseSemanas', false)">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>
</div>