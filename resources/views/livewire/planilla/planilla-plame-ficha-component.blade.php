<div>
    <x-dialog-modal wire:model="mostrar" maxWidth="full">
        <x-slot name="title">
            PLAME · {{ $ficha['trabajador']['nombres'] ?? '' }}
        </x-slot>

        <x-slot name="content">
            @if ($ficha)
                @php
                    $t = $ficha['trabajador'];
                    $d = $ficha['dias'];
                    $tot = $ficha['totales'];
                    $c = $ficha['costos'];
                    $v = $ficha['vacaciones'];
                    $soles = fn($m) => $m === null ? '—' : number_format((float) $m, 2);
                @endphp

                <div class="space-y-5 text-sm">
                    <p class="text-muted-foreground">
                        Datos del PLAME de <b class="capitalize">{{ $ficha['periodo'] }}</b> ({{ $ficha['periodo_corto'] }}) tal como están
                        en el sistema, para compararlos con la boleta R08 del PLAME real.
                    </p>

                    {{-- ============================ Trabajador --}}
                    <div class="overflow-x-auto">
                        <table class="w-full border border-border text-center">
                            <thead class="bg-muted text-xs uppercase">
                                <tr>
                                    <th class="p-2 border border-border">DNI</th>
                                    <th class="p-2 border border-border">Nombres y apellidos</th>
                                    <th class="p-2 border border-border">Fecha de ingreso</th>
                                    <th class="p-2 border border-border">Tipo de trabajador</th>
                                    <th class="p-2 border border-border">Régimen pensionario</th>
                                    <th class="p-2 border border-border">Grupo</th>
                                    <th class="p-2 border border-border">Edad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="p-2 border border-border font-semibold">{{ $t['documento'] ?? '—' }}</td>
                                    <td class="p-2 border border-border font-semibold">{{ $t['nombres'] }}</td>
                                    <td class="p-2 border border-border">{{ formatear_fecha($t['fecha_ingreso']) ?? '—' }}</td>
                                    <td class="p-2 border border-border capitalize">
                                        {{ $t['tipo_planilla'] ?? '—' }}{{ $t['tipo_contrato'] ? ' · ' . $t['tipo_contrato'] : '' }}
                                    </td>
                                    <td class="p-2 border border-border">
                                        {{ $t['regimen'] ?? '—' }}
                                        @if ($t['pensionista']) <x-badge color="yellow">Pensionista</x-badge> @endif
                                    </td>
                                    <td class="p-2 border border-border">{{ $t['grupo'] ?? '—' }}</td>
                                    <td class="p-2 border border-border">{{ $t['edad'] ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="text-xs text-muted-foreground mt-1">El CUSPP no se registra en el sistema.</p>
                    </div>

                    {{-- ============================ Días --}}
                    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                        @foreach ([
                            ['Días laborados', $d['laborados']],
                            ['Días no laborados', $d['no_laborados']],
                            ['Días subsidiados', $d['subsidiados']],
                            ['Horas jornada ordinaria', rtrim(rtrim(number_format($d['total_horas'], 2), '0'), '.')],
                            // Con estas se calculan el jornal básico y lo pagado (incluyen descanso médico y licencias con goce)
                            ['Horas registradas (pagadas)', rtrim(rtrim(number_format($d['horas_registradas'], 2), '0'), '.')],
                            ['Horas meta del mes', rtrim(rtrim(number_format($d['horas_meta_mes'], 2), '0'), '.')],
                        ] as [$etiqueta, $valor])
                            <div class="rounded-lg border p-3">
                                <p class="text-xs text-muted-foreground">{{ $etiqueta }}</p>
                                <p class="text-2xl font-bold">{{ $valor }}</p>
                            </div>
                        @endforeach
                    </div>

                    {{-- ============================ Suspensiones --}}
                    <div>
                        <p class="font-semibold mb-1">Motivos de suspensión de labores</p>
                        @if ($ficha['suspensiones'])
                            <table class="w-full border border-border">
                                <thead class="bg-muted text-xs uppercase">
                                    <tr>
                                        <th class="p-2 border border-border w-20">Tipo</th>
                                        <th class="p-2 border border-border text-left">Motivo</th>
                                        <th class="p-2 border border-border w-24">N.º días</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ficha['suspensiones'] as $s)
                                        <tr>
                                            <td class="p-2 border border-border text-center">{{ $s['codigo'] }}</td>
                                            <td class="p-2 border border-border">{{ $s['motivo'] }}</td>
                                            <td class="p-2 border border-border text-center">{{ $s['dias'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-muted-foreground">Sin suspensiones en el mes.</p>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        {{-- ============================ Conceptos (como la boleta) --}}
                        <div>
                            <table class="w-full border border-border">
                                <thead class="bg-muted text-xs uppercase">
                                    <tr>
                                        <th class="p-2 border border-border w-16">Código</th>
                                        <th class="p-2 border border-border text-left">Concepto</th>
                                        <th class="p-2 border border-border text-right w-28">Ingresos S/</th>
                                        <th class="p-2 border border-border text-right w-28">Descuentos S/</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="bg-muted/50"><td colspan="4" class="p-1.5 px-2 font-semibold">Ingresos</td></tr>
                                    @forelse ($ficha['ingresos'] as $i)
                                        <tr>
                                            <td class="p-1.5 px-2 border border-border">{{ $i['codigo'] }}</td>
                                            <td class="p-1.5 px-2 border border-border">{{ $i['concepto'] }}</td>
                                            <td class="p-1.5 px-2 border border-border text-right">{{ $soles($i['monto']) }}</td>
                                            <td class="p-1.5 px-2 border border-border"></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="p-2 text-muted-foreground">Sin ingresos.</td></tr>
                                    @endforelse
                                    <tr class="bg-muted/50"><td colspan="4" class="p-1.5 px-2 font-semibold">Descuentos · aportes del trabajador</td></tr>
                                    @forelse ($ficha['descuentos'] as $i)
                                        <tr>
                                            <td class="p-1.5 px-2 border border-border">{{ $i['codigo'] }}</td>
                                            <td class="p-1.5 px-2 border border-border">{{ $i['concepto'] }}</td>
                                            <td class="p-1.5 px-2 border border-border"></td>
                                            <td class="p-1.5 px-2 border border-border text-right">{{ $soles($i['monto']) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="p-2 text-muted-foreground">Sin descuentos.</td></tr>
                                    @endforelse
                                    <tr class="font-semibold">
                                        <td colspan="2" class="p-2 border border-border text-right">Totales</td>
                                        <td class="p-2 border border-border text-right">{{ $soles($tot['ingresos']) }}</td>
                                        <td class="p-2 border border-border text-right">{{ $soles($tot['descuentos']) }}</td>
                                    </tr>
                                    <tr class="font-bold text-base bg-green-50 dark:bg-green-950/40">
                                        <td colspan="3" class="p-2 border border-border text-right">Neto a pagar</td>
                                        <td class="p-2 border border-border text-right">{{ $soles($tot['neto']) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <p class="text-xs text-muted-foreground mt-1">Remuneración bruta (0117 + 0118 + 0121 + 0201): S/ {{ $soles($tot['remuneracion_bruta']) }}</p>
                        </div>

                        <div class="space-y-5">
                            {{-- ============================ Aportes del empleador --}}
                            <table class="w-full border border-border">
                                <thead class="bg-muted text-xs uppercase">
                                    <tr>
                                        <th class="p-2 border border-border w-16">Código</th>
                                        <th class="p-2 border border-border text-left">Aportes del empleador</th>
                                        <th class="p-2 border border-border text-right w-28">S/</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($ficha['aportes'] as $i)
                                        <tr>
                                            <td class="p-1.5 px-2 border border-border">{{ $i['codigo'] }}</td>
                                            <td class="p-1.5 px-2 border border-border">{{ $i['concepto'] }}</td>
                                            <td class="p-1.5 px-2 border border-border text-right">{{ $soles($i['monto']) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="p-2 text-muted-foreground">Sin aportes.</td></tr>
                                    @endforelse
                                    <tr class="font-semibold">
                                        <td colspan="2" class="p-2 border border-border text-right">Total aportes del empleador</td>
                                        <td class="p-2 border border-border text-right">{{ $soles($tot['aportes_empleador']) }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            {{-- ============================ Vacaciones --}}
                            <div class="rounded-lg border p-3 space-y-1">
                                <p class="font-semibold">Vacaciones</p>
                                <p>Días de descanso vacacional (S.I. 23): <b>{{ $v['dias'] }}</b></p>
                                <p>0118 Remuneración vacacional: <b>S/ {{ $soles($v['remuneracion']) }}</b>
                                    · 0117 Compensación vacacional: <b>S/ {{ $soles($v['compensacion']) }}</b></p>
                                @if ($v['plame_personalizado'] !== null || $v['neto_pagadas'] !== null || $v['negro'] !== null)
                                    <p class="text-muted-foreground">
                                        Ajustes: PLAME personalizado S/ {{ $soles($v['plame_personalizado']) }} ·
                                        neto pagadas S/ {{ $soles($v['neto_pagadas']) }} · negro S/ {{ $soles($v['negro']) }}
                                    </p>
                                @endif
                            </div>

                            {{-- ============================ Costos --}}
                            <div class="rounded-lg border p-3">
                                <p class="font-semibold mb-2">Costos del mes</p>
                                <dl class="grid grid-cols-2 gap-x-4 gap-y-1">
                                    <dt class="text-muted-foreground">Costo formal PLAME (ingresos + aportes empleador)</dt>
                                    <dd class="text-right font-semibold">S/ {{ $soles($tot['costo_formal']) }}</dd>
                                    <dt class="text-muted-foreground">Sueldo acordado (neto mensual)</dt>
                                    <dd class="text-right">S/ {{ $soles($c['sueldo_acordado']) }}</dd>
                                    <dt class="text-muted-foreground">Sueldo pagado (proporcional a horas)</dt>
                                    <dd class="text-right">S/ {{ $soles($c['sueldo_pagado']) }}</dd>
                                    <dt class="text-muted-foreground">Costo real del mes</dt>
                                    <dd class="text-right font-semibold">S/ {{ $soles($c['costo_real']) }}</dd>
                                    <dt class="text-muted-foreground">Costo por hora (usado en costos)</dt>
                                    <dd class="text-right">S/ {{ $c['costo_hora'] === null ? '—' : number_format($c['costo_hora'], 4) }}</dd>
                                    <dt class="text-muted-foreground">Bono de productividad</dt>
                                    <dd class="text-right">S/ {{ $soles($c['bono_productividad']) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    {{-- ============================ Verificaciones --}}
                    <div class="rounded-lg border p-3">
                        <p class="font-semibold mb-2">Verificaciones</p>
                        <ul class="space-y-1">
                            @foreach ($ficha['verificaciones'] as $ver)
                                <li class="flex gap-2 {{ $ver['ok'] ? '' : 'text-amber-700 dark:text-amber-400' }}">
                                    <i class="fa {{ $ver['ok'] ? 'fa-check text-green-600' : 'fa-exclamation-triangle' }} mt-0.5"></i>
                                    <span>{{ $ver['texto'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrar', false)">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>
</div>
