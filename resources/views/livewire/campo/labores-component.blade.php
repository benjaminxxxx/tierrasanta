{{-- Labores (lista) según DESIGN.md. La importación/exportación en Excel es el botón flotante "Recursos" (labores-indice). --}}
@php
    $tarjeta = 'rounded-[10px] border border-mist bg-paper';
    $control = 'h-9 rounded-[10px] border border-mist bg-paper px-3 text-sm text-ink focus:border-carmin focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $iconoBtn = 'inline-flex h-8 w-8 items-center justify-center rounded-md text-graphite hover:bg-fog hover:text-ink focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
@endphp
<div class="font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em] text-ink space-y-4">

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold">Labores</h1>
            <p class="text-sm text-graphite">Labores de planilla y cuadrilla: código, mano de obra, estándar de producción y tramos de bono.</p>
        </div>
        @can(\App\Constants\Permisos::CAMPO_LABOR_GESTIONAR)
            <button type="button" wire:click="$dispatch('crearLabor')"
                class="h-9 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25">
                <i class="fa fa-plus"></i> Nueva labor
            </button>
        @endcan
    </div>

    @if ($sinManoObra)
        <div class="flex flex-wrap items-center gap-2 rounded-[10px] bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">
            <i class="fa fa-triangle-exclamation"></i>
            <span>{{ $sinManoObra }} labor(es) sin mano de obra: sus costos caen en "Sin mano de obra asignada" en los costos de las campañas.</span>
            @if ($manoObraFiltro !== 'sin')
                <button type="button" class="font-semibold underline underline-offset-4" wire:click="$set('manoObraFiltro', 'sin')">Ver cuáles</button>
            @endif
        </div>
    @endif

    @can(\App\Constants\Permisos::CAMPO_LABOR_GESTIONAR)
        <div class="{{ $tarjeta }}">
            <button type="button" class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left" wire:click="$toggle('verDisponibles')">
                <span>
                    <span class="text-sm font-semibold"><i class="fa fa-recycle text-graphite"></i> Códigos disponibles para reutilizar</span>
                    <span class="block text-xs text-pewter">Sin registros hace más de {{ $mesesReutilizar }} mes(es) (se ajusta en Sistema → Configuración). En vez de crear un código nuevo, uno de estos puede pasar a ser otra labor desde una fecha, sin cambiar sus reportes antiguos.</span>
                </span>
                <i class="fa fa-chevron-down text-steel transition-transform {{ $verDisponibles ? 'rotate-180' : '' }}"></i>
            </button>
            @if ($verDisponibles)
                <div class="flex flex-wrap gap-2 border-t border-hairline px-5 py-4">
                    @forelse ($disponibles as $d)
                        <button type="button" wire:click="$dispatch('reasignarCodigoLabor', { id: {{ $d['id'] }} })"
                            class="rounded-md border border-mist px-2 py-1 text-left text-xs hover:bg-fog"
                            title="Último registro: {{ \Illuminate\Support\Carbon::parse($d['ultimo_uso'])->format('d/m/Y') }}. Clic para reasignarlo a otra labor.">
                            <b class="tabular-nums">{{ $d['codigo'] }}</b> {{ \Illuminate\Support\Str::limit($d['nombre_labor'], 35) }}
                            <span class="text-pewter">· {{ $d['meses_sin_uso'] }} mes(es){{ $d['desactivada'] ? ' · desactivada' : '' }}</span>
                        </button>
                    @empty
                        <p class="text-sm text-pewter">No hay códigos sin uso hace más de {{ $mesesReutilizar }} mes(es).</p>
                    @endforelse
                </div>
            @endif
        </div>
    @endcan

    @can(\App\Constants\Permisos::CAMPO_LABOR_VER)
        <div class="{{ $tarjeta }}">
            {{-- Filtros --}}
            <div class="flex flex-wrap items-end gap-3 border-b border-hairline p-4">
                <div class="min-w-[16rem] flex-1">
                    <label for="buscar-labor" class="mb-1 block text-xs font-medium text-graphite">Buscar</label>
                    <div class="relative">
                        <i class="fa fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-steel"></i>
                        <input id="buscar-labor" type="search" wire:model.live.debounce.300ms="search" autocomplete="off"
                            placeholder="Código o nombre de la labor" class="{{ $control }} w-full pl-8 placeholder:text-steel" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-graphite">Mano de obra</label>
                    <select wire:model.live="manoObraFiltro" class="{{ $control }} pr-8">
                        <option value="">Todas</option>
                        <option value="sin">— Sin mano de obra —</option>
                        @foreach ($manoObras as $manoObra)
                            <option value="{{ $manoObra->codigo }}">{{ $manoObra->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-graphite">Tipo</label>
                    <select wire:model.live="tipoFiltro" class="{{ $control }} pr-8">
                        <option value="">Todas</option>
                        <option value="trabajo">De trabajo</option>
                        <option value="suspension">De suspensión (DM, V, FR…)</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-graphite">Bono</label>
                    <select wire:model.live="afectoBonoFiltro" class="{{ $control }} pr-8">
                        <option value="">Todas</option>
                        <option value="con_tramos">Con tramos de bono</option>
                        <option value="sin_tramos">Sin tramos</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-graphite">Pago del bono</label>
                    <select wire:model.live="metodoBonoFiltro" class="{{ $control }} pr-8">
                        <option value="">Todos</option>
                        <option value="se_paga_con_jornal">Con el jornal</option>
                        <option value="se_acumula">Se acumula</option>
                    </select>
                </div>
                <label class="flex h-9 items-center gap-2 text-sm text-graphite">
                    <input type="checkbox" wire:model.live="verEliminados" class="h-4 w-4 rounded border-mist text-carmin focus:ring-carmin/25" />
                    Ver desactivadas
                </label>
            </div>

            {{-- Tabla --}}
            <div class="overflow-x-auto">
                <table class="w-full text-[13px]">
                    <thead class="bg-fog text-xs text-graphite">
                        <tr>
                            <th class="px-3 py-2 text-right font-medium">Código</th>
                            <th class="px-3 py-2 text-left font-medium">Labor</th>
                            <th class="px-3 py-2 text-left font-medium">Mano de obra</th>
                            <th class="px-3 py-2 text-left font-medium">Suspensión</th>
                            <th class="px-3 py-2 text-right font-medium">Estándar</th>
                            <th class="px-3 py-2 text-left font-medium">Tramos de bono</th>
                            <th class="px-3 py-2 text-right font-medium"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($labores as $labor)
                            @php
                                $tramos = is_string($labor->tramos_bonificacion) ? json_decode($labor->tramos_bonificacion, true) : $labor->tramos_bonificacion;
                                $tramos = is_string($tramos) ? json_decode($tramos, true) : $tramos;
                            @endphp
                            <tr class="border-t border-hairline align-top hover:bg-fog {{ $labor->trashed() ? 'text-pewter' : '' }}">
                                <td class="px-3 py-2 text-right font-semibold tabular-nums">{{ $labor->codigo }}</td>
                                <td class="px-3 py-2">
                                    {{ $labor->nombre_labor }}
                                    @if ($labor->trashed())
                                        <span class="ml-1 rounded-md bg-fog px-1.5 py-0.5 text-[11px] font-medium text-graphite">Desactivada</span>
                                    @endif
                                    @if ($labor->vigente_desde || ($historias[$labor->codigo] ?? 0))
                                        <span class="block text-xs text-pewter" title="El código se reutilizó: antes era otra labor (ver Reasignar)">
                                            <i class="fa fa-clock-rotate-left"></i>
                                            {{ $labor->vigente_desde ? 'desde ' . $labor->vigente_desde->format('d/m/Y') : '' }}
                                            {{ ($historias[$labor->codigo] ?? 0) ? '· ' . $historias[$labor->codigo] . ' labor(es) anterior(es)' : '' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 {{ $labor->manoObra ? '' : 'text-pewter' }}">{{ $labor->manoObra?->descripcion ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    @if ($labor->tipo_asistencia_codigo)
                                        {{-- El color es el del tipo de asistencia (identifica el código en la planilla) --}}
                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-fog px-2 py-0.5 text-xs font-medium"
                                            title="Representa la asistencia {{ $labor->tipoAsistencia?->descripcion }}: sus horas van a FDM y cuentan para suspensiones">
                                            <span class="h-2.5 w-2.5 rounded-sm border border-black/10" style="background-color: {{ $labor->tipoAsistencia?->color ?? '#FDE68A' }}"></span>
                                            {{ $labor->tipo_asistencia_codigo }} <span class="font-normal text-graphite">{{ $labor->tipoAsistencia?->descripcion }}</span>
                                        </span>
                                    @else
                                        <span class="text-pewter">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums">
                                    @if ($labor->estandar_produccion)
                                        {{ $labor->estandar_produccion }} <span class="text-pewter">{{ $labor->unidades }}</span>
                                    @else
                                        <span class="text-pewter">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if (!empty($tramos) && is_array($tramos))
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($tramos as $tramo)
                                                <span class="rounded-md bg-fog px-1.5 py-0.5 text-xs tabular-nums text-graphite">
                                                    hasta {{ $tramo['hasta'] ?? '?' }} → <b class="text-ink">S/ {{ $tramo['monto'] ?? '?' }}</b>
                                                </span>
                                            @endforeach
                                        </div>
                                        <span class="text-xs text-pewter">{{ $labor->se_paga_con_jornal ? 'Se paga con el jornal' : 'Se acumula' }}</span>
                                    @else
                                        <span class="text-pewter">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-1.5">
                                    @can(\App\Constants\Permisos::CAMPO_LABOR_GESTIONAR)
                                        <div class="flex justify-end gap-0.5">
                                            @if ($labor->trashed())
                                                <button type="button" class="{{ $iconoBtn }}" wire:click="restaurarLabor({{ $labor->id }})" title="Reactivar">
                                                    <i class="fa fa-rotate-left"></i>
                                                </button>
                                                <button type="button" class="{{ $iconoBtn }}" wire:click="$dispatch('reasignarCodigoLabor', { id: {{ $labor->id }} })" title="Reutilizar el código para otra labor">
                                                    <i class="fa fa-right-left"></i>
                                                </button>
                                            @else
                                                <button type="button" class="{{ $iconoBtn }}" wire:click="$dispatch('editarLabor', { id: {{ $labor->id }} })"
                                                    title="Editar (corregir el nombre cambia también cómo se ven sus registros antiguos)">
                                                    <i class="fa fa-pen"></i>
                                                </button>
                                                <button type="button" class="{{ $iconoBtn }}" wire:click="$dispatch('reasignarCodigoLabor', { id: {{ $labor->id }} })"
                                                    title="Reasignar el código a otra labor desde una fecha (los registros anteriores no cambian)">
                                                    <i class="fa fa-right-left"></i>
                                                </button>
                                                @if ($usos[$labor->codigo] ?? 0)
                                                    <button type="button" class="{{ $iconoBtn }}" wire:click="confirmarEliminarLabor({{ $labor->id }})"
                                                        title="Tiene {{ number_format($usos[$labor->codigo]) }} registros: solo se puede desactivar">
                                                        <i class="fa fa-ban"></i>
                                                    </button>
                                                @else
                                                    <button type="button" class="{{ $iconoBtn }} hover:!text-red-700 dark:hover:!text-red-400" wire:click="confirmarEliminarLabor({{ $labor->id }})"
                                                        title="Sin registros: se puede eliminar">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-8 text-center text-sm text-pewter">No hay labores con esos filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-hairline px-4 py-3">
                {{ $labores->links() }}
            </div>
        </div>
    @else
        <div class="rounded-[10px] bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-400/10 dark:text-red-400">
            <i class="fa fa-circle-exclamation"></i> No tienes permiso para ver las labores. Pídelo al administrador.
        </div>
    @endcan

    <x-loading wire:loading />
</div>
