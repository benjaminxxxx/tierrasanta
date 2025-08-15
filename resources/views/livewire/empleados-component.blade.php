<div x-data="empleados">

    <x-card2>
        <x-flex class="justify-between">
            <x-flex>
                <x-h3>
                    Administración de empleados
                </x-h3>
                <div class="mt-5 md:mt-0">
                    <livewire:empleado-form-component />
                    <livewire:asignacion-familiar-form-component />
                </div>

                <livewire:empleados-import-export-component wire:key="eleement" />
            </x-flex>
            <x-flex>
                <div class="relative mt-5 md:mt-0">
                    <x-dropdown align="right">
                        <x-slot name="trigger">
                            <span class="inline-flex rounded-md w-full lg:w-auto">
                                <x-button type="button" class="flex items-center justify-center">
                                    Opciones adicionales

                                    <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                    </svg>
                                </x-button>
                            </span>
                        </x-slot>

                        <x-slot name="content">
                            <div class="w-full">
                                <x-dropdown-link class="text-center" wire:click="abrirFormCambioMasivoSueldo">
                                    <i class="fa fa-money-bill"></i> Cambio de sueldo masivo
                                </x-dropdown-link>
                                <x-dropdown-link class="text-center" @click="alert('Función no disponible')">
                                    <i class="fa fa-money-bill"></i> Eliminar sueldos masivo
                                </x-dropdown-link>
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>
            </x-flex>
        </x-flex>
        <form class="md:flex my-10 gap-3">
            <x-group-field>
                <x-label for="cargo_id">Nombre o DNI</x-label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-primary">
                        <i class="fa fa-search"></i>
                    </div>
                    <x-input type="search" wire:model.live="search" id="default-search" class="w-full !pl-10"
                        autocomplete="off" placeholder="Busca por Nombres, Apellidos o Documento" required />
                </div>
            </x-group-field>

            <div>
                <x-label for="cargo_id">Cargo</x-label>
                <x-select class="uppercase" wire:model.live="cargo_id" id="cargo_id">
                    <option value="">TODOS</option>
                    @if ($cargos)
                        @foreach ($cargos as $cargo)
                            <option value="{{ $cargo->codigo }}">{{ $cargo->nombre }}</option>
                        @endforeach
                    @endif
                </x-select>
            </div>
            <div>
                <x-label for="descuento_sp_id">SPP o SNP</x-label>
                <x-select class="uppercase" wire:model.live="descuento_sp_id" id="descuento_sp_id">
                    <option value="">TODOS</option>
                    @if ($descuentos)
                        @foreach ($descuentos as $descuento)
                            <option value="{{ $descuento->codigo }}">{{ $descuento->descripcion }}</option>
                        @endforeach
                    @endif
                </x-select>
            </div>
            <div>
                <x-label for="grupo_codigo">Grupo</x-label>
                <x-select class="uppercase" wire:model.live="grupo_codigo" id="grupo_codigo">
                    <option value="">TODOS</option>
                    <option value="sg">SIN GRUPO</option>
                    @if ($grupos)
                        @foreach ($grupos as $grupo)
                            <option value="{{ $grupo->codigo }}">{{ $grupo->descripcion }}</option>
                        @endforeach
                    @endif
                </x-select>
            </div>
            <div>
                <x-label for="genero">Género</x-label>
                <x-select class="uppercase" wire:model.live="genero" id="genero">
                    <option value="">TODOS</option>
                    <option value="F">MUJERES</option>
                    <option value="M">HOMBRES</option>
                </x-select>
            </div>
            <div>
                <x-label for="estado">Estado</x-label>
                <x-select class="uppercase" wire:model.live="estado" id="estado">
                    <option value="">Todos + eliminados</option>
                    <option value="activo">Todos</option>
                    <option value="inactivo">Eliminados</option>
                </x-select>
            </div>
            <div>
                <x-label for="tipo_planilla">Tipo de planilla</x-label>
                <x-select class="uppercase" wire:model.live="tipo_planilla" id="tipo_planilla">
                    <option value="">TODOS</option>
                    <option value="1">Planilla Agraria</option>
                    <option value="2">Planilla Oficina</option>
                </x-select>
            </div>
        </form>
        <x-table class="mt-5">
            <x-slot name="thead">
                <tr>
                    <x-th value="INFORMACIÓN BÁSICA" colspan="5" class="text-center bg-gray-100 dark:bg-gray-700" />
                    <x-th value="INFORMACIÓN DE ÚLTIMO CONTRATO" colspan="8"
                        class="text-center bg-gray-200 dark:bg-sky-700" />
                    <x-th value="Acciones" rowspan="2" class="text-center" />
                </tr>
                <tr>
                    <x-th value="N°" class="text-center bg-gray-100 dark:bg-gray-700" />
                    <x-th value="Documento" class="text-center bg-gray-100 dark:bg-gray-700" />
                    <x-th value="Nombre Completo" class=" bg-gray-100 dark:bg-gray-700" />
                    <x-th value="Orden" class="text-center bg-gray-100 dark:bg-gray-700" />
                    <x-th value="Asignación Familiar" class="text-center bg-gray-100 dark:bg-gray-700" />

                    <x-th value="F. vigencia." class="text-center bg-gray-200 dark:bg-sky-700" />
                    <x-th value="Sueldo" class="text-center bg-gray-200 dark:bg-sky-700" />
                    <x-th value="Grupo" class="text-center bg-gray-200 dark:bg-sky-700" />
                    <x-th value="Comp. vacacional" class="text-center bg-gray-200 dark:bg-sky-700" />
                    <x-th value="SNP/SPP" class="text-center bg-gray-200 dark:bg-sky-700" />
                    <x-th value="Cargo" class="text-center bg-gray-200 dark:bg-sky-700" />
                    <x-th value="Mod. pago" class="text-center bg-gray-200 dark:bg-sky-700" />
                    <x-th value="Tpo Planilla" class="text-center bg-gray-200 dark:bg-sky-700" />

                </tr>
            </x-slot>
            <x-slot name="tbody">
                @if ($empleados->count())
                    @foreach ($empleados as $indice => $empleado)
                        <x-tr style="background-color:{{ $empleado->grupo ? $empleado->grupo->color : '#ffffff' }}">
                            <x-th value="{{ $indice + 1 }}" class="dark:text-gray-800" />
                            <x-td value="{{ $empleado->documento }}" class="dark:text-gray-800" />
                            <x-td class="dark:text-gray-800">
                                <p>
                                    {{ $empleado->nombreCompleto }}
                                </p>
                                <p>
                                    F. Nac. {{ formatear_fecha($empleado->fecha_nacimiento) }} - F. Ingr. {{ formatear_fecha($empleado->fecha_ingreso) }}
                                </p>
                            </x-td>

                            <x-td>
                                @if($empleado->status == 'activo')
                                    <div class="flex items-center gap-2">
                                        <x-success-button wire:click="moveUp({{ $empleado->id }})" class="">
                                            <i class="fa fa-arrow-up"></i>
                                        </x-success-button>
                                        <x-input class="!w-12 !p-2 !mt-0 text-center" value="{{ $empleado->orden }}"
                                            wire:keyup.debounce.500ms="moveAt({{ $empleado->id }}, $event.target.value)" />
                                        <x-button wire:click="moveDown({{ $empleado->id }})" class="">
                                            <i class="fa fa-arrow-down"></i>
                                        </x-button>
                                    </div>
                                @endif
                            </x-td>
                            <x-td>
                                @if($empleado->status == 'activo')
                                    <x-secondary-button wire:click="asignacionFamiliar('{{ $empleado->code }}')">
                                        {{ $empleado->tieneAsignacionFamiliar['mensaje'] }}
                                    </x-secondary-button>
                                @endif
                            </x-td>
                            {{-- Informacion da ultimo contrato --}}
                            <x-td value="{{ formatear_fecha($empleado->ultimoContrato?->fecha_inicio) }}" class="dark:text-gray-800" />
                            <x-td value="{{ formatear_numero($empleado->ultimoContrato?->sueldo) }}" class="dark:text-gray-800" />
                            <x-td value="{{ $empleado->ultimoContrato?->grupo_codigo }}" class="dark:text-gray-800" />
                            <x-td value="{{ $empleado->ultimoContrato?->compensacion_vacacional }}"
                                class="dark:text-gray-800" />
                            <x-td value="{{ $empleado->ultimoContrato?->descuento_sp_id }}" class="dark:text-gray-800" />
                            <x-td value="{{ isset($empleado->cargo) ? $empleado->cargo->nombre : '-' }}"
                                class="dark:text-gray-800" />
                            <x-td value="{{ $empleado->ultimoContrato?->modalidad_pago }}" class="dark:text-gray-800" />
                            <x-td value="{{ $empleado->tipo_planilla_descripcion }}" class="dark:text-gray-800" />

                            <x-td>
                                <div class="flex items-center justify-center gap-2">

                                    @if ($empleado->status == 'activo')
                                        <x-button wire:click="editar('{{ $empleado->code }}')">
                                            <i class="fa fa-pencil"></i>
                                        </x-button>
                                        <x-danger-button wire:confirm="¿Está seguro que desea eliminar el empleado?"
                                            wire:click="confirmarEliminacion('{{ $empleado->code }}')">
                                            <i class="fa fa-remove"></i>
                                        </x-danger-button>
                                    @else
                                        <x-button wire:click="restaurar('{{ $empleado->code }}')">
                                            <i class="fa fa-arrow-left"></i> Restaurar
                                        </x-button>
                                    @endif

                                </div>

                            </x-td>
                        </x-tr>
                    @endforeach
                @else
                    <x-tr>
                        <x-td colspan="4">No hay Empleados registrados.</x-td>
                    </x-tr>
                @endif
            </x-slot>
        </x-table>
        <div class="mt-5">
            {{ $empleados->links() }}
        </div>
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