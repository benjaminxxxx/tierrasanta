<div x-data="almacenSalida" class="space-y-4">

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

            <x-group-field>
                <label class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Grupo Operativo</label>
                <x-select wire:model.live="filtros.categoria">
                    <option value="">Todos</option>
                    @foreach ($listaGruposOperativos as $grupo)
                        <option value="{{ $grupo }}">{{ $grupo }}</option>
                    @endforeach
                </x-select>
            </x-group-field>

            <button wire:click="limpiarFiltros"
                class="h-8 px-3 text-sm rounded border border-input bg-background text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors flex items-center gap-1.5">
                <i class="fa fa-times text-xs"></i>
                Limpiar
            </button>

        </x-flex>
        <div wire:ignore>
            <div x-ref="tableContainerLista"></div>
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
                        <span
                            class="font-semibold uppercase
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
            <x-button wire:click="abrirCrear">
                <i class="fa fa-plus"></i> Agregar salidas
            </x-button>
        </x-inferior-derecha>
    @endif

    {{-- El componente de formulario vive fuera de esta jerarquía; se abre/cierra vía eventos --}}
    <livewire:almacen-salida-formulario-component />

    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('almacenSalida', () => ({
        hotLista: null,
        isDark: JSON.parse(localStorage.getItem('darkMode')),
        tableDataLista: [],
        tipo: @js($tipo),
        init() {
            this.initTableLista();
            Livewire.on('actualizarTabla', ({ data }) => {
                console.log(data);
                this.tableDataLista = data;
                this.initTableLista();
            })
        },
        initTableLista() {
            if (this.hotLista) {
                try {
                    this.hotLista.destroy();
                } catch (e) { }
                this.hotLista = null;
            }
            const container = this.$refs.tableContainerLista;
            if (!container) return;
            const esCombustible = this.tipo === 'combustible';

            const hotLista = new Handsontable(container, {
                ...window.HstConfig,
                data: this.tableDataLista,
                themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                columns: this.getColumnsLista(),
                contextMenu: {
                    items: {
                        // Distribución combustible (solo si aplica)
                        ...(esCombustible ? {
                            'distribucion': {
                                name: '<i class="fa fa-list"></i> &nbsp; Distribución combustible',
                                callback: () => {
                                    const selected = this.hotLista.getSelected();
                                    if (!selected) return;
                                    const fila = this.hotLista.getSourceDataAtRow(selected[0][0]);
                                    if (!fila?.id) {
                                        alert('Guarda el registro antes de ver la distribución.');
                                        return;
                                    }
                                    $wire.dispatch('abrirModalDistribucion', {
                                        salidaId: fila.id
                                    });
                                },
                            },
                            'sep1': '---------',
                        } : {}),

                        'historial': {
                            name: '<i class="fa fa-history"></i> &nbsp; Ver historial',
                            callback: () => {
                                const selected = this.hotLista.getSelected();
                                if (!selected) return;
                                const fila = this.hotLista.getSourceDataAtRow(selected[0][0]);
                                if (!fila?.id) {
                                    alert('Este registro aún no ha sido guardado.');
                                    return;
                                }
                                $wire.verHistorialSalida(fila.id);
                            },
                        },
                        'editar': {
                            name: '<i class="fa fa-edit text-blue-500"></i> &nbsp; Editar salida(s) seleccionadas',
                            callback: () => {
                                const selected = this.hotLista.getSelected(); // [ [row1, col1, row2, col2], ... ]
                                if (!selected || selected.length === 0) return;

                                const idsAEditar = [];

                                // Recorrer los rangos de selección para soportar selección múltiple o por bloques
                                selected.forEach(range => {
                                    const startRow = Math.min(range[0], range[2]);
                                    const endRow = Math.max(range[0], range[2]);

                                    for (let r = startRow; r <= endRow; r++) {
                                        const fila = this.hotLista.getSourceDataAtRow(r);
                                        if (fila?.id && !idsAEditar.includes(fila.id)) {
                                            idsAEditar.push(fila.id);
                                        }
                                    }
                                });

                                if (idsAEditar.length === 0) {
                                    alert('No hay registros guardados en la selección.');
                                    return;
                                }

                                $wire.editarSalidas(idsAEditar);
                            }
                        },
                        'sep2': '---------',

                        'eliminar': {
                            name: '<i class="fa fa-trash text-red-500"></i> &nbsp; Eliminar salida(s) seleccionadas',
                            callback: () => {
                                const selected = this.hotLista.getSelected(); // [ [row1, col1, row2, col2], ... ]
                                if (!selected || selected.length === 0) return;

                                const idsAEliminar = [];

                                // Extraer IDs únicos de todas las filas seleccionadas (soporta selecciones múltiples)
                                selected.forEach(range => {
                                    const startRow = Math.min(range[0], range[2]);
                                    const endRow = Math.max(range[0], range[2]);

                                    for (let r = startRow; r <= endRow; r++) {
                                        const fila = this.hotLista.getSourceDataAtRow(r);
                                        if (fila?.id && !idsAEliminar.includes(fila.id)) {
                                            idsAEliminar.push(fila.id);
                                        }
                                    }
                                });

                                if (idsAEliminar.length === 0) {
                                    alert('No hay registros guardados en la selección.');
                                    return;
                                }

                                const mensaje = idsAEliminar.length === 1
                                    ? '¿Eliminar la salida seleccionada?'
                                    : `¿Eliminar las ${idsAEliminar.length} salidas seleccionadas?`;

                                if (confirm(mensaje)) {
                                    $wire.eliminarSalidas(idsAEliminar);
                                }
                            }
                        }
                    },
                }

            });

            this.hotLista = hotLista;
            this.hotLista.render();
        },
        getColumnsLista() {
            const esCombustible = this.tipo === 'combustible';

            const columns = [
                {
                    data: 'fecha_reporte',
                    type: 'date',
                    dateFormat: 'YYYY-MM-DD',
                    title: 'FECHA',
                    className: '!text-center',
                    readOnly: true
                },
                {
                    data: 'producto',
                    type: 'text',
                    title: 'PRODUCTO',
                    readOnly: true,
                    width: 120,
                    readOnly: true
                },
                {
                    data: 'unidad_medida',
                    type: 'text',
                    title: 'UND',
                    readOnly: true,
                    className: '!text-center',
                },
                {
                    data: 'cantidad',
                    type: 'numeric',
                    title: 'CANTIDAD',
                    className: '!text-right',
                    readOnly: true,
                },
                // Columna DESTINO dinámica arreglada
                esCombustible ? {
                    data: 'maquinaria_id',
                    title: 'MAQUINARIA',
                    type: 'text',
                    className: '!text-center',
                    readOnly: true,
                } : {
                    data: 'campo_nombre',
                    title: 'CAMPO',
                    type: 'text',
                    className: '!text-center',
                    readOnly: true,
                },
                {
                    data: 'tipo_kardex',
                    title: 'TIPO KARDEX',
                    type: 'text',
                    className: '!text-center',
                    readOnly: true,
                },
                {
                    data: 'categoria',
                    type: 'text',
                    title: 'CATEGORIA',
                    className: '!text-center',
                    readOnly: true,
                },
                {
                    data: 'costo_por_kg',
                    type: 'numeric',
                    title: 'COSTO X UND',
                    className: '!text-center',
                    readOnly: true,
                },
                {
                    data: 'total_costo',
                    type: 'numeric',
                    title: 'TOTAL COSTO',
                    className: '!text-center',
                    readOnly: true,
                },
            ];

            if (esCombustible) {
                columns.push({
                    data: 'distribuciones_count',
                    type: 'numeric',
                    title: 'DISTRIB.',
                    className: '!text-center',
                    readOnly: true,
                });
            }

            return columns;
        }
    }));
</script>

@endscript