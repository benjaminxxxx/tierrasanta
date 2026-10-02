<div class="space-y-4">
    <div>
        <x-title>Historial de caja</x-title>
        <x-subtitle>Quién cerró y reabrió cada mes, y quién registró, editó o eliminó cada movimiento.</x-subtitle>
    </div>

    <div class="flex gap-2 border-b border-border">
        @foreach (['cierres' => 'Cierres y reaperturas', 'actividad' => 'Actividad de movimientos'] as $clave => $titulo)
            <button type="button" wire:click="$set('pestana', '{{ $clave }}')"
                class="px-4 py-2 text-sm font-medium -mb-px border-b-2 {{ $pestana === $clave ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground hover:text-foreground' }}">
                {{ $titulo }}
            </button>
        @endforeach
    </div>

    @if ($pestana === 'cierres')
        <x-card class="space-y-4">
            <div class="flex items-end gap-3">
                <x-select label="Año" wire:model.live="anio" class="w-auto">
                    @foreach ($anios as $a)
                        <option value="{{ $a }}">{{ $a }}</option>
                    @endforeach
                </x-select>
            </div>

            @forelse ($cierres as $m)
                <div class="rounded-lg border border-border p-3" wire:key="mes-{{ $anio }}-{{ $m['mes'] }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <span class="font-semibold text-foreground">{{ $m['nombre'] }} {{ $anio }}</span>
                            @if ($m['estado'] === 'cerrado')
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300"><i class="fa fa-lock"></i> Cerrado</span>
                            @elseif ($m['estado'] === 'abierto')
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"><i class="fa fa-lock-open"></i> Reabierto</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-muted text-muted-foreground">Nunca cerrado</span>
                            @endif
                            <span class="text-xs text-muted-foreground">{{ number_format($m['movimientos']) }} movimientos</span>
                        </div>
                        <span class="text-xs text-muted-foreground">
                            Cerrado {{ $m['cerrado_veces'] }} {{ $m['cerrado_veces'] === 1 ? 'vez' : 'veces' }} ·
                            reabierto {{ $m['reabierto_veces'] }} {{ $m['reabierto_veces'] === 1 ? 'vez' : 'veces' }}
                        </span>
                    </div>
                    @if (count($m['eventos']))
                        <ol class="mt-3 space-y-1 border-l-2 border-border pl-4">
                            @foreach ($m['eventos'] as $e)
                                <li class="text-sm relative">
                                    <span class="absolute -left-[1.4rem] top-1.5 h-2.5 w-2.5 rounded-full {{ $e['accion'] === 'cerrado' ? 'bg-green-600' : 'bg-amber-500' }}"></span>
                                    <span class="font-medium">{{ $e['accion'] === 'cerrado' ? 'Cerrado' : 'Reabierto' }}</span>
                                    el {{ $e['fecha'] }} por <b>{{ $e['usuario'] }}</b>
                                    @if ($e['saldo'] !== null)
                                        <span class="text-muted-foreground">· saldo S/ {{ number_format($e['saldo'], 2) }} · {{ $e['movimientos'] }} mov.</span>
                                    @endif
                                    @if ($e['motivo'])
                                        <span class="block text-xs text-muted-foreground">{{ $e['accion'] === 'cerrado' ? 'Observación' : 'Motivo' }}: {{ $e['motivo'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            @empty
                <p class="text-sm text-muted-foreground">No hay movimientos de caja en {{ $anio }}.</p>
            @endforelse
        </x-card>
    @else
        <x-card class="space-y-3">
            <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                <x-input type="date" label="Desde" wire:model.live="desde" />
                <x-input type="date" label="Hasta" wire:model.live="hasta" />
                <x-select label="Qué" wire:model.live="tipo">
                    <option value="">Todo</option>
                    @foreach ($tipos as $clase => $nombre)
                        <option value="{{ $clase }}">{{ $nombre }}</option>
                    @endforeach
                </x-select>
                <x-select label="Acción" wire:model.live="accion">
                    <option value="">Todas</option>
                    <option value="crear">Registró</option>
                    <option value="editar">Editó</option>
                    <option value="eliminar">Eliminó</option>
                </x-select>
                <x-select label="Usuario" wire:model.live="usuario">
                    <option value="">Todos</option>
                    @foreach ($usuarios as $u)
                        <option value="{{ $u['id'] }}">{{ $u['nombre'] }}</option>
                    @endforeach
                </x-select>
                <x-input type="search" label="Buscar" wire:model.live.debounce.400ms="buscar" placeholder="Beneficiario, motivo…" />
            </div>
            <div class="flex justify-end">
                <x-button size="xs" variant="outline" wire:click="limpiarFiltros"><i class="fa fa-eraser"></i> Quitar filtros</x-button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted text-muted-foreground text-xs">
                        <tr>
                            <th class="p-2 text-left whitespace-nowrap">Fecha y hora</th>
                            <th class="p-2 text-left">Usuario</th>
                            <th class="p-2 text-left">Acción</th>
                            <th class="p-2 text-left">Registro</th>
                            <th class="p-2 text-left">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($actividad as $a)
                            <tr class="border-t border-border align-top" wire:key="act-{{ $a['id'] }}">
                                <td class="p-2 whitespace-nowrap tabular-nums">{{ $a['fecha'] }}</td>
                                <td class="p-2 whitespace-nowrap">{{ $a['usuario'] }}</td>
                                <td class="p-2 whitespace-nowrap">
                                    @php
                                        [$texto, $clase] = match ($a['accion']) {
                                            'crear' => ['Registró', 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'],
                                            'editar' => ['Editó', 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'],
                                            'eliminar' => ['Eliminó', 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'],
                                            default => [ucfirst($a['accion']), 'bg-muted text-muted-foreground'],
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $clase }}">{{ $texto }}</span>
                                    <span class="block text-xs text-muted-foreground mt-1">{{ $a['tipo'] }}</span>
                                </td>
                                <td class="p-2">{{ $a['registro'] }}</td>
                                <td class="p-2 text-xs">
                                    @if ($a['observacion'])
                                        <div class="mb-1">{{ $a['observacion'] }}</div>
                                    @endif
                                    @foreach ($a['cambios'] as $c)
                                        <div>
                                            <span class="text-muted-foreground">{{ $c['campo'] }}:</span>
                                            <span class="line-through text-red-700 dark:text-red-400">{{ $c['antes'] ?? '—' }}</span>
                                            → <span class="text-green-700 dark:text-green-400">{{ $c['despues'] ?? '—' }}</span>
                                        </div>
                                    @endforeach
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-4 text-center text-muted-foreground">Sin actividad con estos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $actividad->links() }}</div>
            <p class="text-xs text-muted-foreground">Los movimientos importados del Excel no tienen alta individual: su registro es la importación.</p>
        </x-card>
    @endif
    <x-loading wire:loading />
</div>
