{{-- Reporte diario de regadores (DESIGN.md: barra superior; las tarjetas de cada regador siguen con su estilo) --}}
@php
    $btn2 = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25 disabled:cursor-not-allowed disabled:opacity-50';
    $btnPrincipal = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $flecha = 'inline-flex h-9 w-9 items-center justify-center rounded-[10px] border border-mist bg-paper text-graphite hover:bg-fog hover:text-ink';
    $hayRegadores = $consolidados && $consolidados->count() > 0;
@endphp
<div>
    <div class="space-y-4">
        <div class="sticky top-0 z-[999] -mx-1 space-y-3 bg-background px-1 py-2 font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-semibold text-ink">Registro diario de riego</h1>
                <div class="flex flex-wrap items-center gap-2">
                    @can(\App\Constants\Permisos::CAMPO_RIEGO_REPORTE_GESTIONAR)
                        <button type="button" class="{{ $btn2 }}" wire:click="enviarRegistroDiarioRegadores" @disabled(!$hayRegadores)
                            title="{{ $hayRegadores ? 'Pasa las horas de los regadores al registro diario de planilla y cuadrilla' : 'No hay regadores este día' }}">
                            <i class="fa fa-paper-plane"></i> Enviar a registro diario
                        </button>
                    @endcan
                    <button type="button" class="{{ $btn2 }}" wire:click="verificarSincronizacion" wire:loading.attr="disabled" @disabled(!$hayPlanilleros)
                        title="{{ $hayPlanilleros ? 'Compara las horas de riego con el registro diario de planilla' : 'No hay regadores de planilla este día' }}">
                        <i class="fa fa-rotate"></i> Verificar sincronización
                    </button>
                    <button type="button" class="{{ $btn2 }}" wire:click="descargarReporteDiario" wire:loading.attr="disabled" wire:target="descargarReporteDiario"
                        @disabled(!$hayRegistros)
                        title="{{ $hayRegistros ? 'Descarga el Excel del reporte diario (planilla, cuadrilla y riego) de la fecha' : 'No hay nada registrado este día' }}">
                        <i class="fa fa-file-excel"></i> Enviar reporte diario
                    </button>
                    @can(\App\Constants\Permisos::CAMPO_RIEGO_REPORTE_GESTIONAR)
                        <button type="button" class="{{ $btnPrincipal }}" @click="$wire.dispatch('abrirAgregarRegador', { fecha: $wire.fecha })">
                            <i class="fa fa-user-plus"></i> Agregar regadores
                        </button>
                    @endcan
                </div>
            </div>

            {{-- Fecha compacta --}}
            <div class="flex flex-wrap items-center gap-2 rounded-[10px] border border-mist bg-paper px-3 py-2">
                <button type="button" wire:click="fechaAnterior" class="{{ $flecha }}" title="Día anterior"><i class="fa fa-chevron-left text-xs"></i></button>
                <x-input type="date" wire:model.live="fecha" class="!h-9 !w-auto" />
                <button type="button" wire:click="fechaPosterior" class="{{ $flecha }}" title="Día siguiente"><i class="fa fa-chevron-right text-xs"></i></button>
                <span class="text-sm text-graphite">{{ ucfirst(\Illuminate\Support\Carbon::parse($fecha)->translatedFormat('l d \d\e F')) }}</span>
                @if (\Illuminate\Support\Carbon::parse($fecha)->isSunday())
                    <span class="rounded-[6px] bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">Domingo</span>
                @endif
                <span class="text-sm text-pewter">· {{ $consolidados?->count() ?? 0 }} regador(es)</span>
                <label class="ml-auto flex items-center gap-2 text-sm text-graphite">
                    Límite de horas
                    <x-input type="number" wire:model.live.debounce.500ms="limiteHorasDiarias" class="!h-9 !w-20 text-right tabular-nums" />
                </label>
            </div>
        </div>

        <div>
            @can(\App\Constants\Permisos::CAMPO_RIEGO_REPORTE_VER)
                @if ($hayRegadores)
                    @foreach ($consolidados as $riego)
                        <livewire:riego.reporte-diario-riego-detalle-component :resumenId="$riego->id"
                            :fecha="$riego->fecha" :tipoLabores="$catalogoLabores" :campos="$catalogoCampos"
                            wire:key="horas_riego_{{ $riego->id }}_{{ $riego->fecha }}" />
                    @endforeach
                @else
                    <div class="rounded-[10px] border border-dashed border-mist px-4 py-10 text-center">
                        <p class="text-sm text-graphite">No hay regadores el {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}.</p>
                        @can(\App\Constants\Permisos::CAMPO_RIEGO_REPORTE_GESTIONAR)
                            <button type="button" class="{{ $btnPrincipal }} mt-3" @click="$wire.dispatch('abrirAgregarRegador', { fecha: $wire.fecha })">
                                <i class="fa fa-user-plus"></i> Agregar regadores
                            </button>
                        @endcan
                    </div>
                @endif
            @else
                <x-danger>
                    No tienes permisos para ver el reporte diario de riego. Por favor, contacta al administrador.
                </x-danger>
            @endcan
        </div>
    </div>
    <x-dialog-modal wire:model.live="mostrarEnvioAReporteDiario">
        <x-slot name="title">
            Enviar resumen a registros diarios
        </x-slot>

        <x-slot name="content">
            <h4 class="text-lg text-card-foreground mb-3">
                Registros a enviar
            </h4>

            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th>Tipo</x-th>
                        <x-th>Trabajador</x-th>
                        <x-th>Campo</x-th>
                        <x-th>Labor</x-th>
                        <x-th>Hora Inicio</x-th>
                        <x-th>Hora Fin</x-th>
                        <x-th>Total Horas</x-th>
                    </x-tr>
                </x-slot>

                <x-slot name="tbody">
                    @foreach ($listaPorEnviarRegadores as $fila)
                        @php
                            $minutosTotales = round(($fila['total_horas'] ?? 0) * 60);
                            $horas = floor($minutosTotales / 60);
                            $minutos = $minutosTotales % 60;
                            $formatoTiempo = $horas . 'h' . ($minutos > 0 ? ' ' . $minutos . 'm' : '');
                        @endphp
                        <x-tr>
                            <x-td class="uppercase">{{ $fila['tipo'] }}</x-td>
                            <x-td class="!text-left">{{ $fila['trabajador_name'] }}</x-td>
                            <x-td>{{ $fila['campo'] }}</x-td>
                            <x-td>{{ $fila['labor'] }}</x-td>
                            <x-td>{{ $fila['hora_inicio'] }}</x-td>
                            <x-td>{{ $fila['hora_fin'] }}</x-td>
                            <x-td class="font-semibold text-slate-800 dark:text-slate-200">{{ $formatoTiempo }}</x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" @click="$wire.set('mostrarEnvioAReporteDiario',false)">
                Cerrar
            </x-button>
            <x-button wire:click="confirmarEnvio" wire:loading.attr="disabled">
                <i class="fa fa-paper-plane mr-1"></i>Confirmar envío
            </x-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Un solo modal para agregar regadores (recibe la fecha al abrirse; no se recrea al cambiar de día) --}}
    <livewire:riego.reporte-diario-agregar-regadores-component />

    <livewire:riego.reporte-diario-riego-horas-acumuladas-component />

    <x-loading wire:loading wire:target="fecha,fechaAnterior,fechaPosterior,confirmarEnvio,verificarSincronizacion,enviarRegistroDiarioRegadores" />
</div>
