<div class="space-y-4">
    <x-flex class="justify-between">
        <x-title>
            Gestión de Proveedores
        </x-title>
        @can(\App\Constants\Permisos::INSUMO_PROVEEDOR_GESTIONAR)
            <x-button type="button" @click="$wire.dispatch('crearProveedor')" class="w-full md:w-auto">
                <i class="fa fa-plus"></i> Agregar Proveedor
            </x-button>
        @endcan
    </x-flex>

    @can(\App\Constants\Permisos::INSUMO_PROVEEDOR_VER)
        <x-card class="mt-3 space-y-4">

            <x-flex class="justify-between flex-wrap gap-3">
                <x-flex class="flex-wrap gap-3">
                    <x-group-field>
                        <x-label for="search">Buscar por nombre, razón social o N° documento</x-label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-primary dark:text-primarydark">
                                <i class="fa fa-search"></i>
                            </div>
                            <x-input type="search" wire:model.live="search" id="default-search" class="w-full !pl-10"
                                autocomplete="off" placeholder="Buscar proveedor..." />
                        </div>
                    </x-group-field>

                    <x-select wire:model.live="verificadoFiltro" label="Verificación SUNAT" class="w-auto">
                        <option value="">Todos</option>
                        <option value="1">Verificados</option>
                        <option value="0">No verificados</option>
                    </x-select>
                </x-flex>

                <div>
                    <x-toggle-switch :checked="$verEliminados" label="Ver eliminados" wire:model.live="verEliminados" />
                </div>
            </x-flex>

            <x-table class="mt-5">
                <x-slot name="thead">
                    <tr>
                        <x-th value="N°" class="text-center" />
                        <x-th value="Proveedor / Razón Social" />
                        <x-th value="Tipo / Doc." />
                        <x-th value="Documento" class="text-center" />
                        <x-th value="Verificado" class="text-center" />
                        <x-th value="Acciones" class="text-center" />
                    </tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($proveedores && $proveedores->count() > 0)
                        @foreach ($proveedores as $indice => $proveedor)
                            @php
                                $persona = $proveedor->persona;
                            @endphp
                            <x-tr>
                                <x-th value="{{ $proveedores->firstItem() + $indice }}" class="text-center" />
                                
                                {{-- Nombre de la Persona / Empresa --}}
                                <x-td value="{{ $persona?->nombre_mostrar ?? '-' }}" />

                                {{-- Tipo de Persona --}}
                                <x-td>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $persona?->tipo === 'empresa' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($persona?->tipo ?? 'N/A') }}
                                    </span>
                                </x-td>

                                {{-- Documento (RUC / DNI) --}}
                                <x-td value="{{ ($persona?->tipo_documento ? $persona->tipo_documento . ': ' : '') . ($persona?->numero_documento ?? '-') }}" class="text-center" />

                                {{-- Estado Verificado SUNAT --}}
                                <x-td class="text-center">
                                    @if ($proveedor->verificado)
                                        <span class="text-green-600 dark:text-green-400" title="Verificado"><i class="fa fa-check-circle"></i></span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </x-td>

                                {{-- Acciones --}}
                                <x-td class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <x-button variant="secondary" size="sm"
                                            @click="$wire.dispatch('verDetalleProveedor', { id: {{ $proveedor->id }} })">
                                            <i class="fa fa-eye"></i>
                                        </x-button>

                                        @can(\App\Constants\Permisos::INSUMO_PROVEEDOR_GESTIONAR)
                                            @if ($verEliminados)
                                                <x-button variant="success" size="sm" wire:click="restaurarProveedor({{ $proveedor->id }})">
                                                    <i class="fa fa-undo"></i>
                                                </x-button>
                                            @else
                                                <x-button variant="secondary" size="sm"
                                                    @click="$wire.dispatch('editarProveedor', { id: {{ $proveedor->id }} })">
                                                    <i class="fa fa-edit"></i>
                                                </x-button>
                                                <x-button variant="danger" size="sm" wire:click="confirmarEliminacion({{ $proveedor->id }})">
                                                    <i class="fa fa-trash"></i>
                                                </x-button>
                                            @endif
                                        @endcan
                                    </div>
                                </x-td>
                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="100%" class="text-center">
                                {{ $verEliminados ? 'No hay proveedores eliminados.' : 'No Hay Proveedores Registrados.' }}
                            </x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>
            <div class="mt-5">
                {{ $proveedores->links() }}
            </div>

        </x-card>
    @else
        <x-danger>
            No tiene permiso para visualizar esta sección.
        </x-danger>
    @endcan
    <livewire:gestion-proveedor.proveedor-detalle-component />
    <livewire:gestion-proveedor.proveedores-form-component />
</div>