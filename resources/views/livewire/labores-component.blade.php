<div>
    <x-flex>
        <x-h3>
            Labores para planilla y cuadrilla
        </x-h3>
        <x-button wire:click="crearNuevaLabor">
            <i class="fa fa-plus"></i> Crear nueva labor
        </x-button>
        <div x-data="{ openFileDialog() { $refs.fileLabores.click() } }">
            <x-button variant="success" type="button" @click="openFileDialog()">
                <i class="fa fa-file-excel"></i> Importar desde Excel
            </x-button>
            <input type="file" accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                x-ref="fileLabores" style="display: none;" wire:model.live="fileLabores" />
        </div>
    </x-flex>
    <x-card class="mt-3">

        <x-flex class="justify-between">
            <x-flex>
                <x-group-field>
                    <x-label for="search">Buscar por código o descripción</x-label>
                    <div class="relative">
                        <div
                            class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-primary dark:text-primarydark">
                            <i class="fa fa-search"></i>
                        </div>
                        <x-input type="search" wire:model.live="search" id="default-search" class="w-full !pl-10"
                            autocomplete="off" placeholder="Busca por Nombre de la labor aqui." required />
                    </div>
                </x-group-field>
                <x-select wire:model.live="manoObraFiltro" label="Mano de obra" class="w-auto">
                    <option value="">Todos</option>
                    @foreach ($manoObras as $manoObra)
                        <option value="{{ $manoObra->codigo }}">{{ $manoObra->descripcion }}</option>
                    @endforeach
                </x-select>

            </x-flex>
            <div>
                <x-toggle-switch :checked="$verEliminados" label="Ver eliminados" wire:model.live="verEliminados" />
            </div>
        </x-flex>
        <x-table class="mt-5">
            <x-slot name="thead">
                <x-tr>
                    <x-th value="Código" class="text-center" />
                    <x-th value="Nombre de la Labor" />
                    <x-th value="Mano de obra" />
                    <x-th value="Estándar de producción" class="text-center" />
                    <x-th value="Tramos de bonificación" class="text-center" />
                    <x-th value="Acciones" class="text-center" />
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                @if ($labores && $labores->count() > 0)
                    @foreach ($labores as $indice => $labor)
                        <x-tr>
                            <x-th valign="top" value="{{ $labor->codigo }}" class="text-center" />
                            <x-td valign="top" value="{{ $labor->nombre_labor }}" />
                            <x-td valign="top" value="{{ $labor->manoObra?->descripcion }}" />
                            <x-td valign="top" value="{{ $labor->estandar_produccion . ' ' . $labor->unidades }}"
                                class="text-center" />
                            <x-td valign="top" class="text-center">
                                {{-- Lista de tramos --}}
                                @php
                                    $tramos = is_string($labor->tramos_bonificacion)
                                        ? json_decode($labor->tramos_bonificacion, true)
                                        : $labor->tramos_bonificacion;
                                @endphp

                                @if (!empty($tramos) && is_array($tramos))
                                    <ul class="text-sm text-left space-y-1">
                                        @foreach ($tramos as $tramo)
                                            <li>
                                                Hasta <span class="font-semibold">{{ $tramo['hasta'] }}</span> unidades
                                                &rarr;
                                                <span class="font-semibold">S/. {{ $tramo['monto'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-gray-400 italic">Sin tramos</span>
                                @endif
                            </x-td>
                            <x-td valign="top" class="text-center">
                                <x-flex class="justify-center">
                                    @if ($labor->trashed())
                                        <x-button class="secondary" wire:click="restaurarLabor({{ $labor->id }})">
                                            <i class="fa fa-undo"></i> Restaurar
                                        </x-button>
                                    @else
                                        <x-button wire:click="editarLabor({{ $labor->id }})">
                                            <i class="fa fa-edit"></i>
                                        </x-button>
                                        <x-button variant="danger"
                                            wire:click="confirmarEliminarLabor({{ $labor->id }})">
                                            <i class="fa fa-trash"></i>
                                        </x-button>
                                    @endif

                                </x-flex>
                            </x-td>
                        </x-tr>
                    @endforeach
                @else
                    <x-tr>
                        <x-td colspan="100%">No hay Labores registrados.</x-td>
                    </x-tr>
                @endif
            </x-slot>
        </x-table>
        <div class="my-5">
            {{ $labores->links() }}
        </div>
    </x-card>
    <x-dialog-modal maxWidth="lg" wire:model="mostrarFormularioLabor">
        <x-slot name="title">
            Registro de labores
        </x-slot>

        <x-slot name="content">
            <form wire:submit.prevent="guardarLabor" id="frmLabores">
                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <x-input type="number" wire:model="codigo" label="Código de labor" error="codigo" />
                        <x-input wire:model="nombre_labor" label="Nombre de la labor" error="nombre_labor" />
                        <x-input type="number" wire:model="estandar_produccion" label="Estándar de producción"
                            error="estandar_produccion" />
                        <x-input wire:model="unidades" label="Unidades" placeholder="Ejem: Kg, Lavaderos"
                            error="unidades" />
                        <x-select wire:model="codigo_mano_obra" label="Mano de obra" error="codigo_mano_obra">
                            <option value="">Seleccione un grupo</option>
                            @foreach ($manoObras as $manoObra)
                                <option value="{{ $manoObra->codigo }}">{{ $manoObra->descripcion }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    <div x-data="{
                        tramos: @entangle('tramos'),
                        addTramo() {
                            this.tramos.push({ hasta: '', monto: '' });
                        },
                        removeTramo(index) {
                            this.tramos.splice(index, 1);
                        }
                    }" class="space-y-4">
                        <x-flex class="mt-3 mb-2">
                            <x-h3>Tramos de Bonificación</x-h3>
                            <x-button variant="secondary" @click="addTramo">
                                <i class="fa fa-plus"></i> Agregar Tramo
                            </x-button>
                        </x-flex>

                        <template x-for="(tramo, index) in tramos" :key="index">
                            <div class="flex items-center space-x-4 p-2">
                                <!-- Hasta -->
                                <div class="flex flex-col">
                                    <x-input type="number" label="Hasta (unidades)" x-model="tramo.hasta" />
                                </div>

                                <!-- Monto -->
                                <div class="flex flex-col">
                                    <x-input type="number" step="0.1" label="Se paga S/."
                                        x-model="tramo.monto" />
                                </div>

                                <!-- Remove button -->
                                <x-button variant="danger" @click="removeTramo(index)">
                                    <i class="fa fa-trash"></i>
                                </x-button>
                            </div>
                        </template>
                    </div>
                </div>

            </form>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" @click="$wire.set('mostrarFormularioLabor', false)">
                Cancelar
            </x-button>
            <x-button type="submit" form="frmLabores">
                <i class="fa fa-save"></i> Guardar Labor
            </x-button>
        </x-slot>

    </x-dialog-modal>
    <x-loading wire:loading />
</div>
