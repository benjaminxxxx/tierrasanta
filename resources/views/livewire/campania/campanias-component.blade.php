{{-- Campañas (DESIGN.md): contadores que filtran, barra de filtros y una fila por campaña. La "Tabla técnica" sigue con su estilo. --}}
@php
    $btn2 = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btnChico = 'inline-flex h-8 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-2.5 text-[13px] font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btnPrincipal = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $tile = 'rounded-[10px] border bg-paper p-4 text-left transition-colors hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $hayFiltros = $buscar || $campoSeleccionado || $soloPorCerrar || $estado !== 'vigentes';
@endphp
<div class="space-y-4 font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">
    <x-loading wire:loading wire:target="filtrarEstado,limpiarFiltros,eliminarCampania,descargarReporteCampania,descargarInforme,verInforme,gotoPage,nextPage,previousPage,estado,campoSeleccionado,campaniaSeleccionada,soloPorCerrar,vista" />

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Campañas</h1>
            <p class="text-sm text-graphite">El ciclo de cada campo: avance, etapa, cosecha e informes. Elige una campaña para ver su informe al día.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="{{ $btn2 }}" wire:click="descargarReporteCampania" title="Excel con todas las campañas de los filtros">
                <i class="fa fa-file-excel text-steel"></i> Reporte general
            </button>
            @can(\App\Constants\Permisos::CAMPAÑA_GESTIONAR)
                <button type="button" class="{{ $btnPrincipal }}" @click="$wire.dispatch('registroCampania')">
                    <i class="fa fa-plus"></i> Registrar campaña
                </button>
            @endcan
        </div>
    </div>

    {{-- Contadores: también filtran --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @php $activo = fn($on) => $on ? 'border-ink bg-fog' : 'border-mist'; @endphp
        <button type="button" wire:click="filtrarEstado('vigentes')" class="{{ $tile }} {{ $activo($estado === 'vigentes' && !$soloPorCerrar) }}">
            <p class="text-sm text-graphite">Vigentes{{ $campoSeleccionado ? " · campo {$campoSeleccionado}" : '' }}</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums text-ink">{{ $totales['vigentes'] }}</p>
        </button>
        <button type="button" wire:click="filtrarEstado('vigentes', true)" class="{{ $tile }} {{ $activo($soloPorCerrar) }}">
            <p class="text-sm text-graphite">Cosechadas que conviene cerrar</p>
            <p class="mt-1 flex items-center gap-2 text-3xl font-semibold tabular-nums {{ $totales['por_cerrar'] ? 'text-red-700 dark:text-red-400' : 'text-ink' }}">
                {{ $totales['por_cerrar'] }}
                @if ($totales['por_cerrar'])<i class="fa fa-flag-checkered text-base"></i>@endif
            </p>
        </button>
        <button type="button" wire:click="filtrarEstado('cerradas')" class="{{ $tile }} {{ $activo($estado === 'cerradas') }}">
            <p class="text-sm text-graphite">Cerradas</p>
            <p class="mt-1 text-3xl font-semibold tabular-nums text-ink">{{ $totales['cerradas'] }}</p>
        </button>
    </div>

    {{-- Filtros --}}
    <div class="rounded-[10px] border border-mist bg-paper p-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="flex flex-wrap items-end gap-3">
                <div class="w-64">
                    <x-input type="search" wire:model.live.debounce.400ms="buscar" label="Buscar" placeholder="Campo o campaña (ej. A11, T.2026)" />
                </div>
                <div class="w-44">
                    <x-select-campo label="Campo" wire:model.live="campoSeleccionado" />
                </div>
                @if (is_array($campanias) && count($campanias) > 0)
                    <x-select wire:model.live="campaniaSeleccionada" label="Campaña" class="w-auto">
                        <option value="">Todas</option>
                        @foreach ($campanias as $campaniaId => $campaniaNombre)
                            <option value="{{ $campaniaId }}">{{ $campaniaNombre }}</option>
                        @endforeach
                    </x-select>
                @endif
                <x-select wire:model.live="estado" label="Estado" class="w-auto">
                    <option value="vigentes">Vigentes</option>
                    <option value="cerradas">Cerradas</option>
                    <option value="todas">Todas</option>
                </x-select>
                <label class="flex h-9 items-center gap-2 text-sm text-graphite">
                    <input type="checkbox" wire:model.live="soloPorCerrar" class="rounded border-mist text-carmin focus:ring-carmin/25"> Solo las que conviene cerrar
                </label>
                @if ($hayFiltros)
                    <button type="button" class="{{ $btn2 }}" wire:click="limpiarFiltros"><i class="fa fa-xmark text-steel"></i> Limpiar</button>
                @endif
            </div>
            {{-- Vista: pestañas --}}
            <div class="flex gap-5 border-b border-hairline text-sm font-medium">
                @foreach (['lista' => ['fa-list', 'Lista'], 'tabla' => ['fa-table', 'Tabla técnica']] as $valor => [$ico, $texto])
                    <button type="button" wire:click="$set('vista', '{{ $valor }}')"
                        class="-mb-px flex items-center gap-1.5 border-b-2 pb-2 {{ $vista === $valor ? 'border-carmin text-ink' : 'border-transparent text-graphite hover:text-ink' }}">
                        <i class="fa {{ $ico }}"></i> {{ $texto }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    @can(\App\Constants\Permisos::CAMPAÑA_RESUMEN_VER)
        @if ($vista === 'tabla')
            @include('livewire.campania.partials.campanias-tabla-tecnica')
        @else
            @php
                $puedeGestionar = auth()->user()->can(\App\Constants\Permisos::CAMPAÑA_GESTIONAR);
                $puedeInforme = auth()->user()->can(\App\Constants\Permisos::CAMPAÑA_INFORME_MENSUAL);
            @endphp
            <div class="overflow-hidden rounded-[10px] border border-mist bg-paper">
                @forelse ($campaniasGenerales as $campania)
                    @php
                        $c = $cosecha[$campania->id] ?? null;
                        $a = $avances[$campania->id] ?? null;
                        $e = $etapas[$campania->id] ?? null;
                        $inf = $ultimosInformes[$campania->id] ?? null;
                        $cerrada = (bool) $campania->fecha_fin;
                    @endphp
                    <div wire:key="camp-{{ $campania->id }}" class="flex flex-col gap-4 border-t border-hairline p-4 first:border-t-0 hover:bg-fog/50 lg:flex-row lg:items-center">
                        {{-- Identidad --}}
                        <div class="flex shrink-0 items-center gap-3 lg:w-72">
                            <div class="flex h-14 w-16 shrink-0 items-center justify-center rounded-[10px] border border-mist bg-fog text-center text-lg font-semibold leading-tight text-ink">
                                {{ $campania->campo }}
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="truncate font-semibold text-ink">{{ $campania->nombre_campania }}</span>
                                    @if ($cerrada)
                                        <span class="rounded-[6px] bg-fog px-1.5 py-0.5 text-[11px] font-medium text-graphite">Cerrada</span>
                                    @else
                                        <span class="rounded-[6px] bg-green-50 px-1.5 py-0.5 text-[11px] font-medium text-green-700 dark:bg-green-400/10 dark:text-green-400">Vigente</span>
                                    @endif
                                </div>
                                <div class="text-xs text-pewter">
                                    {{ $campania->area ? rtrim(rtrim(number_format((float) $campania->area, 2), '0'), '.') . ' ha' : 'Sin área' }}{{ $campania->variedad_tuna ? ' · ' . $campania->variedad_tuna : '' }}
                                </div>
                                <div class="text-xs tabular-nums text-graphite">
                                    {{ formatear_fecha($campania->fecha_inicio) }} → {{ $cerrada ? formatear_fecha($campania->fecha_fin) : 'hoy' }}
                                </div>
                            </div>
                        </div>

                        {{-- Avance vegetativo --}}
                        <div class="shrink-0 lg:w-52">
                            @if ($a)
                                @php $lleno = $a['avance_pct'] >= 100; @endphp
                                <div class="flex justify-between text-xs">
                                    <span class="text-graphite">Avance vegetativo</span>
                                    <span class="font-semibold tabular-nums {{ $lleno ? 'text-red-700 dark:text-red-400' : 'text-ink' }}">{{ number_format($a['avance_pct'], 1) }} %</span>
                                </div>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-fog" role="progressbar" aria-valuenow="{{ round($a['avance_pct']) }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="h-1.5 rounded-full {{ $lleno ? 'bg-red-600 dark:bg-red-400' : 'bg-[#2a78d6] dark:bg-[#3987e5]' }}" style="width: {{ min(100, $a['avance_pct']) }}%"></div>
                                </div>
                                <div class="mt-1 text-xs tabular-nums text-pewter" title="Meses proyectados: {{ implode(' + ', $a['meses_proyectados']) }}">
                                    {{ number_format($a['meses_acumulados'], 1) }} de {{ $a['meses_proyectados_total'] }} meses{{ $lleno ? ' · cumplido' : '' }}
                                </div>
                            @else
                                <span class="text-xs text-pewter">Aún no inicia</span>
                            @endif
                        </div>

                        {{-- Etapa y cosecha --}}
                        <div class="min-w-0 flex-1 space-y-1.5">
                            @if ($e)
                                <span class="inline-flex items-center gap-1.5 rounded-[6px] border border-mist px-2 py-0.5 text-xs font-medium text-ink">
                                    <span class="h-2 w-2 rounded-full" style="background: {{ $e['color'] }}"></span> {{ $e['nombre'] }}
                                </span>
                            @endif
                            <div class="text-sm">
                                @include('livewire.campania.partials.campanias-estado-cosecha', ['estado' => $c])
                            </div>
                        </div>

                        {{-- Informes --}}
                        <div class="shrink-0 text-xs lg:w-40">
                            @if ($inf)
                                <div class="text-graphite">Informes mensuales: <span class="font-semibold tabular-nums text-ink">{{ $inf['cantidad'] }}</span></div>
                                <div class="text-pewter">Último {{ sprintf('%02d/%d', $inf['ultimo']->mes, $inf['ultimo']->anio) }}{{ $inf['ultimo']->es_cierre ? ' (cierre)' : '' }}</div>
                            @else
                                <div class="text-pewter">Sin informes mensuales</div>
                            @endif
                        </div>

                        {{-- Acciones --}}
                        <div class="flex shrink-0 flex-wrap gap-1.5 lg:justify-end">
                            @if ($puedeInforme)
                                <button type="button" class="{{ $btnChico }}" wire:click="verInforme({{ $campania->id }})" title="{{ $cerrada ? 'Informe final (al cierre)' : 'Informe al día de hoy' }}">
                                    <i class="fa fa-eye text-steel"></i> {{ $cerrada ? 'Informe final' : 'Informe actual' }}
                                </button>
                                <a class="{{ $btnChico }}" href="{{ route('campania.informes_mensuales', ['vista' => 'campania', 'campo' => $campania->campo, 'campaniaId' => $campania->id]) }}"
                                    title="Informes guardados al cierre de cada mes">
                                    <i class="fa fa-calendar-days text-steel"></i> Mensuales
                                </a>
                            @endif
                            <x-dropdown align="right" width="60">
                                <x-slot name="trigger">
                                    <button type="button" class="{{ $btnChico }} !px-2" title="Más acciones"><i class="fa fa-ellipsis-vertical text-steel"></i></button>
                                </x-slot>
                                <x-slot name="content">
                                    <x-dropdown-link href="{{ route('campania.por_campo', ['campania' => $campania->id]) }}">
                                        <i class="fa fa-folder-open w-4 text-steel"></i> Detalle de la campaña
                                    </x-dropdown-link>
                                    <x-dropdown-link class="cursor-pointer" @click="$wire.dispatch('verHistoriaCampania', {campaniaId: {{ $campania->id }}})">
                                        <i class="fa fa-book-open w-4 text-steel"></i> Historia de la campaña
                                    </x-dropdown-link>
                                    @if ($puedeInforme)
                                        <x-dropdown-link class="cursor-pointer" wire:click="descargarInforme({{ $campania->id }})">
                                            <i class="fa fa-file-excel w-4 text-steel"></i> Excel del {{ $cerrada ? 'informe final' : 'informe actual' }}
                                        </x-dropdown-link>
                                    @endif
                                    @if ($puedeGestionar)
                                        <div class="my-1 border-t border-hairline"></div>
                                        <x-dropdown-link class="cursor-pointer" @click="$wire.dispatch('editarCampania', {campaniaId: {{ $campania->id }}})">
                                            <i class="fa fa-pen w-4 text-steel"></i> Editar campaña
                                        </x-dropdown-link>
                                        @if ($c && $c['recomendar_cierre'])
                                            <x-dropdown-link class="cursor-pointer !text-red-700 dark:!text-red-400"
                                                @click="$wire.dispatch('cerrarCampania', {campaniaId: {{ $campania->id }}, fecha: '{{ $c['fecha_cierre_sugerida']->toDateString() }}'})">
                                                <i class="fa fa-flag-checkered w-4"></i> Cerrar campaña ({{ formatear_fecha($c['fecha_cierre_sugerida']) }})
                                            </x-dropdown-link>
                                        @elseif (!$cerrada)
                                            <x-dropdown-link class="cursor-pointer" @click="$wire.dispatch('cerrarCampania', {campaniaId: {{ $campania->id }}})">
                                                <i class="fa fa-flag-checkered w-4 text-steel"></i> Cerrar campaña
                                            </x-dropdown-link>
                                        @endif
                                        <x-dropdown-link class="cursor-pointer !text-red-700 dark:!text-red-400"
                                            wire:confirm="¿Eliminar la campaña {{ $campania->nombre_campania }} del campo {{ $campania->campo }}?"
                                            wire:click="eliminarCampania({{ $campania->id }})">
                                            <i class="fa fa-trash w-4"></i> Eliminar campaña
                                        </x-dropdown-link>
                                    @endif
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-10 text-center">
                        <p class="text-sm text-graphite">No hay campañas con estos filtros{{ $campoSeleccionado ? " en el campo {$campoSeleccionado}" : '' }}.</p>
                        @if ($hayFiltros)
                            <button type="button" class="{{ $btn2 }} mt-3" wire:click="limpiarFiltros"><i class="fa fa-xmark text-steel"></i> Quitar filtros</button>
                        @endif
                    </div>
                @endforelse
            </div>
            <div class="my-4">{{ $campaniasGenerales->links() }}</div>
        @endif
    @else
        <x-danger>No tienes permiso para ver las campañas. Por favor, contacta al administrador.</x-danger>
    @endcan

    {{-- Informe al día / final --}}
    <x-dialog-modal wire:model.live="mostrarInforme" maxWidth="complete">
        <x-slot name="title">
            @if ($informe)
                @php $f = $informe->datos['ficha']; @endphp
                <p class="text-lg font-semibold text-ink">{{ $informe->es_cierre ? 'Informe final' : 'Informe al día' }} · Campo {{ $f['campo'] }} · {{ $f['campania'] }}</p>
                <p class="text-xs font-normal text-pewter">
                    Acumulado del {{ formatear_fecha($f['fecha_inicio']) }} al {{ formatear_fecha($f['fecha_corte']) }} · calculado ahora, no se guarda
                    (los informes guardados de cada mes están en "Mensuales").
                </p>
            @endif
        </x-slot>
        <x-slot name="content">
            @if ($informe)
                @include('livewire.campania.partials.informe-mensual-tablas', ['tablas' => $tablas])
            @endif
        </x-slot>
        <x-slot name="footer">
            <div class="flex flex-wrap gap-2">
                <button type="button" class="{{ $btn2 }}" wire:click="$set('mostrarInforme', false)">Cerrar</button>
                @if ($informe)
                    <a class="{{ $btn2 }}" href="{{ route('campania.informes_mensuales', ['vista' => 'campania', 'campo' => $informe->datos['ficha']['campo'], 'campaniaId' => $informeCampaniaId]) }}">
                        <i class="fa fa-calendar-days text-steel"></i> Informes mensuales
                    </a>
                    <button type="button" class="{{ $btnPrincipal }}" wire:click="descargarInforme({{ $informeCampaniaId }})"><i class="fa fa-file-excel"></i> Descargar Excel</button>
                @endif
            </div>
        </x-slot>
    </x-dialog-modal>

    {{-- Modales de campaña: se montan solo en las páginas que los usan --}}
    <livewire:campania.campania-ficha-component />
    <livewire:campania.campania-cerrar-component />
    <livewire:campania.campania-historia-component />
</div>
@script
<script>
    Alpine.data('resumenCampanias', () => ({
        clave: 'campania.resumen.bloques',
        bloques: { poblacion: false, brotes: false, infestacion: false, reinfestacion: false, cosecha: false, financiero: false },
        init() {
            try {
                const guardado = JSON.parse(localStorage.getItem(this.clave) || 'null');
                if (guardado) this.bloques = { ...this.bloques, ...guardado };
            } catch (e) {}
        },
        guardar() {
            try { localStorage.setItem(this.clave, JSON.stringify(this.bloques)); } catch (e) {}
        },
        alternar(bloque) {
            this.bloques[bloque] = !this.bloques[bloque];
            this.guardar();
        },
        todos(visible) {
            Object.keys(this.bloques).forEach(b => this.bloques[b] = visible);
            this.guardar();
        },
    }));
</script>
@endscript
