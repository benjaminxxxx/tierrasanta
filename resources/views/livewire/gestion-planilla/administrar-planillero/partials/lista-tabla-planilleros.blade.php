<x-card2 class="mt-5">
    <x-table>
        <x-slot name="thead">

            <tr>
                <x-th value="N°" class="text-center" />
                <x-th value="Nombre Completo" />
                <x-th value="F. Inicio Contrato" class="text-center" />
                <x-th value="Grupo" class="text-center" />
                <x-th value="Comp. vacacional" class="text-center" />
                <x-th value="SNP/SPP" class="text-center" />
                <x-th value="Cargo" class="text-center" />
                <x-th value="Mod. pago" class="text-center" />
                <x-th value="Tpo Planilla" class="text-center" />
                <x-th value="Acciones" rowspan="2" class="text-center" />
            </tr>
        </x-slot>
        <x-slot name="tbody">
            @if ($empleados->count())
                @foreach ($empleados as $indice => $empleado)
                    <x-tr style="background-color: {{ $empleado->color_grupo ?? '#ffffff' }};
                                     color: {{ $empleado->color_texto_grupo ?? '' }}">
                        <x-th value="{{ $indice + 1 }}" class="dark:text-gray-800 text-center" />
                        <x-td class="dark:text-gray-800">
                            {{ $empleado->nombreCompleto }}
                        </x-td>
                        {{-- Informacion da ultimo contrato --}}
                        <x-td value="{{ formatear_fecha($empleado->ultimoContrato?->fecha_inicio) }}"
                            class="dark:text-gray-800 text-center" />
                        <x-td value="{{ $empleado->ultimoContrato?->grupo_codigo }}" class="dark:text-gray-800 text-center" />
                        <x-td value="{{ $empleado->ultimoContrato?->compensacion_vacacional }}"
                            class="dark:text-gray-800 text-center" />
                        <x-td value="{{ $empleado->ultimoContrato?->descuento?->codigo }}"
                            class="dark:text-gray-800 text-center" />
                        <x-td value="{{ $empleado->ultimoContrato?->cargo?->nombre }}" class="dark:text-gray-800 text-center" />
                        <x-td value="{{ $empleado->ultimoContrato?->modalidad_pago }}" class="dark:text-gray-800 text-center" />
                        <x-td value="{{ $empleado->tipo_planilla_descripcion }}" class="dark:text-gray-800 text-center" />

                        <x-td class="text-center">

                            <x-dropdown align="right">
                                <x-slot name="trigger">
                                    <span class="inline-flex rounded-md w-full lg:w-auto">
                                        <x-button type="button" class="flex items-center justify-center">
                                            Opciones
                                            <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                            </svg>
                                        </x-button>
                                    </span>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="w-full text-center">
                                        @if (!$empleado->trashed())

                                            <x-dropdown-link href="{{ route('planilla.contratos', ['uuid' => $empleado->uuid]) }}">
                                                <i class="fa fa-table"></i> Gestionar Contratos
                                            </x-dropdown-link>

                                            <x-dropdown-link
                                                @click="$wire.dispatch('abrirFormularioRegistroEmpleadoSueldo',{uuid:'{{ $empleado->uuid }}'})">
                                                <i class="fa fa-money-bill"></i> Gestionar Sueldos
                                            </x-dropdown-link>

                                            <x-dropdown-link
                                                @click="$wire.dispatch('agregarFamiliarEmpleado',{uuid:'{{ $empleado->uuid }}'})">
                                                <i class="fa-solid fa-people-roof"></i> Gestionar Familiares
                                            </x-dropdown-link>

                                            <x-dropdown-link
                                                @click="$wire.dispatch('editarEmpleado',{uuid:'{{ $empleado->uuid }}'})">
                                                <i class="fa fa-pencil"></i> Editar Registro
                                            </x-dropdown-link>

                                            <x-dropdown-link wire:click="eliminarEmpleado('{{ $empleado->uuid }}')"
                                                class="text-red-600 hover:text-red-700">
                                                <i class="fa fa-remove"></i> Eliminar Empleado
                                            </x-dropdown-link>
                                        @else
                                            <x-dropdown-link wire:click="restaurarEmpleado('{{ $empleado->uuid }}')"
                                                class="text-green-600 hover:text-green-700">
                                                <i class="fa fa-undo"></i> Restaurar Empleado
                                            </x-dropdown-link>
                                        @endif
                                    </div>
                                </x-slot>
                            </x-dropdown>
                        </x-td>


                    </x-tr>
                @endforeach
            @else
                <x-tr>
                    <x-td colspan="4">No hay Empleados registrados.</x-td>
                </x-tr>
            @endif
        </x-slot>
    </x-table>
</x-card2>
<div class="mt-5">
    {{ $empleados->links() }}
</div>