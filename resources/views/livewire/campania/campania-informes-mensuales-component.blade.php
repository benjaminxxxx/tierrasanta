<div class="space-y-4">
    <div>
        <x-title>Informes mensuales de campaña</x-title>
        <x-subtitle>
            Al cierre de cada mes, cada campaña activa tiene su informe acumulado desde el inicio hasta el fin de mes (o hasta su fecha de fin si
            cierra ese mes). El informe queda guardado: si después se corrige un dato, usa "Recalcular desde aquí" para rehacer ese mes y los siguientes.
        </x-subtitle>
    </div>

    @php $puedeGestionar = auth()->user()->can(\App\Constants\Permisos::CAMPAÑA_INFORME_MENSUAL_GESTIONAR); @endphp

    <x-card class="space-y-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                <button type="button" wire:click="$set('vista', 'mes')"
                    class="px-3 py-2 text-sm font-semibold {{ $vista === 'mes' ? 'bg-primary text-white' : '' }}">Por mes</button>
                <button type="button" wire:click="$set('vista', 'campania')"
                    class="px-3 py-2 text-sm font-semibold {{ $vista === 'campania' ? 'bg-primary text-white' : '' }}">Por campaña</button>
            </div>

            @if ($vista === 'mes')
                <x-select-meses wire:model.live="mes" label="Mes" class="w-auto" />
                <x-select-anios wire:model.live="anio" label="Año" class="w-auto" />
                @if ($puedeGestionar && $estadoMes && $estadoMes['resumen']['generados'] < $estadoMes['resumen']['total'])
                    <x-button wire:click="generarPendientesDelMes"
                        wire:confirm="¿Generar los {{ $estadoMes['resumen']['total'] - $estadoMes['resumen']['generados'] }} informe(s) que faltan de este mes?">
                        <i class="fa fa-cogs"></i> Generar los que faltan ({{ $estadoMes['resumen']['total'] - $estadoMes['resumen']['generados'] }})
                    </x-button>
                @endif
            @else
                <x-select-campo wire:model.live="campo" label="Campo" />
                @if ($campo)
                    <x-select wire:model.live="campaniaId" label="Campaña" class="w-auto">
                        @foreach ($campaniasDelCampo as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre_campania }} ({{ formatear_fecha($c->fecha_inicio) }} – {{ $c->fecha_fin ? formatear_fecha($c->fecha_fin) : 'en curso' }})</option>
                        @endforeach
                    </x-select>
                @endif
            @endif
        </div>

        {{-- ============================================================ POR MES --}}
        @if ($vista === 'mes' && $estadoMes)
            <div class="flex flex-wrap gap-2 text-sm">
                <x-badge color="blue">{{ $estadoMes['resumen']['total'] }} campañas activas</x-badge>
                <x-badge color="green">{{ $estadoMes['resumen']['generados'] }} generados</x-badge>
                @if ($estadoMes['resumen']['sin_anterior'])
                    <x-badge color="yellow">{{ $estadoMes['resumen']['sin_anterior'] }} sin el informe del mes anterior</x-badge>
                @endif
            </div>

            @if ($estadoMes['reemplazadas'])
                <div class="rounded-lg border border-amber-300 bg-amber-50 dark:bg-amber-900/20 p-3 text-sm space-y-1">
                    <p class="font-semibold text-amber-900 dark:text-amber-200"><i class="fa fa-exclamation-triangle"></i> Campañas sin fecha de fin (no se generan):</p>
                    @foreach ($estadoMes['reemplazadas'] as $r)
                        <p class="text-amber-900 dark:text-amber-200">• Campo {{ $r['campania']->campo }} {{ $r['campania']->nombre_campania }}: {{ $r['motivo'] }}</p>
                    @endforeach
                </div>
            @endif

            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th value="Campo" />
                        <x-th value="Campaña" />
                        <x-th value="Inicio" />
                        <x-th value="Corte" />
                        <x-th value="Informe" />
                        <x-th value="Costo US$/ha" />
                        <x-th value="Advertencias" />
                        <x-th value="Acciones" />
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($estadoMes['filas'] as $f)
                        @php $c = $f['campania']; $i = $f['informe']; @endphp
                        <x-tr wire:key="mes-{{ $c->id }}">
                            <x-td class="font-bold text-center">{{ $c->campo }}</x-td>
                            <x-td>
                                <button type="button" class="text-primary hover:underline" wire:click="verCampania({{ $c->id }})">{{ $c->nombre_campania }}</button>
                                @if ($f['es_cierre'])
                                    <x-badge color="indigo">Cierre</x-badge>
                                @endif
                                @if ($f['es_nueva'])
                                    <x-badge color="green">Nueva</x-badge>
                                @endif
                            </x-td>
                            <x-td class="text-center">{{ formatear_fecha($c->fecha_inicio) }}</x-td>
                            <x-td class="text-center">{{ formatear_fecha($f['fecha_corte']) }}</x-td>
                            <x-td class="text-center">
                                @if ($i)
                                    <span class="text-green-700 dark:text-green-400 font-semibold">v{{ $i->version }}</span>
                                    <div class="text-xs text-muted-foreground">{{ $i->updated_at->format('d/m/Y H:i') }}</div>
                                @else
                                    <span class="text-muted-foreground">Sin generar</span>
                                @endif
                                @if ($f['falta_anterior'])
                                    <div class="text-xs text-amber-700 dark:text-amber-400" title="La campaña ya existía el mes anterior y su informe no se generó">
                                        <i class="fa fa-exclamation-triangle"></i> Falta el mes anterior
                                    </div>
                                @endif
                            </x-td>
                            <x-td class="text-right">{{ $i && $i->total_usd_ha !== null ? number_format($i->total_usd_ha, 2) : '' }}</x-td>
                            <x-td class="text-center">
                                @if ($i)
                                    @php $avisos = $i->advertenciasVigentes(); $errores = collect($avisos)->where('nivel', 'error')->count(); @endphp
                                    @if ($errores)
                                        <x-badge color="red">{{ $errores }} faltan</x-badge>
                                    @endif
                                    @if (count($avisos) - $errores)
                                        <x-badge color="yellow">{{ count($avisos) - $errores }} revisar</x-badge>
                                    @endif
                                @endif
                            </x-td>
                            <x-td>
                                @include('livewire.campania.partials.informe-mensual-acciones', ['campaniaId' => $c->id, 'anio' => $anio, 'mes' => $mes, 'informe' => $i])
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
        @endif

        {{-- ============================================================ POR CAMPAÑA --}}
        @if ($vista === 'campania')
            @if (!$campania)
                <p class="text-muted-foreground">Elige un campo y una campaña.</p>
            @else
                <p class="text-sm text-muted-foreground">
                    Campo {{ $campania->campo }} · {{ $campania->nombre_campania }} · desde {{ formatear_fecha($campania->fecha_inicio) }}
                    {{ $campania->fecha_fin ? 'hasta ' . formatear_fecha($campania->fecha_fin) : '(en curso)' }} — un informe por mes, el más reciente arriba.
                </p>
                <x-table>
                    <x-slot name="thead">
                        <x-tr>
                            <x-th value="Mes" />
                            <x-th value="Corte" />
                            <x-th value="Informe" />
                            <x-th value="Costo acumulado S/" />
                            <x-th value="Costo US$/ha" />
                            <x-th value="Advertencias" />
                            <x-th value="Acciones" />
                        </x-tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @foreach ($mesesCampania as $m)
                            @php $i = $m['informe']; @endphp
                            <x-tr wire:key="camp-{{ $m['periodo'] }}">
                                <x-td class="font-semibold">
                                    {{ ucfirst(\Carbon\Carbon::create($m['anio'], $m['mes'], 1)->locale('es')->isoFormat('MMMM YYYY')) }}
                                    @if ($m['es_cierre'])
                                        <x-badge color="indigo">Cierre</x-badge>
                                    @endif
                                </x-td>
                                <x-td class="text-center">{{ formatear_fecha($m['fecha_corte']) }}</x-td>
                                <x-td class="text-center">
                                    @if ($i)
                                        <span class="text-green-700 dark:text-green-400 font-semibold">v{{ $i->version }}</span>
                                        <div class="text-xs text-muted-foreground">{{ $i->updated_at->format('d/m/Y H:i') }}{{ $i->generadoPor ? ' · ' . $i->generadoPor->name : '' }}</div>
                                    @else
                                        <span class="text-muted-foreground">Sin generar</span>
                                    @endif
                                    @if ($i && $m['falta_anterior'])
                                        <div class="text-xs text-amber-700 dark:text-amber-400"><i class="fa fa-exclamation-triangle"></i> Falta el mes anterior</div>
                                    @endif
                                </x-td>
                                <x-td class="text-right">{{ $i ? number_format($i->total_soles, 2) : '' }}</x-td>
                                <x-td class="text-right">{{ $i && $i->total_usd_ha !== null ? number_format($i->total_usd_ha, 2) : '' }}</x-td>
                                <x-td class="text-center">
                                    @if ($i)
                                        @php $avisos = $i->advertenciasVigentes(); $errores = collect($avisos)->where('nivel', 'error')->count(); @endphp
                                        @if ($errores)
                                            <x-badge color="red">{{ $errores }} faltan</x-badge>
                                        @endif
                                        @if (count($avisos) - $errores)
                                            <x-badge color="yellow">{{ count($avisos) - $errores }} revisar</x-badge>
                                        @endif
                                    @endif
                                </x-td>
                                <x-td>
                                    @include('livewire.campania.partials.informe-mensual-acciones', ['campaniaId' => $campania->id, 'anio' => $m['anio'], 'mes' => $m['mes'], 'informe' => $i])
                                </x-td>
                            </x-tr>
                        @endforeach
                    </x-slot>
                </x-table>
            @endif
        @endif
    </x-card>

    <x-dialog-modal wire:model.live="mostrarInforme" maxWidth="complete">
        <x-slot name="title">
            @if ($informe)
                <x-h3>
                    {{ $informe->es_cierre ? 'Informe de cierre' : 'Informe mensual' }} –
                    {{ ucfirst(\Carbon\Carbon::create($informe->anio, $informe->mes, 1)->locale('es')->isoFormat('MMMM YYYY')) }} –
                    Campo {{ $informe->datos['ficha']['campo'] }} · {{ $informe->datos['ficha']['campania'] }}
                </x-h3>
                <p class="text-xs text-muted-foreground">
                    Acumulado del {{ formatear_fecha($informe->fecha_desde) }} al {{ formatear_fecha($informe->fecha_corte) }} · versión {{ $informe->version }} ·
                    generado el {{ $informe->updated_at->format('d/m/Y H:i') }}{{ $informe->generadoPor ? ' por ' . $informe->generadoPor->name : '' }}
                </p>
            @endif
        </x-slot>
        <x-slot name="content">
            @if ($informe)
                @include('livewire.campania.partials.informe-mensual-tablas', ['tablas' => $tablas])
            @endif
        </x-slot>
        <x-slot name="footer">
            <div class="flex gap-2">
                @if ($informe)
                    <x-button wire:click="descargar({{ $informe->id }})"><i class="fa fa-file-excel"></i> Descargar Excel</x-button>
                @endif
                <x-button variant="secondary" wire:click="$set('mostrarInforme', false)">Cerrar</x-button>
            </div>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading wire:target="generar,recalcularDesde,generarPendientesDelMes,descargar,ver" />
</div>
