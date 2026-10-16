<div>
    <div class="flex flex-wrap items-center gap-3">
        <x-title>Gestión de Grupos de Cuadrillas</x-title>
        @can(\App\Constants\Permisos::CUADRILLA_GRUPO_GESTIONAR)
            <x-button type="button" @click="$wire.dispatch('registrarGrupo')">
                <i class="fa fa-plus"></i> Registrar grupo
            </x-button>
        @endcan
        <x-button variant="secondary" type="button" wire:click="abrirEstadistica">
            <i class="fa fa-chart-bar"></i> Estadística por año
        </x-button>
    </div>
    <x-card2 class="mt-5">
        <x-flex class="flex-wrap items-end justify-between gap-3">
            <x-flex class="flex-wrap items-end gap-3">
                <div class="w-72">
                    <x-input type="search" label="Buscar por nombre o código" wire:model.live.debounce.400ms="buscar"
                        placeholder="Ej. JOYA, CSJ…" />
                </div>
                <x-toggle-switch :checked="$soloInactivos" wire:model.live="soloInactivos"
                    label="Solo sin tramo hace más de {{ $mesesSinTramo }} {{ $mesesSinTramo == 1 ? 'mes' : 'meses' }}" />
            </x-flex>
            @can(\App\Constants\Permisos::CUADRILLA_GRUPO_VER_ELIMINADOS)
                <x-toggle-switch :checked="$verEliminados" label="Ver eliminados" wire:model.live="verEliminados" />
            @endcan
        </x-flex>

        <x-table class="mt-5">
            <x-slot name="thead">
                <x-tr>
                    <x-th value="N°" class="text-center" />
                    <x-th value="Código" sortable="codigo" :active="$orden === 'codigo'" :direction="$direccion" />
                    <x-th value="Descripción" sortable="nombre" :active="$orden === 'nombre'" :direction="$direccion" />
                    <x-th value="Modalidad" class="text-center" />
                    <x-th value="Cuadrilleros activos" class="text-center" />
                    <x-th value="Costo Día" sortable="costo_dia_sugerido" :active="$orden === 'costo_dia_sugerido'" :direction="$direccion" />
                    <x-th value="Último tramo" sortable="ultimo_tramo" :active="$orden === 'ultimo_tramo'" :direction="$direccion" />
                    <x-th value="Creado en" sortable="created_at" :active="$orden === 'created_at'" :direction="$direccion" />
                    <x-th value="Acciones" class="text-center" />
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                @forelse ($grupos as $indice => $grupo)
                    @php
                        // Inactivo: sin tramo desde la fecha límite (si nunca estuvo en uno, se cuenta desde su creación)
                        $referencia = $grupo->ultimo_tramo ?? $grupo->created_at?->toDateString();
                        $inactivo = !$referencia || $referencia < $fechaLimite;
                    @endphp
                    <x-tr wire:key="grupo-{{ $grupo->codigo }}">
                        <x-th value="{{ $grupos->firstItem() + $indice }}" class="text-center" />
                        <x-td>
                            <div class="rounded-lg p-2 text-center text-black text-lg font-bold"
                                style="background-color:{{ $grupo->color ? $grupo->color : '#ffffff' }}">
                                {{ $grupo->codigo }}
                            </div>
                        </x-td>
                        <x-td value="{{ $grupo->nombre }}" />
                        <x-td value="{{ ucfirst($grupo->modalidad_pago) }}" class="text-center" />
                        <x-td value="{{ $grupo->cuadrilleros_count }}" class="text-center" />
                        <x-td value="{{ $grupo->costo_dia_sugerido }}"
                            class="text-center text-black  text-lg font-bold" />
                        <x-td class="text-center">
                            @if ($grupo->ultimo_tramo)
                                <div>{{ formatear_fecha($grupo->ultimo_tramo) }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ \Carbon\Carbon::parse($grupo->ultimo_tramo)->locale('es')->diffForHumans() }} · {{ $grupo->tramos }} tramo(s)
                                </div>
                            @else
                                <div class="text-muted-foreground">Nunca</div>
                            @endif
                            @if ($inactivo)
                                <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                    Sin tramo +{{ $mesesSinTramo }} {{ $mesesSinTramo == 1 ? 'mes' : 'meses' }}
                                </span>
                            @endif
                        </x-td>
                        <x-td class="text-center">
                            {{ $grupo->created_at ? formatear_fecha($grupo->created_at) : '—' }}
                        </x-td>
                        <x-td class="text-center">
                            <x-flex class="justify-center">
                                @can(\App\Constants\Permisos::CUADRILLA_GRUPO_GESTIONAR)
                                    @if (!$grupo->trashed())
                                        <x-button type="button"
                                            @click="$wire.dispatch('editarGrupo', { codigo: '{{ $grupo->codigo }}' })">
                                            <i class="fa fa-edit"></i>
                                        </x-button>

                                        <x-button variant="danger" type="button"
                                            wire:click="eliminarGrupoCuadrilla('{{ $grupo->codigo }}')">
                                            <i class="fa fa-trash"></i>
                                        </x-button>
                                    @else
                                        <x-button variant="secondary" wire:click="restaurar('{{ $grupo->codigo }}')">
                                            Restaurar
                                        </x-button>
                                    @endif
                                @endcan
                            </x-flex>
                        </x-td>
                    </x-tr>
                @empty
                    <x-tr>
                        <x-td colspan="9" class="text-center text-muted-foreground py-6">No hay grupos con esos filtros.</x-td>
                    </x-tr>
                @endforelse
            </x-slot>
        </x-table>
        <div class="mt-2">
            {{ $grupos->links() }}
        </div>
        <p class="mt-2 text-xs text-muted-foreground">
            "Sin tramo" = el grupo no está en ningún tramo que termine después del {{ formatear_fecha($fechaLimite) }}. Los meses se cambian en
            Sistema → Configuración.
        </p>
    </x-card2>

    <x-dialog-modal wire:model.live="mostrarEstadistica" maxWidth="full">
        <x-slot name="title">
            <x-h3>Participación de los grupos por año</x-h3>
        </x-slot>
        <x-slot name="content">
            @if (!$estadistica)
                <p class="text-muted-foreground">Todavía no hay registros de cuadrilla.</p>
            @else
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach (array_keys($estadistica) as $anio)
                        <button type="button" wire:click="$set('anioEstadistica', {{ $anio }})" wire:key="anio-{{ $anio }}"
                            class="px-3 py-1 rounded-lg border text-sm font-semibold {{ $anioEstadistica == $anio ? 'bg-primary text-white border-primary' : 'border-gray-300 dark:border-gray-600' }}">
                            {{ $anio }}
                        </button>
                    @endforeach
                </div>

                @php
                    $filas = $estadistica[$anioEstadistica] ?? [];
                    $maxSemanas = max(1, ...array_column($filas, 'semanas'));
                    $maxGanado = max(1, ...array_column($filas, 'ganado'));
                    $maxGastos = max(1, ...array_column($filas, 'gastos'));
                    $barra = fn($valor, $max, $color) => '<div class="h-2 rounded ' . $color . '" style="width:' . round($valor / $max * 100, 1) . '%"></div>';
                @endphp

                <x-table>
                    <x-slot name="thead">
                        <x-tr>
                            <x-th value="Grupo" class="text-left" />
                            <x-th value="Semanas que participó" />
                            <x-th value="Ganado (jornal + bonos)" />
                            <x-th value="Gastos adicionales" />
                        </x-tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @foreach ($filas as $fila)
                            <x-tr wire:key="est-{{ $anioEstadistica }}-{{ $fila['codigo'] }}">
                                <x-td>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-block w-3 h-3 rounded" style="background-color:{{ $fila['color'] ?: '#7b7b7b' }}"></span>
                                        <span class="font-semibold">{{ $fila['codigo'] }}</span>
                                    </div>
                                    <div class="text-xs text-muted-foreground">{{ $fila['nombre'] }}</div>
                                </x-td>
                                <x-td class="w-1/5">
                                    <div class="text-right font-semibold">{{ $fila['semanas'] }} <span class="text-xs font-normal text-muted-foreground">({{ $fila['dias'] }} días)</span></div>
                                    {!! $barra($fila['semanas'], $maxSemanas, 'bg-sky-500') !!}
                                </x-td>
                                <x-td class="w-1/4">
                                    <div class="text-right font-semibold" title="Jornal {{ number_format($fila['jornal'], 2) }} · Bonos {{ number_format($fila['bonos'], 2) }}">
                                        {{ number_format($fila['ganado'], 2) }}
                                    </div>
                                    {!! $barra($fila['ganado'], $maxGanado, 'bg-emerald-500') !!}
                                    <div class="text-xs text-muted-foreground text-right">bonos {{ number_format($fila['bonos'], 2) }}</div>
                                </x-td>
                                <x-td class="w-1/5">
                                    <div class="text-right font-semibold">{{ number_format($fila['gastos'], 2) }}</div>
                                    {!! $barra($fila['gastos'], $maxGastos, 'bg-amber-500') !!}
                                </x-td>
                            </x-tr>
                        @endforeach
                        <x-tr>
                            <x-th value="Total {{ $anioEstadistica }}" class="text-left" />
                            <x-th class="text-right">{{ count(array_filter($filas, fn($f) => $f['semanas'] > 0)) }} grupos con horas</x-th>
                            <x-th class="text-right">{{ number_format(array_sum(array_column($filas, 'ganado')), 2) }}</x-th>
                            <x-th class="text-right">{{ number_format(array_sum(array_column($filas, 'gastos')), 2) }}</x-th>
                        </x-tr>
                    </x-slot>
                </x-table>
                <p class="mt-3 text-xs text-muted-foreground">
                    Semanas: semanas con al menos un cuadrillero con horas en el grupo. Se usa la semana completa (lunes a domingo), por eso la
                    semana que empieza a fines de diciembre cuenta en el año siguiente, igual que su tramo. Ganado: jornal de las horas no a destajo
                    más todos los bonos. Gastos adicionales: pendientes y aprobados, por la semana de su tramo.
                </p>
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarEstadistica', false)">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>

    <livewire:cuadrilla.cuadrillero.cuadrilla-grupo-form-component />

    <x-loading wire:loading />
</div>
