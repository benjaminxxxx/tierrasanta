<div>
    <x-loading wire:loading />
    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Usuarios
        </x-h3>
        <x-button type="button" @click="$wire.dispatch('CrearUsuario')" class="w-full md:w-auto ">Nuevo usuario</x-button>
    </div>
    <x-card>
        <x-spacing>
            <x-table class="mt-5">
                <x-slot name="thead">
                    <tr>
                        <x-th value="N°" />
                        <x-th value="Nombre" />
                        <x-th value="Email" />
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
                                <x-td  class="!text-center">
                                    @if ($usuario->id != Auth::id())
                                        <div class="flex items-center justify-center gap-2">
                                            @if ($usuario->estado != '1')
                                                <x-warning-button wire:click="updateStatus('{{ $usuario->id }}','1')">
                                                    <i class="fa fa-ban"></i>
                                                </x-warning-button>
                                            @else
                                                <x-success-button wire:click="updateStatus('{{ $usuario->id }}','0')">
                                                    <i class="fa fa-check"></i>
                                                </x-success-button>
                                            @endif
                                            <x-button @click="$wire.dispatch('EditarUsuario',{id:{{$usuario->id}}})">
                                                <i class="fa fa-pencil"></i>
                                            </x-button>
                                            <x-danger-button wire:click="confirmarEliminacion({{ $usuario->id }})">
                                                <i class="fa fa-remove"></i>
                                            </x-danger-button>
                                        </div>
                                    @endif
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
        </x-spacing>
    </x-card>
</div>
