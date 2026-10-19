<div class="space-y-4">
    <x-loading wire:loading wire:target="filtrarEstado,limpiarFiltros,eliminarCampania,descargarReporteCampania,descargarInforme,verInforme,gotoPage,nextPage,previousPage,estado,campoSeleccionado,campaniaSeleccionada,soloPorCerrar,vista" />

    <x-flex class="justify-between flex-wrap gap-3">
        <div>
            <x-title>Campañas</x-title>
            <x-subtitle>El ciclo de cada campo: avance, etapa, cosecha e informes. Elige una campaña para ver su informe al día.</x-subtitle>
        </div>
        @can(\App\Constants\Permisos::CAMPAÑA_GESTIONAR)
            <x-button @click="$wire.dispatch('registroCampania')">
                <i class="fa fa-plus"></i> Registrar nueva campaña
            </x-button>
        @endcan
    </x-flex>

    {{-- Contadores: también sirven de atajo para filtrar --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <button type="button" wire:click="filtrarEstado('vigentes')"
            class="text-left rounded-lg border p-4 bg-card transition-colors hover:bg-muted {{ $estado === 'vigentes' && !$soloPorCerrar ? 'ring-2 ring-primary' : '' }}">
            <p class="text-sm text-muted-foreground">Campañas vigentes{{ $campoSeleccionado ? " · campo {$campoSeleccionado}" : '' }}</p>
            <p class="text-3xl font-bold text-foreground">{{ $totales['vigentes'] }}</p>
        </button>
        <button type="button" wire:click="filtrarEstado('vigentes', true)"
            class="text-left rounded-lg border p-4 bg-card transition-colors hover:bg-muted {{ $soloPorCerrar ? 'ring-2 ring-red-500' : '' }}">
            <p class="text-sm text-muted-foreground">Cosechadas que conviene cerrar</p>
            <p class="text-3xl font-bold {{ $totales['por_cerrar'] ? 'text-red-600 dark:text-red-400' : 'text-foreground' }}">{{ $totales['por_cerrar'] }}</p>
        </button>
        <button type="button" wire:click="filtrarEstado('cerradas')"
            class="text-left rounded-lg border p-4 bg-card transition-colors hover:bg-muted {{ $estado === 'cerradas' ? 'ring-2 ring-primary' : '' }}">
            <p class="text-sm text-muted-foreground">Campañas cerradas</p>
            <p class="text-3xl font-bold text-foreground">{{ $totales['cerradas'] }}</p>
        </button>
    </div>

    <x-card>
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="flex flex-wrap items-end gap-3">
                <div class="w-64">
                    <x-input type="search" wire:model.live.debounce.400ms="buscar" label="Buscar" placeholder="Campo o campaña (ej. A11, T.2026)" />
                </div>
                <x-select-campo label="Campo" wire:model.live="campoSeleccionado" class="w-auto" />
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
                <label class="flex items-center gap-2 text-sm pb-2">
                    <input type="checkbox" wire:model.live="soloPorCerrar" class="rounded"> Solo las que conviene cerrar
                </label>
                @if ($buscar || $campoSeleccionado || $soloPorCerrar || $estado !== 'vigentes')
                    <x-button variant="secondary" wire:click="limpiarFiltros" class="mb-0.5"><i class="fa fa-times"></i> Limpiar</x-button>
                @endif
            </div>
            <div class="flex items-end gap-2">
                <div class="flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                    <button type="button" wire:click="$set('vista', 'lista')" class="px-3 py-2 text-sm font-semibold {{ $vista === 'lista' ? 'bg-primary text-white' : '' }}">
                        <i class="fa fa-list"></i> Lista
                    </button>
                    <button type="button" wire:click="$set('vista', 'tabla')" class="px-3 py-2 text-sm font-semibold {{ $vista === 'tabla' ? 'bg-primary text-white' : '' }}">
                        <i class="fa fa-table"></i> Tabla técnica
                    </button>
                </div>
                <x-button variant="success" wire:click="descargarReporteCampania">
                    <i class="fa fa-file-excel"></i> Reporte general
                </x-button>
            </div>
        </div>
    </x-card>

    @can(\App\Constants\Permisos::CAMPAÑA_RESUMEN_VER)
        @if ($vista === 'tabla')
            @include('livewire.campania.partials.campanias-tabla-tecnica')
        @else
            @php
                $puedeGestionar = auth()->user()->can(\App\Constants\Permisos::CAMPAÑA_GESTIONAR);
                $puedeInforme = auth()->user()->can(\App\Constants\Permisos::CAMPAÑA_INFORME_MENSUAL);
            @endphp
            <div class="space-y-2">
                @forelse ($campaniasGenerales as $campania)
                    @php
                        $c = $cosecha[$campania->id] ?? null;
                        $a = $avances[$campania->id] ?? null;
                        $e = $etapas[$campania->id] ?? null;
                        $inf = $ultimosInformes[$campania->id] ?? null;
                        $cerrada = (bool) $campania->fecha_fin;
                    @endphp
                    <div wire:key="camp-{{ $campania->id }}" class="rounded-lg border border-border bg-card p-3 flex flex-col lg:flex-row lg:items-center gap-3">
                        {{-- Identidad --}}
                        <div class="flex items-center gap-3 lg:w-64 shrink-0">
                            <div class="rounded-lg bg-primary/10 text-primary font-bold text-lg w-16 h-14 flex items-center justify-center text-center leading-tight">
                                {{ $campania->campo }}
                            </div>
                            <div>
                                <div class="font-semibold text-foreground">{{ $campania->nombre_campania }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ $campania->area ? $campania->area . ' ha' : 'Sin área' }}{{ $campania->variedad_tuna ? ' · ' . $campania->variedad_tuna : '' }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ formatear_fecha($campania->fecha_inicio) }} →
                                    @if ($cerrada)
                                        {{ formatear_fecha($campania->fecha_fin) }}
                                    @else
                                        <span class="text-green-700 dark:text-green-400 font-semibold">vigente</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Avance vegetativo --}}
                        <div class="lg:w-56 shrink-0">
                            @if ($a)
                                <div class="flex justify-between text-xs">
                                    <span class="text-muted-foreground">Avance vegetativo</span>
                                    <span class="font-semibold">{{ number_format($a['avance_pct'], 1) }} %</span>
                                </div>
                                <div class="h-2 rounded bg-muted overflow-hidden mt-1">
                                    <div class="h-2 {{ $a['avance_pct'] >= 100 ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ min(100, $a['avance_pct']) }}%"></div>
                                </div>
                                <div class="text-xs text-muted-foreground mt-1" title="Meses proyectados: {{ implode(' + ', $a['meses_proyectados']) }}">
                                    {{ number_format($a['meses_acumulados'], 1) }} de {{ $a['meses_proyectados_total'] }} meses
                                </div>
                            @else
                                <span class="text-xs text-muted-foreground">Aún no inicia</span>
                            @endif
                        </div>

                        {{-- Etapa y cosecha --}}
                        <div class="flex-1 min-w-0 space-y-1">
                            @if ($e)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold text-white" style="background: {{ $e['color'] }}">{{ $e['nombre'] }}</span>
                            @endif
                            <div class="text-sm">
                                @include('livewire.campania.partials.campanias-estado-cosecha', ['estado' => $c])
                            </div>
                        </div>

                        {{-- Informes --}}
                        <div class="lg:w-44 shrink-0 text-xs">
                            @if ($inf)
                                <div class="text-muted-foreground">Informes mensuales: <span class="font-semibold text-foreground">{{ $inf['cantidad'] }}</span></div>
                                <div class="text-muted-foreground">Último: {{ sprintf('%02d/%d', $inf['ultimo']->mes, $inf['ultimo']->anio) }}{{ $inf['ultimo']->es_cierre ? ' (cierre)' : '' }}</div>
                            @else
                                <div class="text-muted-foreground">Sin informes mensuales</div>
                            @endif
                        </div>

                        {{-- Acciones --}}
                        <div class="flex flex-wrap gap-1 lg:justify-end shrink-0">
                            @if ($puedeInforme)
                                <x-button size="sm" wire:click="verInforme({{ $campania->id }})" title="{{ $cerrada ? 'Informe final (al cierre)' : 'Informe al día de hoy' }}">
                                    <i class="fa fa-eye"></i> {{ $cerrada ? 'Informe final' : 'Informe actual' }}
                                </x-button>
                                <x-button size="sm" variant="secondary"
                                    :href="route('campania.informes_mensuales', ['vista' => 'campania', 'campo' => $campania->campo, 'campaniaId' => $campania->id])"
                                    title="Informes guardados al cierre de cada mes">
                                    <i class="fa fa-calendar-alt"></i> Mensuales
                                </x-button>
                            @endif
                            <x-dropdown align="right">
                                <x-slot name="trigger">
                                    <x-button type="button" size="sm" variant="secondary"><i class="fa fa-ellipsis-v"></i></x-button>
                                </x-slot>
                                <x-slot name="content">
                                    <x-dropdown-link href="{{ route('campania.por_campo', ['campania' => $campania->id]) }}">
                                        <i class="fa fa-folder-open w-4"></i> Detalle de la campaña
                                    </x-dropdown-link>
                                    <x-dropdown-link class="cursor-pointer" @click="$wire.dispatch('verHistoriaCampania', {campaniaId: {{ $campania->id }}})">
                                        <i class="fa fa-book-open w-4"></i> Historia de la campaña
                                    </x-dropdown-link>
                                    @if ($puedeInforme)
                                        <x-dropdown-link class="cursor-pointer" wire:click="descargarInforme({{ $campania->id }})">
                                            <i class="fa fa-file-excel w-4"></i> Excel del {{ $cerrada ? 'informe final' : 'informe actual' }}
                                        </x-dropdown-link>
                                    @endif
                                    @if ($puedeGestionar)
                                        <x-dropdown-link class="cursor-pointer" @click="$wire.dispatch('editarCampania', {campaniaId: {{ $campania->id }}})">
                                            <i class="fa fa-edit w-4"></i> Editar campaña
                                        </x-dropdown-link>
                                        @if ($c && $c['recomendar_cierre'])
                                            <x-dropdown-link class="cursor-pointer !text-red-600"
                                                @click="$wire.dispatch('cerrarCampania', {campaniaId: {{ $campania->id }}, fecha: '{{ $c['fecha_cierre_sugerida']->toDateString() }}'})">
                                                <i class="fa fa-flag-checkered w-4"></i> Cerrar campaña ({{ formatear_fecha($c['fecha_cierre_sugerida']) }})
                                            </x-dropdown-link>
                                        @elseif (!$cerrada)
                                            <x-dropdown-link class="cursor-pointer" @click="$wire.dispatch('cerrarCampania', {campaniaId: {{ $campania->id }}})">
                                                <i class="fa fa-flag-checkered w-4"></i> Cerrar campaña
                                            </x-dropdown-link>
                                        @endif
                                        <x-dropdown-link class="cursor-pointer !text-red-600"
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
                    <x-card class="text-center text-muted-foreground py-8">No hay campañas con estos filtros.</x-card>
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
                <x-h3>{{ $informe->es_cierre ? 'Informe final' : 'Informe al día' }} – Campo {{ $f['campo'] }} · {{ $f['campania'] }}</x-h3>
                <p class="text-xs text-muted-foreground">
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
            <div class="flex gap-2">
                @if ($informe)
                    <x-button variant="success" wire:click="descargarInforme({{ $informeCampaniaId }})"><i class="fa fa-file-excel"></i> Descargar Excel</x-button>
                    <x-button variant="secondary"
                        :href="route('campania.informes_mensuales', ['vista' => 'campania', 'campo' => $informe->datos['ficha']['campo'], 'campaniaId' => $informeCampaniaId])">
                        <i class="fa fa-calendar-alt"></i> Informes mensuales
                    </x-button>
                @endif
                <x-button variant="secondary" wire:click="$set('mostrarInforme', false)">Cerrar</x-button>
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
