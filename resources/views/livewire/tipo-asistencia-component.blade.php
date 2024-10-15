<div>
    <x-loading wire:loading wire:target="agregarTipoAsistencia" />
    <x-h3 class="my-5">
        Tipo de Asistencias
    </x-h3>
    @if ($tipoAsistenciaId)
        <x-card x-data="tabla">
            <x-spacing>
                <form class="space-y-2" wire:submit.prevent="agregarTipoAsistencia">

                    <div class="2xl:flex items-center">
                        <div>
                            <input wire:model="codigo" type="hidden" />
                        </div>
                        <div class="mb-3">
                            <x-label for="descripcion">Descripción</x-label>
                            <x-input autocomplete="off" wire:model="descripcion" type="text"
                                class="md:!w-auto md:mr-3" id="descripcion" />
                            <x-input-error for="descripcion" />
                        </div>
                        <div class="mb-3">
                            <x-label for="horasJornal">Horas Jornal</x-label>
                            <x-input autocomplete="off" wire:model="horasJornal" type="text"
                                class="md:!w-auto md:mr-3" id="horasJornal" />
                            <x-input-error for="horasJornal" />
                        </div>
                        <div class="mb-3">
                            <x-label for="color">Código de color</x-label>
                            <x-input autocomplete="off" wire:model="color" type="text" id="color-input"
                                class="md:!w-auto md:mr-3" />
                            <x-input-error for="color" />
                        </div>
                        <div class="mb-3">
                            <div wire:ignore>

                                <div x-ref="color_picker" id="color_picker" class="mr-3"></div>

                            </div>
                            <div class="mt-2 mr-5">
                                <span>Color seleccionado:</span>
                                <div x-ref="color_preview"
                                    style="display:inline-block; width: 40px; border:1px solid #000; height: 20px; background-color: {{ $color }}">
                                </div>
                            </div>

                        </div>
                        <div class="flex justify-end md:block">
                            <x-button type="submit" class="mr-3" wire:loading.attr="disabled">
                                Guardar
                            </x-button>
                            <x-secondary-button type="button" wire:click="resetInputFields" wire:loading.attr="disabled">
                                Cancelar
                            </x-secondary-button>
                        </div>

                    </div>
                </form>
            </x-spacing>
        </x-card>
    @endif
    <x-card class="mt-5">
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th>Código</x-th>
                        <x-th>Descripción</x-th>
                        <x-th class="text-center">Horas Jornal</x-th>
                        <x-th class="text-center">Color</x-th>
                        <x-th class="text-center">Acciones</x-th>
                    </x-tr>
                </x-slot>

                <x-slot name="tbody">
                    @foreach ($tipoAsistencias as $tipoAsistencia)
                        <x-tr>
                            <x-th>{{ $tipoAsistencia->codigo }}</x-th>
                            <x-td class="!text-left">{{ $tipoAsistencia->descripcion }}</x-td>
                            <x-td class="text-center font-bold text-lg">
                                @if ($tipoAsistencia->horas_jornal == 0)
                                    <span class="text-red-600">
                                        {{ $tipoAsistencia->horas_jornal }}
                                    </span>
                                @else
                                    <span class="text-green-500">
                                        {{ $tipoAsistencia->horas_jornal }}
                                    </span>
                                @endif
                            </x-td>
                            <x-td class="text-center" x-data="{ color: '{{ $tipoAsistencia->color }}' }">
                                {{ $tipoAsistencia->color }}
                                <div :style="{ backgroundColor: color }"
                                    style="display:inline-block; width: 40px; height: 20px;border:1px solid #000">
                                </div>
                            </x-td>
                            <x-td class="text-center">
                                <x-button wire:click="editarTipoAsistencia({{ $tipoAsistencia->id }})"
                                    wire:loading.attr="disabled">
                                    <i class="fa fa-pencil"></i>
                                </x-button>
                                <!--<x-danger-button wire:click="eliminarTipoAsistencia({{ $tipoAsistencia->id }})"
                                    wire:loading.attr="disabled">
                                    <i class="fa fa-remove"></i>
                                </x-danger-button>-->
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
            <div class="flex justify-end mt-5">
                <x-button wire:click="preguntarRestaurar">
                    Restaurar Valores por Defecto
                </x-button>
            </div>
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
                        setTimeout(() => {
                            $wire.dispatchSelf('rerender');
                        }, 500);
                    })
                );
            },
            initTable() {
                const pickr = Pickr.create({
                    el: "#color_picker",
                    theme: 'classic', // or 'monolith', or 'nano'
                    default: '#42445a', // Color por defecto
                    components: {
                        preview: true,
                        opacity: true,
                        hue: true,
                        interaction: {
                            hex: true,
                            rgba: true,
                            input: true
                        }
                    }
                });

                // Actualizar el campo input y Livewire cuando se elige un color
                pickr.on('change', (color, instance) => {
                    const colorValue = color.toHEXA().toString();


                    const data = {
                        colorValue: colorValue
                    };
                    $wire.dispatchSelf('updateColor', data);
                });

                pickr.on('changestop', (color, instance) => {
                    pickr.hide(); // Oculta el selector de color
                });
            }
        }));
    </script>
@endscript
