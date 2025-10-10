<div x-data="empleados">

    <x-card2>
        <x-flex class="justify-between">
            <x-flex>
                
                <div class="mt-5 md:mt-0">
                    <livewire:empleado-form-component />
                    <livewire:asignacion-familiar-form-component />
                </div>

                <livewire:empleados-import-export-component wire:key="eleement" />
            </x-flex>
            <x-flex>
                <div class="relative mt-5 md:mt-0">
                    
                </div>
            </x-flex>
        </x-flex>
        
        
        
    </x-card2>
    <!-- Modal -->
    <x-dialog-modal wire:model.live="mostrarFormularioCambioSueldos" maxWidth="full">
        <x-slot name="title">
            Cambio de sueldos de trabajadores de planilla
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">

                <!-- Filtros -->
                <x-label>
                    Filtrar por:
                </x-label>
                <div class="lg:flex gap-2">
                    <x-input type="text" placeholder="Buscar nombre" x-model="filtros.nombre" />
                    <x-select x-model="filtros.grupo">
                        <option value="">Todos los grupos</option>
                        <template x-for="g in grupos" :key="g">
                            <option x-text="g"></option>
                        </template>
                    </x-select>
                    <x-select x-model="filtros.cargo">
                        <option value="">Todos los cargos</option>
                        <template x-for="c in cargos" :key="c">
                            <option x-text="c"></option>
                        </template>
                    </x-select>
                    <x-select x-model="filtros.tipo_planilla">
                        <option value="">Todas las planillas</option>
                        <option value="1">Planilla Agraria</option>
                        <option value="2">Planilla Oficina</option>
                    </x-select>
                </div>

                <!-- Acciones masivas -->
                <x-flex class="justify-between">
                    <div class="flex items-center gap-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" x-model="seleccionarTodos" @change="toggleSeleccionarTodos">
                            <span>Seleccionar todos</span>
                        </label>

                        <div class="flex items-center gap-2">
                            <x-input type="number" class="input w-40" placeholder="Nuevo sueldo masivo"
                                x-model.number="sueldoMasivo" @input="aplicarSueldoMasivo" />
                        </div>
                    </div>
                    <div>
                        <x-label>
                            Vigencia desde:
                        </x-label>
                        <x-flex>
                            @php
                                $anioActual = date('Y');
                            @endphp
                            <x-select label="Año" wire:model="anioVigencia">
                                <option value="{{ $anioActual }}">{{ $anioActual }}</option>
                                <option value="{{ $anioActual + 1 }}">{{ $anioActual + 1 }}</option>
                            </x-select>
                            <x-select label="Mes" wire:model="mesVigencia">
                                <option value="">Seleccione</option>
                                <option value="01">Enero</option>
                                <option value="02">Febrero</option>
                                <option value="03">Marzo</option>
                                <option value="04">Abril</option>
                                <option value="05">Mayo</option>
                                <option value="06">Junio</option>
                                <option value="07">Julio</option>
                                <option value="08">Agosto</option>
                                <option value="09">Septiembre</option>
                                <option value="10">Octubre</option>
                                <option value="11">Noviembre</option>
                                <option value="12">Diciembre</option>
                            </x-select>

                        </x-flex>
                    </div>

                </x-flex>


                <!-- Tabla -->
                <div class="overflow-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <x-tr>
                                <x-th>#</x-th>
                                <x-th>Empleado</x-th>
                                <x-th>Grupo</x-th>
                                <x-th>Cargo</x-th>
                                <x-th>Tipo planilla</x-th>
                                <x-th>Sueldo actual</x-th>
                                <x-th class="text-center">Nuevo sueldo</x-th>
                                <x-th class="text-center">Sel</x-th>
                            </x-tr>
                        </thead>
                        <tbody>
                            <template x-for="(t, i) in filtrados" :key="t.id">
                                <x-tr class="border-t">
                                    <x-td class="p-1" x-text="i + 1"></x-td>
                                    <x-td  class="p-1" x-text="t.nombre"></x-td>
                                    <x-td  class="p-1" x-text="t.grupo_codigo || '-'"></x-td>
                                    <x-td  class="p-1" x-text="t.cargo_codigo || '-'"></x-td>
                                    <x-td class="p-1"
                                        x-text="t.tipo_planilla === '1' ? 'Planilla Agraria' : (t.tipo_planilla === '2' ? 'Planilla Oficina' : '-')">
                                    </x-td>
                                    <x-td class="p-1" x-text="Number(t.sueldo_actual).toFixed(2)"></x-td>
                                    <x-td class="p-1 text-center">
                                        <x-input type="number" class="text-center" x-model.number="t.nuevo_sueldo" />
                                    </x-td>
                                    <x-td class="p-1 text-center">
                                        <input type="checkbox" x-model="t.seleccionado">
                                    </x-td>
                                </x-tr>
                            </template>
                        </tbody>
                    </table>
                </div>

            </div>
        </x-slot>

        <x-slot name="footer">
            <x-flex class="justify-end">
                <x-secondary-button wire:click="$set('mostrarFormularioCambioSueldos', false)">
                    Cerrar
                </x-secondary-button>
                <x-button @click="guardarCambios">
                    <i class="fa fa-save"></i> Guardar cambios
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('empleados', () => ({
        lista: [],
        filtros: { nombre: '', grupo: '', cargo: '', tipo_planilla: '' },
        seleccionarTodos: false,
        sueldoMasivo: null,

        init() {
            // recibe data cuando Livewire abre el modal
            Livewire.on('ejecutarCambioSueldos', (payload) => {
                const data = Array.isArray(payload) ? payload : (payload?.trabajadores ?? []);
                this.cargar(data);
            });

            // opcional: si cierras el modal, limpias
            Livewire.hook('element.removed', ({ el, component }) => {
                // noop, o podrías resetear this.lista = []
            });
        },

        cargar(trabajadores) {
            this.lista = trabajadores.map(t => ({
                ...t,
                nuevo_sueldo: t.nuevo_sueldo ?? t.sueldo_actual ?? 0,
                seleccionado: !!t.seleccionado,
            }));
            this.seleccionarTodos = false;
            this.sueldoMasivo = null;
        },

        get grupos() {
            return [...new Set(this.lista.map(t => t.grupo_codigo).filter(Boolean))];
        },
        get cargos() {
            return [...new Set(this.lista.map(t => t.cargo_codigo).filter(Boolean))];
        },
        get filtrados() {
            const n = this.filtros.nombre.toLowerCase();
            return this.lista.filter(t =>
                (!n || (t.nombre || '').toLowerCase().includes(n)) &&
                (!this.filtros.grupo || t.grupo_codigo === this.filtros.grupo) &&
                (!this.filtros.cargo || t.cargo_codigo === this.filtros.cargo) &&
                (!this.filtros.tipo_planilla || t.tipo_planilla === this.filtros.tipo_planilla)
            );
        },

        toggleSeleccionarTodos() {
            this.filtrados.forEach(t => t.seleccionado = this.seleccionarTodos);
        },

        aplicarSueldoMasivo() {
            const val = Number(this.sueldoMasivo);
            if (isNaN(val)) return;
            this.filtrados.forEach(t => {
                if (t.seleccionado) t.nuevo_sueldo = val;
            });
        },

        guardarCambios() {

            const cambios = this.lista
                .filter(t => t.seleccionado && Number(t.nuevo_sueldo) !== Number(t.sueldo_actual))
                .map(t => ({
                    empleado_id: t.id,
                    nuevo_sueldo: Number(t.nuevo_sueldo)
                }));
            console.log(this.lista);
            $wire.guardarCambiosSueldos(cambios);
        },
    }));
</script>
@endscript