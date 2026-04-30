<div>
    <x-loading wire:loading/>

    {{-- ══ HEADER ══ --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <x-icon><i class="fa-solid fa-map"></i></x-icon>
            <div>
                <x-title>Campos</x-title>
                <x-subtitle>Gestión y seguimiento de campos de cultivo</x-subtitle>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <x-button variant="export" wire:click="exportarPdf" wire:target="exportarPdf">
                <i class="fa-solid fa-download"></i>
                <span>Exportar PDF</span>
            </x-button>
            <x-button wire:click="registrarCampo">
                <i class="fa fa-plus"></i> Agregar Campo
            </x-button>
        </div>
    </div>

    {{-- ══ TARJETAS DE ESTADO (filtro rápido) ══ --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <button wire:click="setFiltroEstado('todos')"
            @class([
                'rounded-xl border p-4 text-left transition-all',
                'border-white/20 bg-white/10'  => $filtroEstado === 'todos',
                'border-white/5 bg-white/[0.03] hover:bg-white/[0.06]' => $filtroEstado !== 'todos',
            ])>
            <div class="text-2xl font-bold text-card-foreground">{{ $totales['todos'] }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">Total campos</div>
        </button>

        <button wire:click="setFiltroEstado('activo')"
            @class([
                'rounded-xl border p-4 text-left transition-all',
                'border-emerald-500/40 bg-emerald-500/10' => $filtroEstado === 'activo',
                'border-white/5 bg-white/[0.03] hover:bg-white/[0.06]' => $filtroEstado !== 'activo',
            ])>
            <div class="text-2xl font-bold text-emerald-400">{{ $totales['activos'] }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">Campos activos</div>
        </button>

        <button wire:click="setFiltroEstado('inactivo')"
            @class([
                'rounded-xl border p-4 text-left transition-all',
                'border-slate-500/40 bg-slate-500/10' => $filtroEstado === 'inactivo',
                'border-white/5 bg-white/[0.03] hover:bg-white/[0.06]' => $filtroEstado !== 'inactivo',
            ])>
            <div class="text-2xl font-bold text-slate-400">{{ $totales['inactivos'] }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">Sin campaña activa</div>
        </button>

        <button wire:click="setFiltroEstado('alerta')"
            @class([
                'rounded-xl border p-4 text-left transition-all',
                'border-amber-500/40 bg-amber-500/10' => $filtroEstado === 'alerta',
                'border-white/5 bg-white/[0.03] hover:bg-white/[0.06]' => $filtroEstado !== 'alerta',
            ])>
            <div class="text-2xl font-bold text-amber-400">{{ $totales['alertas'] }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i>Posible sin cerrar
            </div>
        </button>
    </div>

    {{-- ══ FILTRO DE BÚSQUEDA ══ --}}
    <x-card class="mb-4">
        <x-select-campo wire:model.live="filtroCampo" label="Filtrar por campo" />
    </x-card>

    {{-- ══ TABLA ══ --}}
    <x-card>
        <div class="overflow-x-auto">
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th>Campo</x-th>
                        <x-th>Padre</x-th>
                        <x-th align="right">Área (ha)</x-th>
                        <x-th>Estado</x-th>
                        <x-th>Campaña activa</x-th>
                        <x-th align="right">Campañas</x-th>
                        <x-th>Última siembra</x-th>
                        <x-th align="right">Siembras</x-th>
                        <x-th>Acciones</x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @forelse($campos as $campo)
                        <x-tr>

                            {{-- Campo + alias --}}
                            <x-td>
                                <div class="font-mono font-bold text-sm">{{ $campo->nombre }}</div>
                                @if($campo->alias)
                                    <div class="text-[11px] text-muted-foreground mt-0.5">
                                        {{ collect(explode(',', $campo->alias))->map(fn($a) => trim($a))->implode(' · ') }}
                                    </div>
                                @endif
                                @if($campo->campo_parent_nombre)
                                    <div class="text-[11px] text-indigo-400/70 mt-0.5">
                                        <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                        Sub de {{ $campo->campo_parent_nombre }}
                                    </div>
                                @endif
                            </x-td>

                            {{-- Campo padre --}}
                            <x-td>
                                @if($campo->campo_parent_nombre)
                                    <span class="font-mono text-sm text-indigo-400">
                                        {{ $campo->campo_parent_nombre }}
                                    </span>
                                @else
                                    <span class="text-muted-foreground">—</span>
                                @endif
                            </x-td>

                            {{-- Área --}}
                            <x-td align="right">
                                <span class="font-mono">{{ number_format($campo->area, 3) }}</span>
                            </x-td>

                            {{-- Estado --}}
                            <x-td>
                                @if($campo->tiene_alerta)
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                        <i class="fa-solid fa-triangle-exclamation text-[10px]"></i>
                                        Alerta
                                    </span>
                                @elseif($campo->es_activo)
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                        <i class="fa-solid fa-circle text-[8px]"></i>
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold bg-white/5 text-muted-foreground border border-white/10">
                                        <i class="fa-solid fa-circle text-[8px]"></i>
                                        Inactivo
                                    </span>
                                @endif
                            </x-td>

                            {{-- Campaña activa --}}
                            <x-td>
                                @if($campo->campana_activa)
                                    <div class="text-sm font-semibold text-emerald-400">
                                        {{ $campo->campana_activa->nombre_campania }}
                                    </div>
                                    <div class="text-[11px] text-muted-foreground">
                                        desde {{ \Carbon\Carbon::parse($campo->campana_activa->fecha_inicio)->format('d/m/Y') }}
                                    </div>
                                    @if($campo->tiene_alerta)
                                        <div class="text-[11px] text-amber-400 mt-0.5">
                                            <i class="fa-solid fa-triangle-exclamation mr-0.5"></i>
                                            +2 años sin cerrar
                                        </div>
                                    @endif
                                @elseif($campo->ultima_campana)
                                    <div class="text-sm text-muted-foreground">
                                        {{ $campo->ultima_campana->nombre_campania }}
                                    </div>
                                    <div class="text-[11px] text-muted-foreground/60">
                                        cerrada {{ \Carbon\Carbon::parse($campo->ultima_campana->fecha_fin)->format('d/m/Y') }}
                                    </div>
                                @else
                                    <span class="text-muted-foreground text-sm">Sin campaña</span>
                                @endif
                            </x-td>

                            {{-- Total campañas --}}
                            <x-td align="right">
                                <span class="font-mono font-semibold {{ $campo->total_campanas > 0 ? 'text-indigo-400' : 'text-muted-foreground' }}">
                                    {{ $campo->total_campanas }}
                                </span>
                            </x-td>

                            {{-- Última siembra --}}
                            <x-td>
                                @if($campo->ultima_siembra)
                                    <div class="text-sm">
                                        {{ \Carbon\Carbon::parse($campo->ultima_siembra->fecha_siembra)->format('d/m/Y') }}
                                    </div>
                                    <div class="text-[11px] text-muted-foreground">
                                        hace {{ \Carbon\Carbon::parse($campo->ultima_siembra->fecha_siembra)->diffForHumans(null, true) }}
                                    </div>
                                @else
                                    <span class="text-muted-foreground text-sm">—</span>
                                @endif
                            </x-td>

                            {{-- Total siembras --}}
                            <x-td align="right">
                                <span class="font-mono font-semibold {{ $campo->total_siembras > 0 ? 'text-sky-400' : 'text-muted-foreground' }}">
                                    {{ $campo->total_siembras }}
                                </span>
                            </x-td>

                            {{-- Acciones --}}
                            <x-td>
                                <div class="flex items-center gap-1.5">
                                    <x-button
                                        @click="$wire.dispatch('registroCampania', {campoNombre: '{{ $campo->nombre }}'})">
                                        <i class="fa fa-plus"></i>
                                    </x-button>

                                    <x-button variant="secondary"
                                        wire:click="editarCampo('{{ $campo->nombre }}')">
                                        <i class="fa fa-edit"></i>
                                    </x-button>

                                    <x-button variant="ghost"
                                        wire:click="verAuditoria('{{ $campo->nombre }}')"
                                        title="Ver historial">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </x-button>
                                </div>
                            </x-td>

                        </x-tr>
                    @empty
                        <x-tr>
                            <x-td colspan="9">
                                <div class="flex flex-col items-center justify-center gap-1.5 py-10 text-center text-muted-foreground">
                                    <i class="fa-solid fa-map text-3xl opacity-50"></i>
                                    <x-subtitle>No hay campos con los filtros aplicados.</x-subtitle>
                                </div>
                            </x-td>
                        </x-tr>
                    @endforelse
                </x-slot>
            </x-table>
        </div>

        <div class="mt-4">
            {{ $campos->links() }}
        </div>
    </x-card>

    {{-- ══ MODAL FORMULARIO ══ --}}
    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
            {{ $estaEditando ? 'Editar Campo' : 'Registrar Campo' }}
        </x-slot>
        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-group-field>
                    <x-input-string label="Nombre del campo" wire:model="campoNombre" error="campoNombre"/>
                </x-group-field>
                <x-group-field>
                    <x-select-campo label="Campo Padre" wire:model="campoPadre"/>
                </x-group-field>
                <x-group-field>
                    <x-input-string label="Área (ha)" wire:model="area" error="area"/>
                </x-group-field>
                <x-group-field>
                    <x-input-string
                        label="Alias (separados por coma)"
                        wire:model="alias"
                        placeholder="Ej: Limon5, limonero 5"/>
                </x-group-field>
            </div>
        </x-slot>
        <x-slot name="footer">
            <div class="flex justify-end gap-2 w-full">
                <x-button variant="secondary" wire:click="$set('mostrarFormulario', false)">
                    Cerrar
                </x-button>
                <x-button wire:click="storeCampos">
                    <i class="fa fa-save"></i>
                    {{ $estaEditando ? 'Guardar cambios' : 'Registrar Campo' }}
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>

    {{-- ══ MODAL AUDITORÍA ══ --}}
    <x-dialog-modal wire:model.live="modalAuditoria">
        <x-slot name="title">
            Historial — Campo {{ $campoAuditoriaLabel }}
        </x-slot>
        <x-slot name="content">
            @php
                $entradaCreacion = collect($auditoriaHistorial)->firstWhere('accion', 'crear');
                $ultimaEdicion   = collect($auditoriaHistorial)->where('accion', 'editar')->first();
            @endphp

            <div class="flex gap-6 mb-4 text-xs text-muted-foreground border-b border-border pb-3">
                <div>
                    <span class="font-semibold text-card-foreground">Creado por:</span>
                    {{ $entradaCreacion['usuario_nombre'] ?? '—' }}
                    @if($entradaCreacion)
                        <span class="ml-1 text-muted-foreground/60">
                            {{ \Carbon\Carbon::parse($entradaCreacion['fecha_accion'])->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
                <div>
                    <span class="font-semibold text-card-foreground">Última edición:</span>
                    {{ $ultimaEdicion['usuario_nombre'] ?? '—' }}
                    @if($ultimaEdicion)
                        <span class="ml-1 text-muted-foreground/60">
                            {{ \Carbon\Carbon::parse($ultimaEdicion['fecha_accion'])->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
            </div>

            @forelse($auditoriaHistorial as $entrada)
                <div class="mb-4 border-b border-border pb-3">
                    <div class="flex items-center justify-between text-sm mb-2">
                        @php
                            $badgeClass = match($entrada['accion']) {
                                'crear'    => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
                                'editar'   => 'bg-amber-500/20 text-amber-400 border-amber-500/30',
                                'eliminar' => 'bg-red-500/20 text-red-400 border-red-500/30',
                                default    => 'bg-white/10 text-white/60 border-white/10',
                            };
                        @endphp
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold border {{ $badgeClass }}">
                            {{ ucfirst($entrada['accion']) }}
                        </span>
                        <span class="text-xs text-muted-foreground">
                            {{ \Carbon\Carbon::parse($entrada['fecha_accion'])->format('d/m/Y H:i') }}
                            — {{ $entrada['usuario_nombre'] ?? 'Sistema' }}
                        </span>
                    </div>

                    @if(!empty($entrada['cambios']))
                        @if($entrada['accion'] === 'editar')
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-muted-foreground">
                                        <th class="text-left pr-4 pb-1">Campo</th>
                                        <th class="text-left pr-4 pb-1">Antes</th>
                                        <th class="text-left pb-1">Después</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($entrada['cambios']['antes'] ?? [] as $campo => $valorAntes)
                                        <tr>
                                            <td class="pr-4 font-mono text-muted-foreground">{{ $campo }}</td>
                                            <td class="pr-4 text-red-400">{{ $valorAntes ?? '—' }}</td>
                                            <td class="text-emerald-400">{{ $entrada['cambios']['despues'][$campo] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <pre class="text-xs bg-white/5 rounded p-2 overflow-auto max-h-40 text-muted-foreground">{{ json_encode(array_values($entrada['cambios'])[0] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        @endif
                    @endif

                    @if(!empty($entrada['observacion']))
                        <p class="mt-1 text-xs italic text-muted-foreground">{{ $entrada['observacion'] }}</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-muted-foreground">Sin historial de cambios registrado.</p>
            @endforelse
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalAuditoria', false)">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>

</div>