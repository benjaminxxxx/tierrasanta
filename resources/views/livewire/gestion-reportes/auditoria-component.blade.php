<div class="space-y-4">

    {{-- ══ HEADER ══ --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <x-icon><i class="fa-solid fa-clock-rotate-left"></i></x-icon>
            <div>
                <x-title>Auditoría</x-title>
                <x-subtitle>Registro de acciones realizadas por usuarios</x-subtitle>
            </div>
        </div>
        <x-button variant="export" wire:click="exportarPdf" wire:target="exportarPdf">
            <i class="fa-solid fa-download"></i>
            <span>Exportar PDF</span>
        </x-button>
    </div>

    {{-- ══ FILTROS ══ --}}
    <x-card>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">

            <x-selector-dia
                label="Desde"
                wire:model.live="fechaDesde" />

            <x-selector-dia 
                label="Hasta"
                wire:model.live="fechaHasta" />

            <x-select label="Modelo" wire:model.live="modelo">
                <option value="">Todos los modelos</option>
                @foreach($modelosDisponibles as $m)
                    <option value="{{ $m['valor'] }}">{{ $m['etiqueta'] }}</option>
                @endforeach
            </x-select>

            <x-select label="Acción" wire:model.live="accion">
                <option value="">Todas</option>
                <option value="crear">Crear</option>
                <option value="editar">Editar</option>
                <option value="eliminar">Eliminar</option>
            </x-select>

            <x-select label="Usuario" wire:model.live="usuario">
                <option value="">Todos</option>
                @foreach($usuariosDisponibles as $u)
                    <option value="{{ $u['valor'] }}">{{ $u['etiqueta'] }}</option>
                @endforeach
            </x-select>

            <x-input
                type="text"
                label="Buscar"
                placeholder="Usuario, ID, observación..."
                wire:model.live.debounce.400ms="busqueda" />

        </div>

        <div class="mt-3 flex items-center justify-between">
            <span class="text-xs text-muted-foreground">
                {{ number_format($total) }} {{ $total === 1 ? 'registro' : 'registros' }} encontrados
            </span>
            <x-button variant="ghost" wire:click="limpiarFiltros" class="text-xs">
                <i class="fa-solid fa-xmark mr-1"></i> Limpiar filtros
            </x-button>
        </div>
    </x-card>

    {{-- ══ TABLA ══ --}}
    <x-card>
        <div class="overflow-x-auto">
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th>Fecha</x-th>
                        <x-th>Usuario</x-th>
                        <x-th>Acción</x-th>
                        <x-th>Modelo</x-th>
                        <x-th>ID</x-th>
                        <x-th>Cambios</x-th>
                        <x-th>Observación</x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @forelse($registros as $reg)
                        <x-tr>

                            {{-- Fecha --}}
                            <x-td class="whitespace-nowrap text-xs text-muted-foreground">
                                <div>{{ \Carbon\Carbon::parse($reg->fecha_accion)->format('d/m/Y') }}</div>
                                <div class="text-[11px] opacity-70">
                                    {{ \Carbon\Carbon::parse($reg->fecha_accion)->format('H:i:s') }}
                                </div>
                            </x-td>

                            {{-- Usuario --}}
                            <x-td class="whitespace-nowrap">
                                <div class="font-medium">{{ $reg->usuario_nombre }}</div>
                                <div class="text-xs text-muted-foreground">#{{ $reg->usuario_id }}</div>
                            </x-td>

                            {{-- Acción: badge de color --}}
                            <x-td>
                                @php
                                    $badgeClass = match($reg->accion) {
                                        'crear'    => 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30',
                                        'editar'   => 'bg-amber-500/20 text-amber-400 border border-amber-500/30',
                                        'eliminar' => 'bg-red-500/20 text-red-400 border border-red-500/30',
                                        default    => 'bg-white/10 text-white/60 border border-white/10',
                                    };
                                    $icono = match($reg->accion) {
                                        'crear'    => 'fa-plus',
                                        'editar'   => 'fa-pen',
                                        'eliminar' => 'fa-trash',
                                        default    => 'fa-circle',
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                    <i class="fa-solid {{ $icono }} text-[10px]"></i>
                                    {{ ucfirst($reg->accion) }}
                                </span>
                            </x-td>

                            {{-- Modelo --}}
                            <x-td class="text-xs">
                                <div class="font-mono text-indigo-400">{{ class_basename($reg->modelo) }}</div>
                            </x-td>

                            {{-- ID del modelo --}}
                            <x-td class="text-center text-xs font-mono text-muted-foreground">
                                {{ $reg->modelo_id }}
                            </x-td>

                            {{-- Cambios: expandible --}}
                            <x-td class="max-w-xs">
                                @if($reg->cambios)
                                    @php $cambios = is_array($reg->cambios) ? $reg->cambios : json_decode($reg->cambios, true); @endphp

                                    @if($reg->accion === 'editar' && isset($cambios['antes'], $cambios['despues']))
                                        <div class="space-y-0.5 text-[11px]">
                                            @foreach($cambios['despues'] as $campo => $valorNuevo)
                                                <div class="flex items-start gap-1">
                                                    <span class="font-mono text-muted-foreground shrink-0">{{ $campo }}:</span>
                                                    <span class="line-through text-red-400/70 truncate max-w-[80px]">
                                                        {{ $cambios['antes'][$campo] ?? '—' }}
                                                    </span>
                                                    <i class="fa-solid fa-arrow-right text-[9px] text-muted-foreground shrink-0 mt-0.5"></i>
                                                    <span class="text-emerald-400 truncate max-w-[80px]">{{ $valorNuevo }}</span>
                                                </div>
                                            @endforeach
                                        </div>

                                    @elseif($reg->accion === 'crear' && isset($cambios['creado']))
                                        <div class="text-[11px] text-emerald-400/80">
                                            @foreach(array_slice($cambios['creado'], 0, 3) as $k => $v)
                                                <div><span class="font-mono text-muted-foreground">{{ $k }}:</span> {{ Str::limit((string)$v, 30) }}</div>
                                            @endforeach
                                            @if(count($cambios['creado']) > 3)
                                                <div class="text-muted-foreground italic">+{{ count($cambios['creado']) - 3 }} campos más</div>
                                            @endif
                                        </div>

                                    @elseif($reg->accion === 'eliminar' && isset($cambios['eliminado']))
                                        <div class="text-[11px] text-red-400/80">
                                            @foreach(array_slice($cambios['eliminado'], 0, 3) as $k => $v)
                                                <div><span class="font-mono text-muted-foreground">{{ $k }}:</span> {{ Str::limit((string)$v, 30) }}</div>
                                            @endforeach
                                        </div>

                                    @else
                                        <span class="text-xs text-muted-foreground font-mono">
                                            {{ Str::limit(json_encode($cambios, JSON_UNESCAPED_UNICODE), 80) }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-muted-foreground text-xs">—</span>
                                @endif
                            </x-td>

                            {{-- Observación --}}
                            <x-td class="text-xs text-muted-foreground max-w-[120px] truncate">
                                {{ $reg->observacion ?? '—' }}
                            </x-td>

                        </x-tr>
                    @empty
                        <x-tr>
                            <x-td colspan="7">
                                <div class="flex flex-col items-center justify-center gap-1.5 py-10 text-center text-muted-foreground">
                                    <i class="fa-solid fa-clock-rotate-left text-3xl opacity-50"></i>
                                    <x-subtitle>Sin registros para los filtros aplicados.</x-subtitle>
                                </div>
                            </x-td>
                        </x-tr>
                    @endforelse
                </x-slot>
            </x-table>
        </div>

        {{-- Paginación --}}
        @if($registros->hasPages())
            <div class="mt-4">
                {{ $registros->links() }}
            </div>
        @endif
    </x-card>

    <x-loading wire:loading/>
</div>