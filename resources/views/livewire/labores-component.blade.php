<div>
    <x-flex>
        <x-h3>
            Labores para planilla y cuadrilla
        </x-h3>
        <x-button wire:click="crearNuevaLabor">
            <i class="fa fa-plus"></i> Crear nueva labor
        </x-button>
    </x-flex>
    <x-card class="mt-3">
        <x-spacing>

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
                    <x-group-field>
                        <x-label for="verActivos">Estado</x-label>
                        <x-select wire:model.live="verActivos">
                            <option value="">Todos</option>
                            <option value="0">Inactivos</option>
                            <option value="1">Activos</option>
                        </x-select>
                    </x-group-field>
                </x-flex>
            </x-flex>
            <x-table class="mt-5">
                <x-slot name="thead">
                    <x-tr>
                        <x-th value="Código" class="text-center" />
                        <x-th value="Nombre de la Labor" />
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
                                <x-td valign="top" value="{{ $labor->estandar_produccion . ' ' . $labor->unidades}}" class="text-center" />
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
                                                    Hasta <span class="font-semibold">{{ $tramo['hasta'] }}</span> unidades &rarr;
                                                    <span class="font-semibold">S/. {{ $tramo['monto'] }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-gray-400 italic">Sin tramos</span>
                                    @endif
                                </x-td>
                                <x-td valign="top" class="text-center">
                                    <x-flex class="justify-end">
                                         @if ($labor->estado != 1)
                                            <x-warning-button wire:click="habilitar({{ $labor->id }},true)">
                                                <i class="fa fa-ban"></i>
                                            </x-warning-button>
                                        @else
                                            <x-success-button wire:click="habilitar({{ $labor->id }},false)">
                                                <i class="fa fa-check"></i>
                                            </x-success-button>
                                        @endif
                                        <x-button wire:click="editarLabor({{ $labor->id }})">
                                            <i class="fa fa-edit"></i>
                                        </x-button>
                                        <x-danger-button wire:click="confirmarEliminarLabor({{ $labor->id }})">
                                            <i class="fa fa-trash"></i>
                                        </x-danger-button>
                                    </x-flex>
                                </x-td>
                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="4">No hay Labores registrados.</x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>
            <div class="my-5">
                {{ $labores->links() }}
            </div>
        </x-spacing>
    </x-card>
    <x-modal maxWidth="full" wire:model="mostrarFormularioLabor">
        <form wire:submit.prevent="guardarLabor">
            <div class="px-6 py-4">
                <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    <x-h3>
                        Agregar Nueva Labor
                    </x-h3>
                </div>

                <x-flex class="mt-3 mb-2">
                    <x-h3>Información Básica</x-h3>
                </x-flex>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <x-input-number wire:model="codigo" label="Código de labor" />
                    <x-input-string wire:model="nombre_labor" label="Nombre de la labor" />
                    <x-input-number wire:model="estandar_produccion" label="Estándar de producción" />
                    <x-input-string wire:model="unidades" label="Unidades" placeholder="Ejem: Kg, Lavaderos" />
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
                        <x-secondary-button @click="addTramo">
                            <i class="fa fa-plus"></i> Agregar Tramo
                        </x-secondary-button>
                    </x-flex>

                    <template x-for="(tramo, index) in tramos" :key="index">
                        <div class="flex items-center space-x-4 p-2">
                            <!-- Hasta -->
                            <div class="flex flex-col">
                                <x-input-number label="Hasta (unidades)" x-model="tramo.hasta" />
                            </div>

                            <!-- Monto -->
                            <div class="flex flex-col">
                                <x-input-number step="0.1" label="Se paga S/." x-model="tramo.monto" />
                            </div>

                            <!-- Remove button -->
                            <x-danger-button @click="removeTramo(index)">
                                <i class="fa fa-trash"></i>
                            </x-danger-button>
                        </div>
                    </template>
                </div>

            </div>

            <div class="flex flex-row justify-end px-6 py-4 bg-whiten dark:bg-boxdarkbase text-end gap-4">
                <x-secondary-button @click="$wire.set('mostrarFormularioLabor', false)">
                    Cancelar
                </x-secondary-button>
                <x-button type="submit">
                    <i class="fa fa-save"></i> Guardar Labor
                </x-button>
            </div>
        </form>
    </x-modal>
    <x-loading wire:loading />
</div>