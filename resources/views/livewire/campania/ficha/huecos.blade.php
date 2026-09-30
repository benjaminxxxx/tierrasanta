{{-- Aviso: días con actividades que quedarían sin campaña. $huecos viene de CampaniaFichaComponent --}}
@if ($huecos)
    <x-warning class="mt-4">
        <div class="space-y-3 text-sm">
            <p class="font-semibold">Hay días con actividades que no pertenecerían a ninguna campaña.</p>
            <p>Toda labor registrada en el campo debe caer dentro de una campaña (sus costos se cargan por fecha).</p>

            @foreach ($huecos as $lado => $hueco)
                <div class="rounded border border-yellow-400/60 p-2">
                    <p>
                        @if ($lado === 'antes')
                            Entre el cierre de <b>{{ $hueco['vecina'] }}</b> y el inicio de esta campaña
                        @else
                            Entre el cierre de esta campaña y el inicio de <b>{{ $hueco['vecina'] }}</b>
                        @endif
                        ({{ formatear_fecha($hueco['desde']) }} – {{ formatear_fecha($hueco['hasta']) }}):
                    </p>
                    <ul class="list-disc list-inside mt-1">
                        @foreach (array_slice($hueco['actividades'], 0, 8, true) as $dia => $labores)
                            <li>{{ formatear_fecha($dia) }}: {{ implode(', ', $labores) }}</li>
                        @endforeach
                        @if (count($hueco['actividades']) > 8)
                            <li>… y {{ count($hueco['actividades']) - 8 }} día(s) más</li>
                        @endif
                    </ul>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @if ($lado === 'antes')
                            <x-button size="xs" variant="warning" wire:click="usarFechaInicio('{{ $hueco['primer_dia'] }}')">
                                Empezar esta campaña el {{ formatear_fecha($hueco['primer_dia']) }}
                            </x-button>
                            <span class="text-xs self-center">o amplía el cierre de {{ $hueco['vecina'] }} si esas labores eran de su cosecha.</span>
                        @else
                            <x-button size="xs" variant="warning" wire:click="usarFechaFin('{{ $hueco['ultimo_dia'] }}')">
                                Cerrar esta campaña el {{ formatear_fecha($hueco['ultimo_dia']) }}
                            </x-button>
                            <span class="text-xs self-center">o adelanta el inicio de {{ $hueco['vecina'] }}.</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </x-warning>
@endif
