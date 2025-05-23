<div>
    <x-loading wire:loading />

    <x-flex>
        <x-h3>
            Campos
        </x-h3>
        <x-button wire:click="registrarCampo">
            <i class="fa fa-plus"></i> Agregar Campo
        </x-button>
    </x-flex>
     <x-card class="mt-3">
        <x-spacing>
            <x-select-campo wire:model.live="filtroCampo" label="Filtrar por campo" />
        </x-spacing>
     </x-card>
    <x-card class="mt-3">
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">
                            N°
                        </x-th>
                        <x-th class="text-center">
                            Campo
                        </x-th>
                        <x-th class="text-center">
                            Alias
                        </x-th>
                        <x-th class="text-center">
                            Campo Padre
                        </x-th>
                        <x-th class="text-center">
                            Área
                        </x-th>
                        <x-th class="text-center">
                            Campaña Actual
                        </x-th>
                        <x-th class="text-center">
                            Acciones
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($campos as $indice => $campo)
                        <x-tr>
                            <x-td class="text-center">
                                {{ $indice + 1 }}
                            </x-td>
                            <x-th class="text-center">
                                {{ $campo->nombre }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $campo->alias }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $campo->campo_parent_nombre }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $campo->area }}
                            </x-th>
                            <x-th class="text-center">
                                @if ($campo->campania_actual)
                                    <p><b>Nombre de la ultima Campaña:
                                        </b>{{ $campo->campania_actual->nombre_campania }}</p>
                                    <p>Rango: {{ $campo->campania_actual->fecha_inicio }} -
                                        {{ $campo->campania_actual->fecha_fin }}</p>
                                @else
                                    <p>-</p>
                                @endif
                            </x-th>
                            <x-th class="text-center">
                                <x-flex>
                                    <x-button
                                        @click="$wire.dispatch('registroCampania',{campoNombre:'{{ $campo->nombre }}'})">
                                        <i class="fa fa-plus"></i> Crear campaña
                                    </x-button>
                                    <x-button-a href="{{ route('campo.campania', ['campo' => $campo->nombre]) }}">
                                        <i class="fa fa-eye"></i> Ver campañas
                                    </x-button-a>
                                    <x-secondary-button wire:click="editarCampo('{{ $campo->nombre }}')">
                                        <i class="fa fa-edit"></i> Editar
                                    </x-secondary-button>
                                </x-flex>
                            </x-th>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
            <div class="mt-4">
                {{ $campos->links() }}
            </div>
        </x-spacing>
    </x-card>
    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
            Registro de Campo
        </x-slot>

        <x-slot name="content">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-group-field>
                    <x-input-string label="Campo" wire:model="campoNombre" />
                </x-group-field>
                <x-group-field>
                    <x-select-campo label="Campo Padre" wire:model="campoPadre" />
                </x-group-field>
                <x-group-field>
                    <x-input-string label="Área" wire:model="area" error="area" />
                </x-group-field>
                <x-group-field>
                    <x-input-string label="Aliases(separe por comas nombres usados en sistemas antiguos)"
                        wire:model="alias" />
                </x-group-field>
            </div>

        </x-slot>

        <x-slot name="footer">
            <x-flex class="justify-end w-full">
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                @if ($estaEditando)
                    <x-button type="button" wire:click="storeCampos">
                        <i class="fa fa-save"></i> Editar Campo
                    </x-button>
                @else
                    <x-button type="button" wire:click="storeCampos">
                        <i class="fa fa-save"></i> Registrar Campo
                    </x-button>
                @endif

            </x-flex>
        </x-slot>
    </x-dialog-modal>
</div>
