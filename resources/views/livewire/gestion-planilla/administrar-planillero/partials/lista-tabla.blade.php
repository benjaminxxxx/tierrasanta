<x-card2 class="mt-5">
    <x-table>
        <x-slot name="thead">
            <tr>
                <x-th value="INFORMACIÓN BÁSICA" colspan="5" class="text-center bg-gray-100 dark:bg-gray-700" />
                <x-th value="INFORMACIÓN DE ÚLTIMO CONTRATO" colspan="8"
                    class="text-center bg-gray-200 dark:bg-sky-700" />
                <x-th value="Acciones" rowspan="2" class="text-center" />
            </tr>
            <tr>
                <x-th value="N°" class="text-center bg-gray-100 dark:bg-gray-700" />
                <x-th value="Documento" class="text-center bg-gray-100 dark:bg-gray-700" />
                <x-th value="Nombre Completo" class=" bg-gray-100 dark:bg-gray-700" />
                <x-th value="Orden" class="text-center bg-gray-100 dark:bg-gray-700" />
                <x-th value="Asignación Familiar" class="text-center bg-gray-100 dark:bg-gray-700" />

                <x-th value="F. vigencia." class="text-center bg-gray-200 dark:bg-sky-700" />
                <x-th value="Sueldo" class="text-center bg-gray-200 dark:bg-sky-700" />
                <x-th value="Grupo" class="text-center bg-gray-200 dark:bg-sky-700" />
                <x-th value="Comp. vacacional" class="text-center bg-gray-200 dark:bg-sky-700" />
                <x-th value="SNP/SPP" class="text-center bg-gray-200 dark:bg-sky-700" />
                <x-th value="Cargo" class="text-center bg-gray-200 dark:bg-sky-700" />
                <x-th value="Mod. pago" class="text-center bg-gray-200 dark:bg-sky-700" />
                <x-th value="Tpo Planilla" class="text-center bg-gray-200 dark:bg-sky-700" />

            </tr>
        </x-slot>
        <x-slot name="tbody">
            @if ($empleados->count())
                @foreach ($empleados as $indice => $empleado)
                    <x-tr style="background-color:{{ $empleado->grupo ? $empleado->grupo->color : '#ffffff' }}">
                        <x-th value="{{ $indice + 1 }}" class="dark:text-gray-800" />
                        <x-td value="{{ $empleado->documento }}" class="dark:text-gray-800" />
                        <x-td class="dark:text-gray-800">
                            <p>
                                {{ $empleado->nombreCompleto }}
                            </p>
                            <p>
                                F. Nac. {{ formatear_fecha($empleado->fecha_nacimiento) }} - F. Ingr.
                                {{ formatear_fecha($empleado->fecha_ingreso) }}
                            </p>
                        </x-td>

                        <x-td>
                            @if ($empleado->status == 'activo')
                                <div class="flex items-center gap-2">
                                    <x-success-button wire:click="moveUp({{ $empleado->id }})" class="">
                                        <i class="fa fa-arrow-up"></i>
                                    </x-success-button>
                                    <x-input class="!w-12 !p-2 !mt-0 text-center" value="{{ $empleado->orden }}"
                                        wire:keyup.debounce.500ms="moveAt({{ $empleado->id }}, $event.target.value)" />
                                    <x-button wire:click="moveDown({{ $empleado->id }})" class="">
                                        <i class="fa fa-arrow-down"></i>
                                    </x-button>
                                </div>
                            @endif
                        </x-td>
                        <x-td>
                            @if ($empleado->status == 'activo')
                                <x-secondary-button wire:click="asignacionFamiliar('{{ $empleado->code }}')">
                                    {{ $empleado->tieneAsignacionFamiliar['mensaje'] }}
                                </x-secondary-button>
                            @endif
                        </x-td>
                        {{-- Informacion da ultimo contrato --}}
                        <x-td value="{{ formatear_fecha($empleado->ultimoContrato?->fecha_inicio) }}"
                            class="dark:text-gray-800" />
                        <x-td value="{{ formatear_numero($empleado->ultimoContrato?->sueldo) }}"
                            class="dark:text-gray-800" />
                        <x-td value="{{ $empleado->ultimoContrato?->grupo_codigo }}" class="dark:text-gray-800" />
                        <x-td value="{{ $empleado->ultimoContrato?->compensacion_vacacional }}"
                            class="dark:text-gray-800" />
                        <x-td value="{{ $empleado->ultimoContrato?->descuento_sp_id }}" class="dark:text-gray-800" />
                        <x-td value="{{ isset($empleado->cargo) ? $empleado->cargo->nombre : '-' }}"
                            class="dark:text-gray-800" />
                        <x-td value="{{ $empleado->ultimoContrato?->modalidad_pago }}" class="dark:text-gray-800" />
                        <x-td value="{{ $empleado->tipo_planilla_descripcion }}" class="dark:text-gray-800" />

                        <x-td>
                            <div>
                                <x-dropdown align="right">
                                    <x-slot name="trigger">
                                        <span class="inline-flex rounded-md w-full lg:w-auto">
                                            <x-button type="button" class="flex items-center justify-center">
                                                Opciones
                                                <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                                </svg>
                                            </x-button>
                                        </span>
                                    </x-slot>

                                    <x-slot name="content">
                                        <div class="w-full text-center">
                                            @if (!$empleado->trashed())

                                                <x-dropdown-link @click="$wire.dispatch('abrirFormularioRegistroEmpleadoContrato',{uuid:'{{ $empleado->uuid }}'})">
                                                    <i class="fa fa-table"></i> Registrar Contrato
                                                </x-dropdown-link>

                                                <x-dropdown-link @click="$wire.dispatch('editarEmpleado',{uuid:'{{ $empleado->uuid }}'})">
                                                    <i class="fa fa-pencil"></i> Editar
                                                </x-dropdown-link>

                                                <x-dropdown-link wire:click="eliminarEmpleado('{{ $empleado->uuid }}')"
                                                    class="text-red-600 hover:text-red-700">
                                                    <i class="fa fa-remove"></i> Eliminar
                                                </x-dropdown-link>
                                            @else
                                                <x-dropdown-link wire:click="restaurarEmpleado('{{ $empleado->uuid }}')"
                                                    class="text-green-600 hover:text-green-700">
                                                    <i class="fa fa-undo"></i> Restaurar
                                                </x-dropdown-link>
                                            @endif
                                        </div>
                                    </x-slot>
                                </x-dropdown>
                            </div>
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
