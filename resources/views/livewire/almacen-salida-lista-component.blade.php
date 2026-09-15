<div x-data="{
    idsPaginaActual: @js($salidas->pluck('id')->toArray()),
    todosMarcados: false,
    toggleTodos() {
        this.todosMarcados = !this.todosMarcados;
        $wire.toggleSeleccionTodos(this.todosMarcados, this.idsPaginaActual);
    },
}" class="space-y-4">

    <x-card class="space-y-4">
        {{-- FILTROS --}}
        <x-flex>
            <x-input type="number" wire:model.live.debounce.400ms="filtros.dia" min="1" max="31" label="Día"
                placeholder="Día" />

            <x-group-field>
                <label class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Producto</label>
                <x-select wire:model.live="filtros.productoId">
                    <option value="">Todos</option>
                    @foreach ($listaProductos as $p)
                        <option value="{{ $p['id'] }}">{{ $p['label'] }}</option>
                    @endforeach
                </x-select>
            </x-group-field>

            <x-group-field>
                <label class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                    {{ $tipo === 'combustible' ? 'Maquinaria' : 'Campo' }}
                </label>
                <x-select wire:model.live="filtros.destinoId">
                    <option value="">Todos</option>
                    @foreach (($tipo === 'combustible' ? $listaMaquinarias : $listaCampos) as $d)
                        <option value="{{ $d['id'] }}">{{ $d['label'] }}</option>
                    @endforeach
                </x-select>
            </x-group-field>

            <button wire:click="limpiarFiltros"
                class="h-8 px-3 text-sm rounded border border-input bg-background text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors flex items-center gap-1.5">
                <i class="fa fa-times text-xs"></i>
                Limpiar
            </button>

            <span class="text-xs text-muted-foreground ml-auto self-end pb-1">
                {{ $salidas->total() }} resultado{{ $salidas->total() !== 1 ? 's' : '' }}
                — {{ count($seleccionados) }} seleccionado{{ count($seleccionados) !== 1 ? 's' : '' }}
            </span>
        </x-flex>

        {{-- TABLA --}}
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-border text-left text-muted-foreground">
                        <th class="p-2 w-8">
                            <input type="checkbox" x-model="todosMarcados" @change="toggleTodos()">
                        </th>
                        <th class="p-2">Fecha</th>
                        <th class="p-2">Producto</th>
                        <th class="p-2">Cant.</th>
                        <th class="p-2">{{ $tipo === 'combustible' ? 'Maquinaria' : 'Campo' }}</th>
                        @if ($tipo !== 'combustible')
                            <th class="p-2">Uso</th>
                        @endif
                        <th class="p-2">Kardex</th>
                        <th class="p-2">Costo x und</th>
                        <th class="p-2">Total</th>
                        <th class="p-2 w-24">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($salidas as $salida)
                        <tr wire:key="salida-{{ $salida->id }}" class="border-b border-border/50 hover:bg-muted/50">
                            <td class="p-2">
                                <input type="checkbox" value="{{ $salida->id }}" wire:model.live="seleccionados">
                            </td>
                            <td class="p-2">{{ \Carbon\Carbon::parse($salida->fecha_reporte)->format('d/m/Y') }}</td>
                            <td class="p-2">{{ $salida->producto?->nombre_comercial }}</td>
                            <td class="p-2">{{ number_format($salida->cantidad, 3) }}</td>
                            <td class="p-2">{{ $tipo === 'combustible' ? $salida->maquinaria?->nombre : $salida->campo_nombre }}</td>
                            @if ($tipo !== 'combustible')
                                <td class="p-2">{{ $salida->uso?->nombre ?? '—' }}</td>
                            @endif
                            <td class="p-2 text-center">{{ $salida->tipo_kardex }}</td>
                            <td class="p-2">{{ number_format($salida->costo_por_kg ?? 0, 2) }}</td>
                            <td class="p-2">{{ number_format($salida->total_costo ?? 0, 2) }}</td>
                            <td class="p-2">
                                <div class="flex items-center gap-2">
                                    <button wire:click="verHistorialSalida({{ $salida->id }})" title="Historial"
                                        class="text-muted-foreground hover:text-foreground">
                                        <i class="fa fa-history"></i>
                                    </button>
                                    <button wire:click="eliminarSalida({{ $salida->id }})"
                                        wire:confirm="¿Eliminar esta salida? Esta acción no se puede deshacer."
                                        title="Eliminar" class="text-red-500 hover:text-red-600">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-4 text-center text-muted-foreground">
                                Sin registros para este periodo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $salidas->links() }}
        </div>
    </x-card>

    @php
        $puedeGestionar = ($tipo == 'productos' && auth()->user()->can(\App\Constants\Permisos::INSUMO_SALIDA_GESTIONAR)) ||
            ($tipo == 'combustible' && auth()->user()->can(\App\Constants\Permisos::INSUMO_COMBUSTIBLE_GESTIONAR));
    @endphp

    {{-- Modal de auditoría --}}
    <x-dialog-modal wire:model.live="modalAuditoriaSalida">
        <x-slot name="title">Historial de auditoría — Salida</x-slot>
        <x-slot name="content">
            @forelse($auditoriaHistorialSalida as $entrada)
                <div class="mb-4 border-b border-border pb-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold uppercase
                            {{ $entrada['accion'] === 'crear' ? 'text-green-600' : ($entrada['accion'] === 'eliminar' ? 'text-red-600' : 'text-yellow-600') }}">
                            {{ $entrada['accion'] }}
                        </span>
                        <span class="text-gray-400 text-xs">
                            {{ \Carbon\Carbon::parse($entrada['fecha_accion'])->format('d/m/Y H:i') }}
                            — {{ $entrada['usuario_nombre'] ?? 'Sistema' }}
                        </span>
                    </div>
                    @if(!empty($entrada['cambios']) && $entrada['accion'] === 'editar')
                        <table class="mt-2 w-full text-xs text-gray-700">
                            <thead>
                                <tr class="text-left text-gray-400">
                                    <th class="pr-4">Campo</th>
                                    <th class="pr-4">Antes</th>
                                    <th>Después</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entrada['cambios']['antes'] ?? [] as $campo => $valorAntes)
                                    <tr>
                                        <td class="pr-4 font-medium text-muted-foreground">{{ $campo }}</td>
                                        <td class="pr-4 text-red-500">{{ $valorAntes ?? '—' }}</td>
                                        <td class="text-green-600">{{ $entrada['cambios']['despues'][$campo] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @empty
                <p class="text-sm text-card-foreground">Sin historial de cambios.</p>
            @endforelse
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalAuditoriaSalida', false)">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>

    @if($puedeGestionar)
        <x-inferior-derecha>
            <x-button wire:click="abrirEditarSeleccionados">
                <i class="fa fa-pen"></i> Editar seleccionados ({{ count($seleccionados) }})
            </x-button>
            <x-button variant="danger" wire:click="eliminarSeleccionados" wire:confirm="¿Eliminar todas las filas seleccionadas?">
                <i class="fa fa-trash"></i> Eliminar seleccionados
            </x-button>
            <x-button wire:click="abrirCrear">
                <i class="fa fa-plus"></i> Agregar salidas
            </x-button>
        </x-inferior-derecha>
    @endif

    {{-- El componente de formulario vive fuera de esta jerarquía; se abre/cierra vía eventos --}}
    <livewire:almacen-salida-formulario-component />

    <x-loading wire:loading />
</div>