{{-- resources/views/livewire/gestion-cuadrilla/gestion-cuadrilla-pago-component.blade.php --}}
<div x-data="gestionPagoCuadrilla">
    <div class="space-y-6">
        <x-flex class="justify-between">
            <x-title>Gestión de pagos - Cuadrilla</x-title>
            @include('comun.selector-mes-base')
        </x-flex>

        <x-flex class="justify-between items-center">
            <x-button variant="primary" wire:click="abrirModalDesglose">
                <i class="fa-solid fa-plus"></i> Agregar Desglose
            </x-button>
            <div class="flex gap-2">
                <x-button variant="secondary" size="sm" @click="expandirTodos">
                    Expandir todos
                </x-button>
                <x-button variant="secondary" size="sm" @click="colapsarTodos">
                    Colapsar todos
                </x-button>
            </div>
        </x-flex>

        {{-- wire:key con año-mes fuerza remount de este bloque al cambiar de periodo,
        lo que re-dispara x-init y resincroniza expandidos desde localStorage --}}
        <div wire:key="lista-desgloses-{{ $anio }}-{{ $mes }}" x-init="sincronizar('desgloses_expandidos_{{ $anio }}_{{ $mes }}')"
            class="grid grid-cols-1 gap-4">

            @forelse($desgloses as $desglose)
                <x-card wire:key="desglose-{{ $desglose->id }}" class="rounded-lg border border-border overflow-hidden">
                    <x-flex class="justify-between" @click="toggle({{ $desglose->id }})">
                        <div class="flex flex-wrap items-center gap-x-6 gap-y-1">
                            <div>
                                <x-label>Vale</x-label>
                                <x-subtitle>{{ $desglose->codigo_vale ?? '—' }}</x-subtitle>
                            </div>
                            <div>
                                <x-label>Fecha</x-label>
                                <x-subtitle>{{ formatear_fecha($desglose->fecha) }}</x-subtitle>
                            </div>
                            <div>
                                <x-label>Inicial</x-label>
                                <x-subtitle>S/ {{ number_format($desglose->monto_inicial, 2) }}</x-subtitle>
                            </div>
                            <div>
                                <x-label>Gastado</x-label>
                                <x-subtitle>S/ {{ number_format($desglose->monto_total_gastos, 2) }}</x-subtitle>
                            </div>
                            <div>
                                <x-label>Saldo Final</x-label>
                                <x-subtitle class="font-bold">S/
                                    {{ number_format($desglose->saldo_final, 2) }}</x-subtitle>
                            </div>
                        </div>

                        <x-flex>
                            <x-flex @click.stop>
                                <x-button size="sm" variant="primary"
                                    wire:click="editarDesglose({{ $desglose->id }})">
                                    <i class="fa fa-edit"></i> Editar desglose
                                </x-button>
                                <x-button size="sm"
                                    @click="$wire.dispatch('abrirRegistradorDePagos',{desgloseId:{{ $desglose->id }}})"
                                    title="Módulo de pago pendiente">
                                    <i class="fa-solid fa-money-check-dollar"></i> Registrar Pago
                                </x-button>
                                <x-button variant="danger" size="sm"
                                    wire:click="confirmarEliminarDesglose({{ $desglose->id }})">
                                    <i class="fa-solid fa-trash"></i>
                                </x-button>
                            </x-flex>

                            <i class="fa-solid fa-chevron-down transition-transform"
                                :class="abierto({{ $desglose->id }}) && 'rotate-180'"></i>
                        </x-flex>
                    </x-flex>

                    <div x-show="abierto({{ $desglose->id }})" x-collapse class="p-4 border-t border-border space-y-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <x-label>Saldo Anterior</x-label>
                                <x-subtitle>S/ {{ number_format($desglose->saldo_anterior, 2) }}</x-subtitle>
                            </div>
                            <div>
                                <x-label>Entregado por</x-label>
                                <x-subtitle>{{ $desglose->entregado_por ?? '—' }}</x-subtitle>
                            </div>
                            <div>
                                <x-label>Recibido por</x-label>
                                <x-subtitle>{{ $desglose->recibido_por ?? '—' }}</x-subtitle>
                            </div>
                            <div>
                                <x-label>Estado</x-label>
                                <x-subtitle>{{ ucfirst($desglose->estado) }}</x-subtitle>
                            </div>
                        </div>
                        <div class="space-y-4">

                            <x-table>
                                <x-slot name="thead">
                                    <x-tr>
                                        <x-th class="w-48">Nro Doc.</x-th>
                                        <x-th>Descripción</x-th>
                                        <x-th class="text-right">Monto</x-th>
                                        <x-th class="text-right">Saldo</x-th>
                                        <x-th></x-th>
                                    </x-tr>
                                </x-slot>

                                <x-slot name="tbody">
                                    @forelse($desglose->detalles as $index => $detalle)
                                        <x-tr wire:key="detalle-{{ $detalle->id }}">
                                            <x-td compact>
                                                <x-input type="text"
                                                    wire:model="nro_documento.{{ $detalle->id }}" placeholder="Ej: F001-1000" class="font-mono text-xs" />
                                            </x-td>
                                            <x-td>{{ $detalle->descripcion }}</x-td>
                                            <x-td class="text-right">S/ {{ number_format($detalle->monto, 2) }}</x-td>
                                            <x-td class="text-right">S/
                                                {{ number_format($detalle->saldo_resultante, 2) }}</x-td>
                                            <x-td>
                                                <x-button variant="danger" size="xs"
                                                    wire:click="confirmarEliminarDetalle({{ $detalle->id }})">
                                                    <i class="fa-solid fa-trash"></i>
                                                </x-button>
                                            </x-td>
                                        </x-tr>
                                    @empty
                                        <x-tr>
                                            <x-td colspan="5" class="text-center text-base-400">Sin gastos
                                                registrados</x-td>
                                        </x-tr>
                                    @endforelse
                                </x-slot>
                            </x-table>

                            {{-- Botón Inferior de Guardado --}}
                            <x-flex class="justify-end mt-2">
                                <x-button variant="primary" size="sm" wire:click="guardarNumeracionCuadrillaDesglose">
                                    <i class="fa-solid fa-floppy-disk mr-1"></i> Guardar Numeración
                                </x-button>
                            </x-flex>
                        </div>
                    </div>
                </x-card>
            @empty
                <x-subtitle>No hay desgloses registrados en este periodo.</x-subtitle>
            @endforelse
        </div>
    </div>

    @include('livewire.gestion-cuadrilla.partials.modal-desglose')
    <livewire:gestion-cuadrilla.registrar-pago-wizard-component />

    <x-loading wire:loading />
</div>

@script
    <script>
        Alpine.data('gestionPagoCuadrilla', () => ({
            expandidos: [],
            claveActual: null,
            desglosesIds: @js($desglosesIds),
            ultimoId: @js($ultimoDesglose),

            // Se llama en cada x-init del contenedor (cada vez que cambia año/mes vía wire:key)
            sincronizar(clave) {

                this.claveActual = clave;
                const guardado = localStorage.getItem(clave);
                console.log(clave, guardado);
                if (guardado) {
                    try {
                        this.expandidos = JSON.parse(guardado);
                        return;
                    } catch (e) {
                        /* clave corrupta, cae al default */
                    }
                }

                // sin estado guardado: por defecto solo el más reciente queda abierto
                this.expandidos = this.ultimoId ? [this.ultimoId] : [];
                this.guardar();
            },

            guardar() {
                if (this.claveActual) {
                    localStorage.setItem(this.claveActual, JSON.stringify(this.expandidos));
                }
            },

            abierto(id) {
                return this.expandidos.includes(id);
            },

            toggle(id) {
                this.expandidos = this.abierto(id) ?
                    this.expandidos.filter(i => i !== id) : [...this.expandidos, id];
                this.guardar();
            },

            expandirTodos() {
                const ids = this.desglosesIds;
                this.expandidos = [...ids];
                this.guardar();
            },

            colapsarTodos() {
                this.expandidos = [];
                this.guardar();
            },

            init() {
                this.$watch('expandidos', () => {}); // noop, evita warning de watcher vacío si lo agregas luego

                Livewire.on('desglose-creado', ({
                    id
                }) => {
                    if (!this.expandidos.includes(id)) {
                        this.expandidos.push(id);
                        this.guardar();
                    }
                });
            },
        }));
    </script>
@endscript
