<div>
    <x-dialog-modal wire:model="mostrarModal" maxWidth="2xl">
        <x-slot name="title">
            <div class="flex items-center justify-between gap-3 pr-6">
                <span class="text-lg font-bold">Consolidar costos del mes</span>
                <x-input type="number" wire:model.live="anio" class="w-24 text-center" min="2000" max="2100" />
            </div>
        </x-slot>

        <x-slot name="content">
            @php
                $m = fn($v) => $v === null ? '—' : number_format((float) $v, 2);
                $nombres = [1 => 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Dic'];
                $colorEstado = [
                    'ok' => 'bg-green-500', 'diferencia' => 'bg-red-500', 'sin' => 'bg-zinc-300 dark:bg-zinc-600',
                ];
            @endphp
            <div class="space-y-4">
                {{-- Tira de meses: el color dice si cuadra --}}
                <div class="grid grid-cols-6 sm:grid-cols-12 gap-1">
                    @foreach ($nombres as $num => $nombre)
                        <button type="button" wire:click="elegirMes({{ $num }})"
                            class="relative px-1 py-1.5 rounded text-xs font-semibold border transition
                                {{ $mes === $num ? 'border-primary bg-primary text-primary-foreground' : 'border-border hover:bg-muted' }}"
                            title="{{ ['ok' => 'Cuadra', 'diferencia' => 'Tiene diferencias', 'sin' => 'Sin consolidar'][$estados[$num] ?? 'sin'] }}">
                            {{ $nombre }}
                            <span class="absolute top-0.5 right-0.5 w-1.5 h-1.5 rounded-full {{ $colorEstado[$estados[$num] ?? 'sin'] }}"></span>
                        </button>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="text-sm">
                        <b>{{ \Illuminate\Support\Carbon::create($anio ?: date('Y'), $mes ?: 1, 1)->translatedFormat('F Y') }}</b>
                        <span class="text-muted-foreground">
                            · {{ $costo ? 'consolidado ' . $costo->updated_at?->format('d/m/Y H:i') : 'sin consolidar' }}
                        </span>
                    </div>
                    <div class="flex gap-2">
                        @if ($urlExcel)
                            <x-button variant="secondary" href="{{ $urlExcel }}" target="_blank"><i class="fa fa-file-excel"></i> Excel</x-button>
                        @endif
                        <x-button wire:click="consolidar" title="Lee la BDD (ya al día), calcula los totales y genera el Excel">
                            <i class="fa fa-check"></i> Consolidar mes
                        </x-button>
                    </div>
                </div>

                @if ($desactualizados > 0)
                    <x-warning>
                        {{ $desactualizados }} día(s) con cambios en registros diarios, planilla o campañas después de la última actualización de la
                        mano de obra. Reconstruye la mano de obra para que entren.
                    </x-warning>
                @endif

                {{-- Conceptos por grupo: pagado (fuente) vs calculado (BDD) y su botón de reconstruir --}}
                <div class="border border-border rounded-lg divide-y divide-border">
                    @foreach ($grupos as $clave => $g)
                        <div class="p-3 space-y-1">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-sm">{{ $g['nombre'] }}</p>
                                    @if ($g['fuente'])
                                        <p class="text-[11px] text-muted-foreground">Fuente: {{ $g['fuente'] }}</p>
                                    @endif
                                </div>
                                @if ($g['fuente'])
                                    <x-button size="sm" variant="outline" wire:click="reconstruir('{{ $clave }}')"
                                        wire:confirm="¿Reconstruir {{ mb_strtolower($g['nombre']) }} del mes desde {{ $g['fuente'] }}?"
                                        title="Vuelve a leer las fuentes de este grupo y consolida el mes">
                                        <i class="fa fa-wrench"></i> Reconstruir
                                    </x-button>
                                @endif
                            </div>
                            <table class="w-full text-xs">
                                <thead class="text-muted-foreground">
                                    <tr>
                                        <th class="text-left font-normal py-0.5">Concepto</th>
                                        <th class="text-right font-normal">Pagado / fuente</th>
                                        <th class="text-right font-normal">En la BDD</th>
                                        <th class="text-right font-normal">Diferencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($g['conceptos'] as $campo => $nombre)
                                        @php
                                            $pagado = $costo?->{$campo};
                                            $calculado = $costo?->{$campo . '_calculado'};
                                            $dif = $pagado !== null && $calculado !== null ? (float) $pagado - (float) $calculado : null;
                                        @endphp
                                        <tr>
                                            <td class="py-0.5">{{ $nombre }}</td>
                                            <td class="text-right font-mono">{{ $m($pagado) }}</td>
                                            <td class="text-right font-mono">{{ $m($calculado) }}</td>
                                            <td class="text-right font-mono {{ $dif === null ? 'text-muted-foreground' : (abs($dif) < 0.01 ? 'text-green-600 dark:text-green-400' : 'text-red-600 font-semibold') }}">
                                                {{ $dif === null ? '—' : (abs($dif) < 0.01 ? 'cuadra' : $m($dif)) }}
                                            </td>
                                        </tr>
                                        @if ($campo === 'costo_planilla' && $costo?->costo_mano_obra_indirecta !== null)
                                            <tr class="text-muted-foreground">
                                                <td class="pl-3">↳ pagos sin horas (vacaciones pagadas, bono asistencia)</td>
                                                <td></td>
                                                <td class="text-right font-mono">{{ $m($costo->costo_mano_obra_indirecta) }}</td>
                                                <td></td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
                <p class="text-[11px] text-muted-foreground">
                    Si la planilla no cuadra, el Excel (hoja CUADRE PLANILLA) dice qué trabajador, por qué y cómo corregirlo.
                </p>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button wire:click="cerrarModal" variant="secondary">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading wire:target="consolidar,reconstruir" />
</div>
