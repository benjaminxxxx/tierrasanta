<div>
    <x-card>
        <x-flex>
            <x-title>
                Usuarios
            </x-title>
            <x-button type="button" @click="$wire.dispatch('CrearUsuario')" class="w-full md:w-auto ">
                <i class="fa fa-user-plus"></i> Nuevo usuario
            </x-button>
        </x-flex>
        <x-table class="mt-5">
            <x-slot name="thead">
                <tr>
                    <x-th value="N°" />
                    <x-th value="Nombre" />
                    <x-th value="Email" />
                    <x-th value="Roles" class="text-center" />
                    <x-th value="Acciones" class="!text-center" />
                </tr>
            </x-slot>
            <x-slot name="tbody">
                @if ($usuarios->count())
                    @foreach ($usuarios as $indice => $usuario)
                        <x-tr>
                            <x-th value="{{ $indice + 1 }}" />
                            <x-td value="{{ $usuario->name }}" />
                            <x-td value="{{ $usuario->email }}" />
                            <x-td class="text-center">
                                {{ $usuario->roles->pluck('name')->implode(', ') }}
                            </x-td>
                            <x-td class="!text-center">
                                <x-flex class="justify-center">
                                    <x-button @click="$wire.dispatch('EditarUsuario',{id:{{ $usuario->id }}})">
                                        <i class="fa fa-pencil"></i>
                                    </x-button>
                                    @if ($usuario->id != Auth::id())
                                        @if ($usuario->estado != '1')
                                            <x-button variant="warning"
                                                wire:click="updateStatus('{{ $usuario->id }}','1')">
                                                <i class="fa fa-ban"></i>
                                            </x-button>
                                        @else
                                            <x-button variant="success"
                                                wire:click="updateStatus('{{ $usuario->id }}','0')">
                                                <i class="fa fa-check"></i>
                                            </x-button>
                                        @endif

                                        <x-button variant="danger"
                                            wire:click="confirmarEliminacion({{ $usuario->id }})">
                                            <i class="fa fa-remove"></i>
                                        </x-button>
                                    @endif
                                </x-flex>

                            </x-td>
                        </x-tr>
                    @endforeach
                @else
                    <x-tr>
                        <x-td colspan="4">No hay usuarios registrados.</x-td>
                    </x-tr>
                @endif
            </x-slot>
        </x-table>
    </x-card>
    <x-loading wire:loading />
</div>
