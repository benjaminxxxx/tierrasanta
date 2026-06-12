{{-- resources/views/livewire/gestion-cuadrilla/gestion-cuadrilla-gastos-adicionales-component.blade.php --}}
<div>
    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">

            <div>
                Gastos adicionales -
                {{ \Carbon\Carbon::parse($tramoLaboral->fecha_inicio)->format('d/m/Y') }}
                al
                {{ \Carbon\Carbon::parse($tramoLaboral->fecha_fin)->format('d/m/Y') }}
            </div>
            <p class="text-sm text-gray-500 mt-1">
                Los gastos <strong>aprobados</strong> quedan sellados y no pueden eliminarse.
            </p>
        </x-slot>
        <x-slot name="content">
            {{-- ── Tabla de gastos existentes ── --}}
            <div class="mt-4 overflow-x-auto">
                <x-table class="w-full text-sm">
                    <x-slot name="thead">
                        <x-tr>
                            <x-th class="text-left ">Grupo</x-th>
                            <x-th class="text-left ">Descripción</x-th>
                            <x-th class="text-left ">Fecha</x-th>
                            <x-th class="text-right ">Monto</x-th>
                            <x-th class="text-center ">Estado</x-th>
                            <x-th class=""></x-th>
                        </x-tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @forelse ($gastosExistentes as $gasto)
                            @if ($editandoId === $gasto['id'])
                                {{-- ── Fila de edición inline ── --}}
                                <x-tr class="bg-blue-50 dark:bg-blue-900">
                                    <td class="px-3 py-2">
                                        <x-select wire:model="editDatos.grupo" class="w-full text-sm">
                                            <option value="">Seleccione</option>
                                            @foreach ($grupos as $g)
                                                <option value="{{ $g }}">{{ $g }}</option>
                                            @endforeach
                                        </x-select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <x-input wire:model="editDatos.descripcion" type="text" class="w-full text-sm" />
                                    </td>
                                    <td class="px-3 py-2">
                                        <x-input wire:model="editDatos.fecha" type="date" class="w-full text-sm"
                                            min="{{ \Carbon\Carbon::parse($tramoLaboral->fecha_inicio)->toDateString() }}"
                                            max="{{ \Carbon\Carbon::parse($tramoLaboral->fecha_fin)->toDateString() }}" />
                                    </td>
                                    <td class="px-3 py-2">
                                        <x-input wire:model="editDatos.monto" type="number" step="0.01"
                                            class="w-full text-sm text-right" />
                                    </td>
                                    <x-td class="text-center" colspan="2">
                                        <x-flex>
                                            <x-button wire:click="guardarEdicion" class="">
                                                <i class="fa fa-check"></i> Guardar
                                            </x-button>
                                            <x-button variant="secondary" wire:click="cancelarEdicion" class="">
                                                Cancelar
                                            </x-button>
                                        </x-flex>
                                    </x-td>
                                </x-tr>
                            @else
                                {{-- ── Fila normal ── --}}
                                <tr
                                    class="
                                                                                            {{ $gasto['fuera_de_rango'] ? 'bg-red-50 dark:bg-red-900' : '' }}
                                                                                            {{ $gasto['estado'] === 'aprobado' ? 'opacity-60' : '' }}">
                                    <td class="px-3 py-2">{{ $gasto['grupo'] }}</td>
                                    <td class="px-3 py-2">{{ $gasto['descripcion'] }}</td>
                                    <td class="px-3 py-2">
                                        {{ \Carbon\Carbon::parse($gasto['fecha'])->format('d/m/Y') }}
                                        @if ($gasto['fuera_de_rango'])

                                            Fuera del rango del tramo

                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        S/ {{ number_format($gasto['monto'], 2) }}
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        @if ($gasto['estado'] === 'aprobado')
                                            <span
                                                class="inline-flex items-center gap-1 text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 px-2 py-0.5 rounded">
                                                <i class="fa fa-lock text-xs"></i> Aprobado
                                            </span>
                                        @elseif ($gasto['estado'] === 'en_correccion')
                                            <span
                                                class="inline-flex items-center gap-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-0.5 rounded">
                                                <i class="fa fa-edit text-xs"></i> En corrección
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1 text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 px-2 py-0.5 rounded">
                                                <i class="fa fa-clock text-xs"></i> Pendiente
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-1 justify-end">
                                            @if ($gasto['puede_editar'])
                                                <x-button wire:click="iniciarEdicion({{ $gasto['id'] }})" class="" title="Editar">
                                                    <i class="fa fa-pencil text-sm"></i>
                                                </x-button>
                                            @endif
                                            @if ($gasto['puede_eliminar'])
                                                <x-button wire:click="eliminarGasto({{ $gasto['id'] }})"
                                                    wire:confirm="¿Eliminar este gasto?" variant="danger" title="Eliminar">
                                                    <i class="fa fa-trash text-sm"></i>
                                                </x-button>
                                            @endif
                                            {{-- Supervisor: habilitar corrección en aprobados --}}
                                            @can('aprobar-gastos-cuadrilla')
                                                @if ($gasto['estado'] === 'aprobado')
                                                    <x-button wire:click="habilitarCorreccion({{ $gasto['id'] }})" variant="success"
                                                        title="Habilitar corrección">
                                                        <i class="fa fa-unlock text-sm"></i>
                                                    </x-button>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-gray-400 text-sm">
                                    No hay gastos registrados en este tramo.
                                </td>
                            </tr>
                        @endforelse
                    </x-slot>
                    <x-slot name="footer">
                        <tr class="border-t border-gray-300 dark:border-gray-600 font-medium">
                            <td colspan="3" class="px-3 py-2 text-right text-sm">Total</td>
                            <td class="px-3 py-2 text-right text-sm">
                                S/ {{ number_format(collect($gastosExistentes)->sum('monto'), 2) }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </x-slot>
                </x-table>
            </div>

            {{-- ── Advertencia de fechas fuera de rango ── --}}
            @if (collect($gastosExistentes)->where('fuera_de_rango', true)->isNotEmpty())
                <x-danger>
                    Hay {{ collect($gastosExistentes)->where('fuera_de_rango', true)->count() }} gasto(s) con fecha
                    fuera del rango del tramo.
                    Corrija la fecha antes de aprobar.
                </x-danger>
            @endif

            {{-- ── Formulario para agregar nuevo gasto ── --}}
            <div class="mt-5 border-t border-gray-200 dark:border-gray-700 pt-4">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    <i class="fa fa-plus-circle mr-1"></i> Agregar nuevo gasto
                </p>
                <x-flex>

                    <x-select wire:model="nuevoGrupo" label="Grupo" class="w-auto" error="nuevoGrupo">
                        <option value="">Seleccione</option>
                        @foreach ($grupos as $g)
                            <option value="{{ $g }}">{{ $g }}</option>
                        @endforeach
                    </x-select>


                    <x-input wire:model="nuevoDescripcion" type="text" label="Descripción" class="w-auto"
                        placeholder="Ej: COMISION, HERRAMIENTAS..." class="w-auto uppercase" error="nuevoDescripcion" />


                    <div>
                        @php
                            $rangoAceptado = \Carbon\Carbon::parse($tramoLaboral->fecha_inicio)->format('d/m') . ' – ' . \Carbon\Carbon::parse($tramoLaboral->fecha_fin)->format('d/m');
                        @endphp
                        <x-input wire:model="nuevoFecha" type="date" label="Fecha: {{ $rangoAceptado }}"
                            class="w-full text-sm mt-1"
                            min="{{ \Carbon\Carbon::parse($tramoLaboral->fecha_inicio)->toDateString() }}"
                            max="{{ \Carbon\Carbon::parse($tramoLaboral->fecha_fin)->toDateString() }}"
                            error="nuevoFecha" />
                    </div>

                    <x-input wire:model="nuevoMonto" type="number" step="0.01" label="Monto (S/)" min="0" class="w-auto"
                        error="nuevoMonto" />

                    <x-button wire:click="agregarGasto">
                        <i class="fa fa-plus"></i> Agregar
                    </x-button>
                </x-flex>
                <p class="text-xs text-gray-400 mt-2">
                    <i class="fa fa-info-circle"></i>
                    La descripción se guardará en mayúsculas para evitar duplicados (ej: "comision" y "COMISION" se
                    tratan igual).
                </p>
            </div>
        </x-slot>
        <x-slot name="footer">
            @can('aprobar-gastos-cuadrilla')
                <x-button wire:click="aprobarTodos"
                    wire:confirm="¿Aprobar y sellar todos los gastos pendientes? Esta acción no se puede deshacer fácilmente."
                    variant="success">
                    <i class="fa fa-check-double"></i> Aprobar todos los pendientes
                </x-button>
            @endcan
            <x-button wire:click="cerrar" variant="secondary">
                Cerrar
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>