<div class="overflow-x-auto">
    <x-table>
        <x-slot name="thead">
            <x-tr>
                <x-th>
                    Empleado
                </x-th>
                <x-th>
                    Tipo de Contrato
                </x-th>
                <x-th>
                    Fechas
                </x-th>
                <x-th>
                    Cargo
                </x-th>
                <x-th>
                    PENSIÓN
                </x-th>
                <x-th>
                    Acciones
                </x-th>
            </x-tr>
        </x-slot>

        <x-slot name="tbody">
            @forelse ($contratos as $contrato1)
         
                <x-tr>
                    <!-- Empleado -->
                    <x-td class="!text-left">
                        <p class="font-semibold">{{ $contrato1->empleado?->nombre_completo }}</p>
                        <p class="text-xs uppercase text-gray-500">{{ $contrato1->tipo_planilla }}</p>
                    </x-td>

                    <!-- Contrato -->
                    <x-td>
                        <p>{{ ucfirst($contrato1->tipo_contrato) }}</p>

                        @php
                            $estadoColor =
                                [
                                    'activo' => 'bg-green-100 text-green-800',
                                    'finalizado' => 'bg-red-100 text-red-800',
                                    'renovado' => 'bg-blue-100 text-blue-800',
                                ][$contrato1->estado] ?? 'bg-gray-100 text-gray-800';
                        @endphp

                        <span
                            class="inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $estadoColor }}">
                            {{ ucfirst($contrato1->estado) }}
                        </span>
                    </x-td>

                    <!-- Fechas -->
                    <x-td class="text-xs">
                        <div>
                            <b>Inicio:</b> {{ formatear_fecha($contrato1->fecha_inicio) }}
                        </div>
                        <div>
                            <b>Fin:</b> {{ formatear_fecha($contrato1->fecha_fin) }}
                        </div>
                    </x-td>

                    <!-- Cargo -->
                    <x-td class="text-xs">
                        <p><b>Cargo:</b> {{ $contrato1->cargo_codigo }}</p>
                        <p><b>Grupo:</b> {{ $contrato1->grupo_codigo }}</p>
                    </x-td>
                    <x-td class="text-xs">
                        <p>{{ $contrato1->plan_sp_codigo }}</p>
                    </x-td>


                    <!-- Opciones -->
                    <x-td class="text-center">
                        <div class="relative">
                            <x-dropdown align="right" width="60">
                                <x-slot name="trigger">
                                    <span class="inline-flex rounded-md">
                                        <button type="button"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                            Opciones

                                            <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                            </svg>
                                        </button>
                                    </span>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="w-60">
                                        @if ($contrato1->estado === 'activo')
                                        <x-dropdown-link
                                            @click="$dispatch('editarContrato', {id: {{ $contrato1->id }}})">
                                            Editar Registro
                                        </x-dropdown-link>
                                        @endif

                                        <x-dropdown-link wire:click="verInformacion({{ $contrato1->id }})">
                                            Ver Información
                                        </x-dropdown-link>

                                        @if ($contrato1->estado === 'activo')
                                            <x-dropdown-link wire:click="renovarContrato({{ $contrato1->id }})">
                                                Renovar Contrato
                                            </x-dropdown-link>

                                            <x-dropdown-link wire:click="finalizarContrato({{ $contrato1->id }})">
                                                Finalizar Contrato
                                            </x-dropdown-link>
                                        @endif

                                        <x-dropdown-link class="text-red-600"
                                            wire:click="eliminarContrato({{ $contrato1->id }})">
                                            Eliminar Registro
                                        </x-dropdown-link>
                                    </div>
                                </x-slot>
                            </x-dropdown>
                        </div>

                    </x-td>
                </x-tr>
            @empty
                <x-tr>
                    <x-td colspan="7" class="text-center py-6">
                        <p class="font-medium text-sm">No hay contratos registrados</p>
                        <p class="text-xs">Comienza creando un nuevo contrato</p>
                    </x-td>
                </x-tr>
            @endforelse

        </x-slot>
    </x-table>
    <div class="mt-4">
        {{ $contratos->links() }}
    </div>
</div>
