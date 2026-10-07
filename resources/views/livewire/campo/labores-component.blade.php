<div>
    <x-flex>
        <x-title>
            Labores para planilla y cuadrilla
        </x-title>
        @can(\App\Constants\Permisos::CAMPO_LABOR_GESTIONAR)
            <x-button wire:click="$dispatch('crearLabor')">
                <i class="fa fa-plus"></i> Crear nueva labor
            </x-button>
            <div x-data="{ openFileDialog() { $refs.fileLabores.click() } }">
                <x-button variant="success" type="button" @click="openFileDialog()">
                    <i class="fa fa-file-excel"></i> Importar desde Excel
                </x-button>
                <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                    x-ref="fileLabores" style="display: none;" wire:model.live="fileLabores" />
            </div>
        @endcan

    </x-flex>
    @can(\App\Constants\Permisos::CAMPO_LABOR_GESTIONAR)
        <x-card class="mt-3">
            <button type="button" class="w-full flex items-center justify-between text-left" wire:click="$toggle('verDisponibles')">
                <span>
                    <span class="font-semibold text-foreground"><i class="fa fa-recycle"></i> Códigos disponibles para reutilizar</span>
                    <span class="block text-xs text-muted-foreground">Códigos sin registros hace más de {{ $mesesReutilizar }} mes(es) (se ajusta en Sistema → Configuración). En vez de crear un código nuevo, uno de estos puede pasar a ser otra labor desde una fecha, sin cambiar sus reportes antiguos.</span>
                </span>
                <i class="fa fa-chevron-down text-muted-foreground transition-transform {{ $verDisponibles ? 'rotate-180' : '' }}"></i>
            </button>
            @if ($verDisponibles)
                <div class="mt-3 flex flex-wrap gap-2">
                    @forelse ($disponibles as $d)
                        <button type="button" wire:click="$dispatch('reasignarCodigoLabor', { id: {{ $d['id'] }} })"
                            class="px-2 py-1 rounded-md border border-border text-xs hover:bg-muted text-left"
                            title="Último registro: {{ \Illuminate\Support\Carbon::parse($d['ultimo_uso'])->format('d/m/Y') }}. Clic para reasignarlo a otra labor.">
                            <b>{{ $d['codigo'] }}</b> {{ \Illuminate\Support\Str::limit($d['nombre_labor'], 35) }}
                            <span class="text-muted-foreground">· {{ $d['meses_sin_uso'] }} mes(es){{ $d['desactivada'] ? ' · desactivada' : '' }}</span>
                        </button>
                    @empty
                        <p class="text-sm text-muted-foreground">No hay códigos sin uso hace más de {{ $mesesReutilizar }} mes(es).</p>
                    @endforelse
                </div>
            @endif
        </x-card>
    @endcan
    @if ($sinManoObra)
        <x-warning class="mt-3">
            {{ $sinManoObra }} labor(es) aún sin mano de obra asignada. La mano de obra ahora es obligatoria: sin ella sus costos caen en
            "Sin mano de obra asignada" en los costos de producción de las campañas.
            @if ($manoObraFiltro !== 'sin')
                <button type="button" class="underline font-semibold" wire:click="$set('manoObraFiltro', 'sin')">Ver cuáles</button>
            @endif
        </x-warning>
    @endif
    <x-card class="mt-3 space-y-4">

        <x-flex class="justify-between">
            <x-flex>
                <x-group-field>
                    <x-label for="search">Buscar por código o descripción</x-label>
                    <div class="relative">
                        <div
                            class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-primary dark:text-primarydark">
                            <i class="fa fa-search"></i>
                        </div>
                        <x-input type="search" wire:model.live="search" id="default-search" class="w-full !pl-10"
                            autocomplete="off" placeholder="Busca por Nombre de la labor aqui." required />
                    </div>
                </x-group-field>
                <x-select wire:model.live="manoObraFiltro" label="Mano de obra" class="w-auto">
                    <option value="">Todos</option>
                    <option value="sin">— Sin mano de obra —</option>
                    @foreach ($manoObras as $manoObra)
                        <option value="{{ $manoObra->codigo }}">{{ $manoObra->descripcion }}</option>
                    @endforeach
                </x-select>
                <x-select wire:model.live="tipoFiltro" label="Tipo" class="w-auto">
                    <option value="">Todas</option>
                    <option value="trabajo">Labores de trabajo</option>
                    <option value="suspension">De suspensión (DM, V, FR…)</option>
                </x-select>
                <x-select wire:model.live="afectoBonoFiltro" label="Afecto a bono" class="w-auto">
                    <option value="">Todos</option>
                    <option value="con_tramos">Solo afectos a bonos</option>
                    <option value="sin_tramos">No afectos a bonos</option>
                </x-select>

                <x-select wire:model.live="metodoBonoFiltro" label="Método de bono" class="w-auto">
                    <option value="">Todos</option>
                    <option value="se_paga_con_jornal">Con bonos pagados</option>
                    <option value="se_acumula">Con bonos acumulados</option>
                </x-select>

            </x-flex>
            <div>
                <x-toggle-switch :checked="$verEliminados" label="Ver desactivadas" wire:model.live="verEliminados" />
            </div>
        </x-flex>
        @can(\App\Constants\Permisos::CAMPO_LABOR_VER)
            <x-table class="mt-5">
                <x-slot name="thead">
                    <x-tr>
                        <x-th value="Código" class="text-center" />
                        <x-th value="Nombre de la Labor" />
                        <x-th value="Mano de obra" />
                        <x-th value="Suspensión" class="text-center" />
                        <x-th value="Estándar de producción" class="text-center" />
                        <x-th value="Tramos de bonificación" class="text-center" />
                        <x-th value="Acciones" class="text-center" />
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($labores && $labores->count() > 0)
                        @foreach ($labores as $indice => $labor)
                            <x-tr>
                                <x-th valign="top" value="{{ $labor->codigo }}" class="text-center" />
                                <x-td valign="top">
                                    {{ $labor->nombre_labor }}
                                    @if ($labor->vigente_desde || ($historias[$labor->codigo] ?? 0))
                                        <span class="block text-xs text-muted-foreground" title="El código se reutilizó: antes era otra labor (ver Reasignar)">
                                            <i class="fa fa-clock-rotate-left"></i>
                                            {{ $labor->vigente_desde ? 'desde ' . $labor->vigente_desde->format('d/m/Y') : '' }}
                                            {{ ($historias[$labor->codigo] ?? 0) ? '· ' . $historias[$labor->codigo] . ' labor(es) anterior(es)' : '' }}
                                        </span>
                                    @endif
                                </x-td>
                                <x-td valign="top" value="{{ $labor->manoObra?->descripcion }}" />
                                <x-td valign="top" class="text-center">
                                    @if ($labor->tipo_asistencia_codigo)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold text-gray-900 border border-black/10"
                                            style="background-color: {{ $labor->tipoAsistencia?->color ?? '#FDE68A' }}"
                                            title="Representa la asistencia {{ $labor->tipoAsistencia?->descripcion }}: sus horas van a FDM y cuentan para suspensiones">
                                            {{ $labor->tipo_asistencia_codigo }}
                                            <span class="font-normal">{{ $labor->tipoAsistencia?->descripcion }}</span>
                                        </span>
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </x-td>
                                <x-td valign="top" value="{{ $labor->estandar_produccion . ' ' . $labor->unidades }}"
                                    class="text-center" />
                                <x-td valign="top" class="text-center">
                                    {{-- Lista de tramos --}}
                                    @php
                                        $tramos = is_string($labor->tramos_bonificacion)
                                            ? json_decode($labor->tramos_bonificacion, true)
                                            : $labor->tramos_bonificacion;
                                    @endphp

                                    @if (!empty($tramos) && is_array($tramos))
                                        <ul class="text-sm text-left space-y-1">
                                            @foreach ($tramos as $tramo)
                                                <li>
                                                    Hasta <span class="font-semibold">{{ $tramo['hasta'] }}</span> unidades
                                                    &rarr;
                                                    <span class="font-semibold">S/. {{ $tramo['monto'] }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-gray-400 italic">Sin tramos</span>
                                    @endif
                                </x-td>
                                <x-td valign="top" class="text-center">
                                    <x-flex class="justify-center">
                                        @can(\App\Constants\Permisos::CAMPO_LABOR_GESTIONAR)
                                            @if ($labor->trashed())
                                                <x-button class="secondary" wire:click="restaurarLabor({{ $labor->id }})">
                                                    <i class="fa fa-undo"></i> Reactivar
                                                </x-button>
                                                <x-button variant="outline" wire:click="$dispatch('reasignarCodigoLabor', { id: {{ $labor->id }} })" title="Reutilizar el código para otra labor">
                                                    <i class="fa fa-right-left"></i> Reutilizar
                                                </x-button>
                                            @else
                                                <x-button variant="outline" wire:click="$dispatch('reasignarCodigoLabor', { id: {{ $labor->id }} })"
                                                    title="Reasignar el código a otra labor desde una fecha (los registros anteriores no cambian)">
                                                    <i class="fa fa-right-left"></i>
                                                </x-button>
                                                <x-button wire:click="$dispatch('editarLabor', { id: {{ $labor->id }} })" title="Editar (corregir el nombre cambia también cómo se ven sus registros antiguos)">
                                                    <i class="fa fa-edit"></i>
                                                </x-button>
                                                @if ($usos[$labor->codigo] ?? 0)
                                                    <x-button variant="secondary" wire:click="confirmarEliminarLabor({{ $labor->id }})"
                                                        title="Tiene {{ number_format($usos[$labor->codigo]) }} registros: solo se puede desactivar">
                                                        <i class="fa fa-ban"></i>
                                                    </x-button>
                                                @else
                                                    <x-button variant="danger" wire:click="confirmarEliminarLabor({{ $labor->id }})" title="Sin registros: se puede eliminar">
                                                        <i class="fa fa-trash"></i>
                                                    </x-button>
                                                @endif
                                            @endif
                                        @endcan
                                    </x-flex>
                                </x-td>
                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="100%">No hay Labores registrados.</x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>
            <div class="my-5">
                {{ $labores->links() }}
            </div>
        @else
            <x-danger>
                No tienes permisos para ver las labores del campo. Por favor, contacta al administrador.
            </x-danger>
        @endcan
    </x-card>
    <x-loading wire:loading />
</div>