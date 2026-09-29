<div class="space-y-6" x-data="asistenciaMensual()" x-init="init()">
    <x-flex class="justify-between">
        <div>
            <x-title>
                Asistencia Mensual
            </x-title>
            <x-subtitle>
                Reporte mensual de horas y costo de empleados
            </x-subtitle>
        </div>
        <x-flex>
            @include('comun.selector-mes-base')
        </x-flex>
    </x-flex>
    <x-card>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-input x-model="busqueda" icon="magnifying-glass" placeholder="Buscar empleado por nombre..."
                class="sm:max-w-xs" />

            <div class="flex flex-wrap gap-2">
                <x-button size="sm" x-on:click="filtro = 'todos'"
                    x-bind:variant="filtro === 'todos' ? 'primary' : 'filled'">
                    Todos
                </x-button>
                <x-button size="sm" x-on:click="filtro = 'permiso'"
                    x-bind:variant="filtro === 'permiso' ? 'primary' : 'filled'">
                    Con permiso
                </x-button>
                <x-button size="sm" x-on:click="filtro = 'descanso_medico'"
                    x-bind:variant="filtro === 'descanso_medico' ? 'primary' : 'filled'">
                    Descanso médico
                </x-button>
                <x-button size="sm" x-on:click="filtro = 'vacaciones'"
                    x-bind:variant="filtro === 'vacaciones' ? 'primary' : 'filled'">
                    Vacaciones
                </x-button>
            </div>
        </div>

        <x-button variant="secondary" wire:click="$set('mostrandoModalOrden', true)">
            Cambiar orden de visualización
        </x-button>
    </x-card>

    <div x-ref="scrollTabla" x-on:scroll="sincronizarDesdeTabla"
        class="relative max-h-[65vh] overflow-auto rounded-lg border border-border">
        <table class="w-max min-w-full border-collapse text-sm">
            <thead>
                <tr>
                    <x-th
                        class="sticky top-0 left-0 z-30 min-w-[20px] bg-muted text-center">
                        GRUPO
                    </x-th>
                    <th
                        class="sticky top-0 left-[20px] z-30 min-w-[100px] bg-muted text-center">
                        GRUPO
                    </th>
                    <th
                        class="sticky top-0 left-[100px] z-30 min-w-[220px] bg-muted text-left">
                        NOMBRES
                    </th>
                    @foreach ($dias as $dia)
                        <th class="sticky top-0 z-20 min-w-[42px] border-b border-r px-1 py-2 text-center border-border"
                            style="background-color: {{ $dia['es_domingo'] ? '#FFC000' : '' }}">
                            {{ $dia['titulo'] }}<br>{{ $dia['indice'] }}
                        </th>
                    @endforeach
                    <th
                        class="sticky top-0 right-0 z-30 min-w-[80px] border-b bg-muted border-border px-2 py-2 text-center">
                        TOTAL
                    </th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(empleado, index) in filtrados()" :key="empleado.id">
                    <tr x-show="coincideBusqueda(empleado)" class="hover:bg-muted">
                        <x-td class="sticky left-0 z-10 bg-muted text-center"
                            x-text="index + 1"></x-td>
                        <x-td class="sticky left-[20px] z-10 bg-muted text-center"
                            x-text="empleado.grupo"></x-td>
                        <td class="sticky left-[100px] z-10 cursor-pointer border-b border-r bg-muted px-2 py-1 border-border"
                            x-on:click="$wire.verDetalle(empleado.id)" x-text="empleado.nombre_completo"></td>
                        <template x-for="dia in diasIndices" :key="dia">
                            <td class="border-b border-r border-border px-1 py-1 text-center"
                                x-bind:style="'background-color:' + (empleado.dias[dia]?.color || 'transparent')"
                                x-bind:title="empleado.dias[dia]?.descripcion || ''"
                                x-text="empleado.dias[dia]?.horas ?? ''"></td>
                        </template>
                        <td class="border-b bg-muted border-border px-2 py-1 text-center font-semibold"
                            x-text="empleado.total_horas"></td>
                    </tr>
                </template>

                <tr x-show="filtrados().length === 0">
                    <td class="px-3 py-6 text-center text-zinc-400" x-bind:colspan="diasIndices.length + 3">
                        No se encontraron empleados con los filtros aplicados.
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Scrollbar horizontal fija abajo de la pantalla (estilo phpMyAdmin) --}}
        <div x-ref="scrollFijo" x-on:scroll="sincronizarDesdeFijo" x-show="mostrarScrollFijo"
            class="fixed bottom-0 left-0 right-0 z-40 h-4 overflow-x-auto overflow-y-hidden bg-white/80 backdrop-blur dark:bg-zinc-900/80"
            style="display:none">
            <div x-bind:style="'width:' + anchoTabla + 'px; height:1px;'"></div>
        </div>

    </div>
    {{-- Modal de configuración de orden --}}
    <x-dialog-modal wire:model.live="mostrandoModalOrden">
        <x-slot name="title">
            Configurar orden de la planilla
        </x-slot>

        <x-slot name="content">
            <div class="space-y-3">
                <p class="text-sm text-zinc-500">
                    Elige el orden de prioridad para ordenar la lista de empleados. Cada campo solo puede usarse una
                    vez.
                </p>

                <template x-for="(fila, index) in filas" :key="index">
                    <div class="flex items-center gap-2">
                        <span class="w-6 text-sm text-zinc-400" x-text="index + 1"></span>

                        <x-select x-model="fila.campo" x-init="$nextTick(() => $el.value = fila.campo)">
                            <option value="">Seleccionar campo...</option>
                            <template x-for="opcion in camposOrdenables" :key="opcion.value">
                                <option :value="opcion.value" :disabled="estaUsadoEnOtraFila(opcion.value, index)"
                                    x-text="opcion.label"></option>
                            </template>
                        </x-select>

                        <x-select x-model="fila.direccion">
                            <option value="asc">Ascendente</option>
                            <option value="desc">Descendente</option>
                        </x-select>

                        <x-button type="button" variant="danger" x-on:click="quitarFila(index)"
                            x-show="filas.length > 1">
                            <i class="fa fa-times"></i>
                        </x-button>
                    </div>
                </template>

                <button type="button" x-on:click="agregarFila()" x-show="hayCamposDisponibles()"
                    class="text-sm text-indigo-600 hover:text-indigo-800">
                    + Agregar otro criterio de orden
                </button>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrandoModalOrden', false)" wire:loading.attr="disabled">
                Cancelar
            </x-button>
            <x-button x-on:click="guardar()" wire:loading.attr="disabled">
                Guardar orden
            </x-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>

@script
<script>
    Alpine.data('asistenciaMensual', () => ({
        busqueda: '',
        filtro: 'todos',
        empleados: @json($empleados),
        diasIndices: @json(collect($dias)->pluck('indice')),
        anchoTabla: 0,
        mostrarScrollFijo: false,
        camposOrdenables: @json($camposOrdenables),
        filas: @json(count($ordenGuardado) ? $ordenGuardado : [['campo' => '', 'direccion' => 'asc']]),

        init() {
            this.$nextTick(() => this.calcularAnchoTabla());
            window.addEventListener('resize', () => this.calcularAnchoTabla());

            Livewire.on('empleados-actualizados', () => {
                this.$nextTick(() => this.calcularAnchoTabla());
            });
        },
        // Devuelve las opciones que aún no han sido elegidas en OTRAS filas
        estaUsadoEnOtraFila(valorCampo, index) {
            return this.filas.some((f, i) => i !== index && f.campo === valorCampo);
        },

        hayCamposDisponibles() {
            const elegidos = this.filas.map(f => f.campo).filter(Boolean);
            return elegidos.length < this.camposOrdenables.length;
        },

        agregarFila() {
            if (this.hayCamposDisponibles()) {
                this.filas.push({ campo: '', direccion: 'asc' });
            }
        },

        quitarFila(index) {
            this.filas.splice(index, 1);
            if (this.filas.length === 0) {
                this.filas.push({ campo: '', direccion: 'asc' });
            }
        },

        guardar() {
            const ordenFinal = this.filas.filter(f => f.campo);
            $wire.guardarOrdenConfiguracion(ordenFinal);
        },
        calcularAnchoTabla() {
            const tabla = this.$refs.scrollTabla?.querySelector('table');
            if (!tabla) return;

            this.anchoTabla = tabla.scrollWidth;
            this.mostrarScrollFijo = tabla.scrollWidth > this.$refs.scrollTabla.clientWidth;
        },

        sincronizarDesdeTabla() {
            if (!this.$refs.scrollFijo) return;
            this.$refs.scrollFijo.scrollLeft = this.$refs.scrollTabla.scrollLeft;
        },

        sincronizarDesdeFijo() {
            if (!this.$refs.scrollTabla) return;
            this.$refs.scrollTabla.scrollLeft = this.$refs.scrollFijo.scrollLeft;
        },

        coincideBusqueda(empleado) {
            if (!this.busqueda) return true;
            return empleado.nombre_completo.toLowerCase().includes(this.busqueda.toLowerCase());
        },

        filtrados() {
            if (this.filtro === 'todos') return this.empleados;

            const campo = {
                permiso: 'tiene_permiso',
                descanso_medico: 'tiene_descanso_medico',
                vacaciones: 'tiene_vacaciones',
            }[this.filtro];

            return this.empleados.filter(e => e[campo]);
        },
    }));
</script>
@endscript