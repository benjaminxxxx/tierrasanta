<div class="space-y-4">
    <x-flex class="justify-between">
        <div>
            <x-title>Planilla Oficina</x-title>
            <x-subtitle>Régimen general: personal con contrato de oficina o general, en planilla o por recibo por honorarios</x-subtitle>
        </div>
        @include('comun.selector-mes-base')
    </x-flex>

    @php
        $m = fn($v) => $v === null ? '—' : number_format((float) $v, 2);
        $puedeGestionar = auth()->user()->can(\App\Constants\Permisos::PLANILLA_OFICINA_GESTIONAR);
        // Celda de un concepto: amarilla si tiene ajuste manual; clic para ajustar
        $celda = function ($f, string $col, string $clase = '') use ($m, $puedeGestionar) {
            $aj = $f->ajuste($col);
            $titulo = $aj ? 'Monto puesto a mano' . ($aj['motivo'] ? " ({$aj['motivo']})" : '') . '. Calculado: S/ ' . $m($f->calculado($col)) : 'Calculado por el sistema. Clic para ajustar.';
            $click = $puedeGestionar ? "wire:click=\"abrirAjuste({$f->id}, '{$col}')\"" : '';
            return '<td class="px-2 py-1 text-right whitespace-nowrap ' . ($puedeGestionar ? 'cursor-pointer hover:bg-muted ' : '') . ($aj ? 'bg-amber-100 dark:bg-amber-900/30 ' : '') . $clase . '" title="' . e($titulo) . '" ' . $click . '>'
                . $m($f->{$col}) . ($aj ? ' <i class="fa fa-pen text-[9px] text-amber-700"></i>' : '') . '</td>';
        };
        $grupos = [
            'Recibo por honorarios' => $filas->where('tipo_ingreso', 'honorarios'),
            'En planilla' => $filas->where('tipo_ingreso', 'planilla'),
        ];
    @endphp

    @if ($filas->isEmpty())
        <x-card>
            <p class="text-sm text-muted-foreground">
                No se ha generado la planilla oficina de este mes. Entran todos los que tienen contrato de tipo "oficina" o "general" vigente
                (en planilla o por recibo por honorarios), con sus suspensiones (vacaciones, faltas) y las tasas del mes. Si alguien no está, regístralo
                en Empleados y créale su contrato.
            </p>
        </x-card>
    @else
        {{-- ============================================================ COSTO ADMINISTRATIVO --}}
        <x-card class="space-y-2">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h3 class="font-semibold text-foreground">Costo administrativo del mes</h3>
                    <p class="text-xs text-muted-foreground">
                        Como la hoja ADM: blanco = neto (planilla u honorarios) + CTS y gratificación del mes; negro = bonificación pagada por fuera.
                        Se compara con lo que se puso a mano en Costos mensuales.
                    </p>
                </div>
                @if ($urlExcel)
                    <x-button href="{{ $urlExcel }}"><i class="fa fa-file-excel"></i> Descargar Excel (ADM, EMPLEADOS, BENEFICIOS)</x-button>
                @endif
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach (['blanco' => 'Blanco', 'negro' => 'Negro'] as $clave => $texto)
                    @php
                        $manual = $costo['manual_' . $clave];
                        $dif = $manual === null ? null : round($costo[$clave] - $manual, 2);
                    @endphp
                    <div class="rounded-lg border border-border p-3">
                        <p class="text-xs text-muted-foreground">{{ $texto }}</p>
                        <p class="text-xl font-semibold">S/ {{ $m($costo[$clave]) }}</p>
                        <p class="text-xs mt-1">
                            Costos mensuales (a mano): {{ $manual === null ? 'sin registrar' : 'S/ ' . $m($manual) }}
                            @if ($dif !== null)
                                · <span class="{{ abs($dif) < 0.01 ? 'text-green-700 dark:text-green-400' : 'text-red-600' }}">
                                    {{ abs($dif) < 0.01 ? 'coincide' : 'diferencia ' . sprintf('%+.2f', $dif) }}
                                </span>
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs">
                @foreach ($costo['grupos'] as $grupo => $g)
                    <span><b>{{ $grupo }}:</b> blanco S/ {{ $m($g['blanco']) }} · negro S/ {{ $m($g['negro']) }} · total S/ {{ $m($g['blanco'] + $g['negro']) }}</span>
                @endforeach
            </div>
            <p class="text-xs text-muted-foreground">
                Neto S/ {{ $m($costo['neto']) }} · CTS y gratificación del mes S/ {{ $m($costo['provisiones']) }} · pagado en blanco este mes S/ {{ $m($costo['pagado_blanco']) }}
                · costo contable S/ {{ $m($costo['contable']) }} · costo B+N S/ {{ $m($costo['total']) }}
            </p>
        </x-card>

        {{-- ============================================================ PLANILLA --}}
        <x-card class="space-y-2">
            <h3 class="font-semibold text-foreground">Planilla del mes ({{ $filas->count() }})</h3>
            <p class="text-xs text-muted-foreground">
                Clic en un monto para ajustarlo a mano (5ta categoría, sueldo real, vacaciones calculadas aparte…) o en el nombre para el N° de recibo
                por honorarios. Lo ajustado queda en amarillo, se conserva al regenerar y lo que depende de él se recalcula. Beneficios en dos tramos:
                se retienen cada mes y se pagan en julio/diciembre (gratificación) y mayo/noviembre (CTS); cada mes: se pagan con el sueldo.
            </p>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border border-border">
                    <thead class="bg-muted text-muted-foreground">
                        <tr>
                            <th class="px-2 py-1 text-left">Trabajador</th>
                            <th class="px-2 py-1" title="Forma de pago de CTS y gratificación">Benef.</th>
                            <th class="px-2 py-1" title="Laborados / vacaciones / faltas y otras sin goce">Días</th>
                            <th class="px-2 py-1 text-right">Sueldo</th>
                            <th class="px-2 py-1 text-right">Vacac.</th>
                            <th class="px-2 py-1 text-right">Asig. fam.</th>
                            <th class="px-2 py-1 text-right font-semibold">Total rem.</th>
                            <th class="px-2 py-1 text-right">AFP fondo</th>
                            <th class="px-2 py-1 text-right">Comisión</th>
                            <th class="px-2 py-1 text-right">Prima</th>
                            <th class="px-2 py-1 text-right">SNP</th>
                            <th class="px-2 py-1 text-right">5ta</th>
                            <th class="px-2 py-1 text-right">4ta</th>
                            <th class="px-2 py-1 text-right font-semibold">Neto</th>
                            <th class="px-2 py-1 text-right">EsSalud</th>
                            <th class="px-2 py-1 text-right">Vida ley</th>
                            <th class="px-2 py-1 text-right">CTS mes</th>
                            <th class="px-2 py-1 text-right">Grat. mes</th>
                            <th class="px-2 py-1 text-right">Gratif. pag.</th>
                            <th class="px-2 py-1 text-right">Bonif. pag.</th>
                            <th class="px-2 py-1 text-right">CTS pag.</th>
                            <th class="px-2 py-1 text-right">Sueldo real</th>
                            <th class="px-2 py-1 text-right">Negro</th>
                            <th class="px-2 py-1 text-right">Pagado blanco</th>
                            <th class="px-2 py-1 text-right">Costo contable</th>
                            <th class="px-2 py-1 text-right font-semibold">Costo B+N</th>
                        </tr>
                    </thead>
                    @foreach ($grupos as $nombreGrupo => $grupo)
                        @continue($grupo->isEmpty())
                        <tbody>
                            <tr class="bg-muted/60"><td colspan="26" class="px-2 py-1 font-semibold">{{ $nombreGrupo }} ({{ $grupo->count() }})</td></tr>
                            @foreach ($grupo as $f)
                                <tr class="border-t border-border" wire:key="of-{{ $f->id }}">
                                    <td class="px-2 py-1 whitespace-nowrap {{ $puedeGestionar ? 'cursor-pointer hover:bg-muted' : '' }}"
                                        @if ($puedeGestionar) wire:click="abrirAjuste({{ $f->id }})" @endif
                                        title="{{ $f->cuenta_principal ? 'Blanco: ' . $f->cuenta_principal : 'Sin cuenta principal' }}{{ $f->cuenta_secundaria ? ' · Diferencia: ' . $f->cuenta_secundaria : '' }}">
                                        {{ $f->nombres }}
                                        <span class="block text-muted-foreground">
                                            {{ $f->esHonorarios() ? 'RxH ' . ($f->comprobante ?? 's/n') : ($f->sistema_pension . ($f->es_pensionista ? ' · pensionista' : '')) }}
                                            @if (!$f->cuenta_principal) · <span class="text-amber-700">sin cuenta</span> @endif
                                        </span>
                                    </td>
                                    <td class="px-2 py-1 text-center whitespace-nowrap">{{ $f->esHonorarios() ? '—' : ($f->beneficios_mensuales ? 'Mensual' : '2 tramos') }}</td>
                                    <td class="px-2 py-1 text-center whitespace-nowrap">{{ $f->dias_laborados }}/{{ $f->dias_vacaciones }}/{{ $f->dias_suspension_perfecta }}</td>
                                    {!! $celda($f, 'rem_sueldo') !!}
                                    {!! $celda($f, 'rem_vacaciones') !!}
                                    {!! $celda($f, 'rem_asignacion_familiar') !!}
                                    <td class="px-2 py-1 text-right font-semibold">{{ $m($f->total_remuneracion) }}</td>
                                    {!! $celda($f, 'desc_afp_fondo') !!}
                                    {!! $celda($f, 'desc_afp_comision') !!}
                                    {!! $celda($f, 'desc_afp_prima') !!}
                                    {!! $celda($f, 'desc_snp') !!}
                                    {!! $celda($f, 'desc_renta_quinta') !!}
                                    {!! $celda($f, 'desc_renta_cuarta') !!}
                                    <td class="px-2 py-1 text-right font-semibold">{{ $m($f->neto_planilla) }}</td>
                                    {!! $celda($f, 'aporte_essalud') !!}
                                    {!! $celda($f, 'aporte_vida_ley') !!}
                                    {!! $celda($f, 'provision_cts') !!}
                                    {!! $celda($f, 'provision_gratificacion') !!}
                                    {!! $celda($f, 'gratificacion') !!}
                                    {!! $celda($f, 'bonif_extraordinaria') !!}
                                    {!! $celda($f, 'cts') !!}
                                    {!! $celda($f, 'sueldo_real') !!}
                                    <td class="px-2 py-1 text-right">{{ $m($f->bonificacion_negro) }}</td>
                                    <td class="px-2 py-1 text-right">{{ $m($f->pago_blanco_mes) }}</td>
                                    <td class="px-2 py-1 text-right">{{ $m($f->costo_contable) }}</td>
                                    <td class="px-2 py-1 text-right font-semibold">{{ $m($f->costo_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                    <tfoot class="bg-muted font-semibold">
                        <tr>
                            <td class="px-2 py-1" colspan="3">Total</td>
                            @foreach (['rem_sueldo', 'rem_vacaciones', 'rem_asignacion_familiar', 'total_remuneracion', 'desc_afp_fondo', 'desc_afp_comision', 'desc_afp_prima', 'desc_snp', 'desc_renta_quinta', 'desc_renta_cuarta', 'neto_planilla', 'aporte_essalud', 'aporte_vida_ley', 'provision_cts', 'provision_gratificacion', 'gratificacion', 'bonif_extraordinaria', 'cts', 'sueldo_real', 'bonificacion_negro', 'pago_blanco_mes', 'costo_contable', 'costo_total'] as $col)
                                <td class="px-2 py-1 text-right">{{ $m($filas->sum(fn($f) => (float) $f->{$col})) }}</td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-card>
    @endif

    {{-- ============================================================ MODAL AJUSTE --}}
    <x-dialog-modal wire:model.live="modalAjuste">
        <x-slot name="title">{{ $filaAjuste?->nombres ?? 'Ajuste manual' }}</x-slot>
        <x-slot name="content">
            <div class="space-y-3">
                @if ($filaAjuste)
                    <p class="text-xs text-muted-foreground">
                        {{ $filaAjuste->esHonorarios() ? 'Recibo por honorarios' : 'En planilla' }} ·
                        Blanco: {{ $filaAjuste->cuenta_principal ?? 'sin cuenta' }}{{ $filaAjuste->cuenta_secundaria ? ' · diferencia: ' . $filaAjuste->cuenta_secundaria : '' }}.
                        Las cuentas y la forma de pago se cambian en el contrato del empleado.
                    </p>
                    <x-input wire:model="ajusteComprobante" label="{{ $filaAjuste->esHonorarios() ? 'N° de recibo por honorarios del mes' : 'Comprobante / observación del mes' }}" placeholder="Ej: E001-125" />
                @endif
                <x-select wire:model.live="ajusteColumna" label="Concepto a ajustar (opcional)" error="ajusteColumna">
                    <option value="">—</option>
                    @foreach ($ajustables as $col => $texto)
                        <option value="{{ $col }}">{{ $texto }}</option>
                    @endforeach
                </x-select>
                @if ($ajusteColumna)
                    @if ($filaAjuste)
                        <p class="text-xs text-muted-foreground">Calculado por el sistema: S/ {{ $m($filaAjuste->calculado($ajusteColumna)) }}</p>
                    @endif
                    <x-input type="number" step="0.01" min="0" wire:model="ajusteMonto" label="Monto" error="ajusteMonto" />
                    <x-input wire:model="ajusteMotivo" label="Motivo" placeholder="Ej: 5ta categoría del mes, vacaciones calculadas aparte…" />
                @endif
            </div>
        </x-slot>
        <x-slot name="footer">
            @if ($filaAjuste && $ajusteColumna && $filaAjuste->ajuste($ajusteColumna))
                <x-button variant="danger" wire:click="guardarAjuste(true)">Quitar ajuste</x-button>
            @endif
            <x-button variant="secondary" wire:click="$set('modalAjuste', false)">Cancelar</x-button>
            <x-button wire:click="guardarAjuste"><i class="fa fa-save"></i> Guardar</x-button>
        </x-slot>
    </x-dialog-modal>

    @if ($puedeGestionar)
        <x-inferior-derecha>
            <x-button wire:click="generar" wire:confirm="¿Generar la planilla oficina de este mes? Los ajustes se conservan.">
                <i class="fa fa-refresh"></i> Generar planilla oficina
            </x-button>
        </x-inferior-derecha>
    @endif
    <x-loading wire:loading wire:target="generar,guardarAjuste,mes,anio" />
</div>
