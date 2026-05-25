<div class="space-y-4">
    <x-flex class="justify-between">
        <x-breadcrumb :items="$breadcrumb" />
        @can(\App\Constants\Permisos::SISTEMA_ROL_GESTIONAR)
            <x-button wire:click="$set('mostrarModalCrearRol', true)">
                <I class="fa fa-plus"></I> Nuevo Rol
            </x-button>
        @endcan
    </x-flex>

    <x-h3>Roles Existentes</x-h3>
    @can(\App\Constants\Permisos::SISTEMA_ROL_VER)
        <div>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th>
                            #
                        </x-th>
                        <x-th>
                            Rol
                        </x-th>
                        <x-th>
                            Cantidad de Permisos
                        </x-th>
                        <x-th>
                            Acciones
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @php
                        $indice = 0;
                    @endphp
                    @foreach ($roles as $rol)
                        @if ($rol->name != 'Super Admin')
                            @php
                                $indice++;
                            @endphp
                            <x-tr>
                                <x-td>
                                    {{ $indice }}
                                </x-td>
                                <x-td>
                                    {{ $rol->name }}
                                </x-td>
                                <x-td>
                                    {{ $rol->permissions->count() }}
                                </x-td>

                                @can(\App\Constants\Permisos::SISTEMA_ROL_GESTIONAR)
                                    <x-td class="text-center">
                                        <x-flex class="justify-center">
                                            <x-button href="{{ route('gestion-usuario.permisos-rol', ['rol' => $rol->name]) }}">
                                                <i class="fa fa-link"></I> Gestionar Permisos
                                            </x-button>
                                            <x-button variant="danger" wire:click="eliminarRol({{ $rol->id }})"
                                                wire:confirm="¿Está seguro que desea eliminar este rol?">
                                                <i class="fa fa-remove"></i>
                                            </x-button>
                                        </x-flex>
                                    </x-td>

                                @endcan
                            </x-tr>
                        @endif


                    @endforeach
                </x-slot>
            </x-table>

        </div>
    @else
        <p class="text-sm text-muted-foreground">Sin permisos para ver roles.</p>

    @endcan
    <!-- Modal Crear Rol -->
    <x-dialog-modal wire:model.live="mostrarModalCrearRol">
        <x-slot name="title">
            Crear Nuevo Rol
        </x-slot>

        <x-slot name="content">
            <x-input label="Nombre del Rol" wire:model="nombreRol" error="nombreRol" />
        </x-slot>

        <x-slot name="footer">
            <x-flex class="justify-end full-width">
                <x-button variant="secondary" wire:click="$set('mostrarModalCrearRol', false)">
                    Cancelar
                </x-button>

                <x-button wire:click="guardarRol">
                    <I class="fa fa-save"></I> {{ $modoEditarRol ? 'Actualizar Rol' : 'Guardar Rol' }}
                </x-button>
            </x-flex>

        </x-slot>
    </x-dialog-modal>
</div>