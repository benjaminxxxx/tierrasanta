<div>
    <x-loading wire:loading />
    <x-card x-data="tabla">
        <x-spacing>
            <form class="space-y-2" wire:submit.prevent="agregarTipoAsistencia">
                <x-label value="Agregar Nuevo Tipo de Asistencia" />
                <div class="lg:flex items-center">
                    <div>
                        <x-input autocomplete="off" wire:model="codigo" type="text" class="!w-auto mr-3"
                            placeholder="Código" autofocus id="codigo" />
                        <x-input-error for="codigo" />
                    </div>
                    <div>
                        <x-input autocomplete="off" wire:model="descripcion" type="text" class="!w-auto mr-3"
                            placeholder="Descripción" id="descripcion" />
                        <x-input-error for="descripcion" />
                    </div>
                    <div>
                        <x-input autocomplete="off" wire:model="horasJornal" type="text" class="!w-auto mr-3"
                            placeholder="Horas Jornal" id="horasJornal" />
                        <x-input-error for="horasJornal" />
                    </div>

                    <div>
                        <!-- Input de color (invisible) que se actualizará con el valor del selector -->
                        <div wire:ignore>
                            <x-input autocomplete="off" wire:model="color" type="text" id="color-input"
                                class="!w-auto mr-3 hidden" />

                            <!-- Div para el selector de color -->
                            <div x-ref="color_picker" class="mr-3"></div>

                            <!-- Mostrar color seleccionado -->
                            <div class="mt-2 mr-5">
                                <span>Color seleccionado:</span>
                                <div x-ref="color_preview"
                                    style="display:inline-block; width: 40px; height: 20px; background-color: {{ $color }}">
                                </div>
                            </div>
                        </div>


                    </div>

                    <x-button type="submit" class="mr-3" wire:loading.attr="disabled">
                        Agregar
                    </x-button>
                    @if ($tipoAsistenciaId)
                        <x-secondary-button type="button" wire:click="resetInputFields" wire:loading.attr="disabled">
                            Cancelar
                        </x-secondary-button>
                    @endif
                </div>
            </form>
        </x-spacing>
    </x-card>

    <x-card class="mt-5">
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th>Código</x-th>
                        <x-th>Descripción</x-th>
                        <x-th>Horas Jornal</x-th>
                        <x-th>Color</x-th>
                        <x-th>Acciones</x-th>
                    </x-tr>
                </x-slot>

                <x-slot name="tbody">
                    @foreach ($tipoAsistencias as $tipoAsistencia)
                        <x-tr>
                            <x-td>{{ $tipoAsistencia->codigo }}</x-td>
                            <x-td class="!text-left">{{ $tipoAsistencia->descripcion }}</x-td>
                            <x-td>{{ $tipoAsistencia->horas_jornal }}</x-td>
                            <x-td x-data="{ color: '{{ $tipoAsistencia->color }}' }">
                                {{ $tipoAsistencia->color }}
                                <div :style="{ backgroundColor: color }" style="display:inline-block; width: 40px; height: 20px;">
                                </div>
                            </x-td>
                            <x-td>
                                <x-button wire:click="editarTipoAsistencia({{ $tipoAsistencia->id }})"
                                    wire:loading.attr="disabled">
                                    <i class="fa fa-pencil"></i>
                                </x-button>
                                <x-danger-button wire:click="eliminarTipoAsistencia({{ $tipoAsistencia->id }})"
                                    wire:loading.attr="disabled">
                                    <i class="fa fa-remove"></i>
                                </x-danger-button>
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>
</div>
@script
    <script>
        Alpine.data('tabla', () => ({
            listeners: [],
            tableData: [],
            hot: null,
            init() {
                this.initTable();
                this.listeners.push(
                    Livewire.on('setColorEdit', (data) => {
                        console.log(data[0]);

                    })
                );
            },
            initTable() {
                const pickr = Pickr.create({
                    el: this.$refs.color_picker,
                    theme: 'classic', // or 'monolith', or 'nano'
                    default: '#42445a', // Color por defecto
                    components: {
                        preview: true,
                        opacity: true,
                        hue: true,
                        interaction: {
                            hex: true,
                            rgba: true,
                            input: true,
                            save: true // Muestra el botón de guardar
                        }
                    }
                });

                // Actualizar el campo input y Livewire cuando se elige un color
                pickr.on('save', (color, instance) => {
                    const colorValue = color.toHEXA().toString();

                    //document.getElementById('color-input').value = colorValue;
                    const data = {
                        colorValue: colorValue
                    };
                    $wire.dispatchSelf('updateColor', data);
                });

                // Actualizar el fondo de vista previa en tiempo real
                pickr.on('change', (color, instance) => {
                    const colorValue = color.toHEXA().toString();
                    this.$refs.color_preview.style.backgroundColor = colorValue;
                });
            },
            sendData() {

                //$wire.dispatchSelf('storeTableData', data);
            }
        }));
    </script>
@endscript
