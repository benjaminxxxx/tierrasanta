<div>

    <x-dialog-modal wire:model.live="mostrarFormularioGrupoCuadrilla">
        <x-slot name="title">
            <x-h3>
                Registrar Grupo de Cuadrilla
            </x-h3>
        </x-slot>

        <x-slot name="content">
            <form class="grid grid-cols-1 md:grid-cols-2 gap-4" x-data="form_cuadrilla_grupo" id="form_cuadrilla_grupo"
                wire:submit="registrar">
                <!-- Nombre -->
                <x-input id="nombre" type="text" class="w-full uppercase" label="Nombre del Grupo (*)" wire:model="nombre"
                    error="nombre" />

                <x-input id="codigo" type="text" class="w-full uppercase" label="Código del Grupo (*)" wire:model="codigo"
                    error="codigo" />

                <x-input id="costo_dia_sugerido" type="number" label="Costo Día Sugerido (*)" step="0.01"
                    class="w-full" error="costo_dia_sugerido" wire:model="costo_dia_sugerido" />

                <!-- Color -->
                <div>
                    <x-label for="color" value="Color del Grupo" />
                    <div class="flex justify-center">
                        <div x-on:keydown.escape.prevent.stop="close($refs.button)"
                            x-on:focusin.window="! $refs.panel.contains($event.target) && close()"
                            x-id="['dropdown-button']" class="relative w-full">
                            <!-- Botón -->
                            <button x-ref="button" x-on:click="toggle()" :aria-expanded="open"
                                :aria-controls="$id('dropdown-button')" type="button"
                                class="relative flex items-center whitespace-nowrap justify-center gap-2 py-2 rounded-lg shadow-sm bg-primary dark:bg-primaryDark border border-primary dark:border-meta-4 px-4 w-full">
                                <div class="flex items-center gap-2">
                                    <!-- Cuadro de color seleccionado -->
                                    <div class="w-5 h-5 rounded border border-white"
                                        :style="`background-color: ${selected.hex}`"></div>
                                    <span x-text="selected.nombre || 'Seleccionar color'"></span>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"
                                    class="size-4">
                                    <path fill-rule="evenodd"
                                        d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>

                            <!-- Panel -->
                            <div x-ref="panel" x-show="open" x-transition.origin.top.left
                                x-on:click.outside="close($refs.button)" :id="$id('dropdown-button')" x-cloak
                                class="absolute left-0 min-w-48 rounded-lg shadow-sm mt-2 z-10 origin-top-left bg-white p-1.5 outline-none border border-gray-200 dark:bg-primaryDark">
                                <template x-for="color in colores" :key="color.hex">
                                    <button type="button"
                                        class="w-full flex items-center px-2 py-2 gap-2 rounded hover:bg-gray-50"
                                        @click="selectColor(color)">
                                        <div :style="'background-color:' + color.hex" class="w-5 h-5 rounded border">
                                        </div>
                                        <span x-text="color.nombre"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                </div>
                <x-select id="modalidad_pago" wire:model="modalidad_pago" fullWidth="true" label="Modalidad de Pago"
                    error="modalidad_pago" class="w-full">
                    <option value="semanal">Semanal</option>
                    <option value="quincenal">Quincenal</option>
                    <option value="mensual">Mensual</option>
                </x-select>
            </form>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-2">
                <x-button variant="secondary" wire:click="$set('mostrarFormularioGrupoCuadrilla', false)"
                    wire:loading.attr="disabled">
                    Cerrar
                </x-button>
                <x-button type="submit" form="form_cuadrilla_grupo" wire:loading.attr="disabled">
                    <i class="fa fa-save"></i>
                    @if (!$grupoId)
                        Registrar
                    @else
                        Actualizar
                    @endif
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('form_cuadrilla_grupo', () => ({
        open: false,
        colorSeleccionado: @entangle('color'),
        mostrarFormularioGrupoCuadrilla: @entangle('mostrarFormularioGrupoCuadrilla'),
        selected: {
            hex: '',
            nombre: ''
        },
        colores: [
            { hex: '#FF6467', nombre: 'Rojo Coral' },
            { hex: '#FF8904', nombre: 'Naranja Intenso' },
            { hex: '#FFB900', nombre: 'Amarillo Mostaza' },
            { hex: '#9AE600', nombre: 'Verde Lima' },
            { hex: '#05DF72', nombre: 'Verde Esmeralda' },
            { hex: '#51A2FF', nombre: 'Azul Claro' },
            { hex: '#7C86FF', nombre: 'Azul Lavanda' },
            { hex: '#A684FF', nombre: 'Violeta Suave' },
            { hex: '#ED6BFF', nombre: 'Fucsia' },
            { hex: '#FF637E', nombre: 'Rosa Brillante' },
            { hex: '#90A1B9', nombre: 'Gris Azulado' },
            { hex: '#A6A09B', nombre: 'Gris Cálido' }
        ],
        init() {
            this.$watch('mostrarFormularioGrupoCuadrilla', () => this.resetearColor());
        },
        resetearColor() {
            this.selected = {
                hex: '',
                nombre: ''
            }
        },
        toggle() {
            this.open = !this.open
        },
        close(focusAfter) {
            this.open = false
            focusAfter && focusAfter.focus()
        },
        selectColor(color) {
            this.colorSeleccionado = color.hex;
            this.selected = color;
            this.close();
        }
    }));
</script>
@endscript