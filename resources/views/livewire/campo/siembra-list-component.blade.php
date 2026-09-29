<div class="space-y-4">
    <x-flex class="justify-between">
        <div>
            <x-title>
                Gestión de Siembras
            </x-title>
            <x-subtitle>
                Administra y gestiona las siembras de tus campos de manera eficiente.
            </x-subtitle>
        </div>
        <x-flex>
            @can(\App\Constants\Permisos::CAMPO_SIEMBRA_GESTIONAR)
                <x-button type="button" @click="$wire.dispatch('agregarSiembra')">
                    <i class="fa fa-plus"></i> Registrar Siembra
                </x-button>
            @endcan
            <x-button type="button" variant="secondary" wire:click="exportarReporte">
                <i class="fa fa-file-excel"></i> Exportar Reporte
            </x-button>
        </x-flex>
    </x-flex>
    <x-card>
        <x-flex class="justify-between">
            <x-flex>
                <x-select-campo wire:model.live="filtroCampo" label="Filtrar por campo" error="false"
                    placeholder="Todos los campos" class="w-auto" />

                <x-select label="Filtrar por año" wire:model.live="filtroAnio" class="w-auto">
                    <option value="">Todos los años</option>
                    @foreach ($aniosDisponibles as $anio)
                        <option value="{{ $anio }}">{{ $anio }}</option>
                    @endforeach
                </x-select>

            </x-flex>
            <x-flex>
                <x-button type="button" variant="ghost" wire:click="mostrarAuditoriaForm">
                    <i class="fa fa-clock"></i> Auditoria
                </x-button>

            </x-flex>
        </x-flex>

        <div class="mt-5">
            @can(\App\Constants\Permisos::CAMPO_SIEMBRA_VER)
                <x-table>
                    <x-slot name="thead">
                        <x-tr>
                            <x-th class="text-center">#</x-th>
                            <x-th class="text-center" sortable="campo_nombre">Campo</x-th>
                            <x-th class="text-center" sortable="fecha_siembra">Fecha de Siembra</x-th>
                            <x-th class="text-center">Fecha de Renovación</x-th>
                            <x-th class="text-center">N° de Campañas</x-th>
                            <x-th class="text-center">Creado Por</x-th>
                            <x-th class="text-center">Acciones</x-th>
                        </x-tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @foreach ($siembraLista as $index => $siembra)
                            <x-tr>
                                <x-td class="text-center">{{ $index + 1 }}</x-td>
                                <x-td class="text-center">{{ $siembra->campo_nombre }}</x-td>
                                <x-td class="text-center">{{ formatear_fecha($siembra->fecha_siembra) }}</x-td>
                                <x-td class="text-center">{{ formatear_fecha($siembra->fecha_renovacion) ?? '-' }}</x-td>
                                <x-td class="text-center">{{ $siembra->numero_campanias }}</x-td>
                                <x-td class="text-center">{{ $siembra->creado_por ?? '-' }}</x-td>
                                <x-td class="text-center">
                                    <x-flex class="justify-center">
                                        @can(\App\Constants\Permisos::CAMPO_SIEMBRA_GESTIONAR)

                                            <x-button size="xs" variant="danger"
                                                wire:click="preguntarEliminarSiembra({{ $siembra->id }})">
                                                <i class="fa fa-trash"></i> Eliminar
                                            </x-button>
                                        @endcan
                                    </x-flex>
                                </x-td>
                            </x-tr>
                        @endforeach
                    </x-slot>
                </x-table>
                <div class="mt-5">
                    {{ $siembraLista->links() }}
                </div>
            @else
                <x-danger>
                    No tienes permisos para ver las siembras. Por favor, contacta al administrador.
                </x-danger>
            @endcan
        </div>
    </x-card>

    <x-card class="mt-6">
        <x-flex class="justify-between">
            <x-title>Resumen Anual</x-title>
            <x-flex>
                <x-select-campo wire:model.live="filtroResumenCampo" label="Filtrar por campo" error="false"
                    placeholder="Todos los campos" class="w-auto" />

                <x-select label="Filtrar por año" wire:model.live="filtroResumenAnio" class="w-auto">
                    <option value="">Todos los años</option>
                    @foreach ($aniosDisponibles as $anio)
                        <option value="{{ $anio }}">{{ $anio }}</option>
                    @endforeach
                </x-select>
            </x-flex>
        </x-flex>

        <div class="mt-5">
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">Año</x-th>
                        <x-th class="text-center">N° de Siembras</x-th>
                        <x-th class="text-center">Campos Distintos</x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @forelse ($resumenAnual as $fila)
                        <x-tr>
                            <x-td class="text-center">{{ $fila->anio }}</x-td>
                            <x-td class="text-center">{{ $fila->total_siembras }}</x-td>
                            <x-td class="text-center">{{ $fila->total_campos }}</x-td>
                        </x-tr>
                    @empty
                        <x-tr>
                            <x-td colspan="3" class="text-center text-gray-500 py-4">
                                Sin datos para los filtros seleccionados.
                            </x-td>
                        </x-tr>
                    @endforelse
                </x-slot>
            </x-table>
        </div>
    </x-card>

    <x-dialog-modal wire:model.live="mostrarAuditoria" maxWidth="full">
        <x-slot name="title">
            Auditoria
        </x-slot>

        <x-slot name="content">

            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">#</x-th>
                        <x-th class="text-center">Acción</x-th>
                        <x-th>Información</x-th>
                        <x-th class="text-center">Usuario</x-th>
                        <x-th class="text-center">Fecha</x-th>
                    </x-tr>
                </x-slot>

                <x-slot name="tbody">
                    @forelse ($auditoriaHistorial as $index => $entrada)
                                    <x-tr>
                                        <x-td class="text-center">
                                            {{ $auditoriaHistorial->firstItem() + $index }}
                                        </x-td>

                                        <x-td class="text-center">
                                            <span class="font-semibold uppercase
                                                                                                        {{ $entrada->accion === 'crear'
                        ? 'text-green-600'
                        : 'text-red-600' }}">
                                                {{ $entrada->accion }}
                                            </span>
                                        </x-td>

                                        <x-td>
                                            @if(!empty($entrada->cambios))
                                                <div class="space-y-1 text-sm">
                                                    @foreach($entrada->cambios as $campo => $cambios)
                                                        <div>
                                                            @foreach ($cambios as $key => $cambio)
                                                                <div>
                                                                    <span class="font-semibold">{{ $key }}:</span>
                                                                    @if($entrada->accion === 'crear')
                                                                        <span class="text-green-600"> {{ $cambio }}</span>
                                                                    @elseif($entrada->accion === 'eliminar')
                                                                        <span class="text-red-600"> {{ $cambio }}</span>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endforeach

                                                    @if($entrada->observacion)
                                                        <div class="italic text-gray-500 mt-2">
                                                            {{ $entrada->observacion }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </x-td>

                                        <x-td class="text-center">
                                            {{ $entrada->usuario_nombre ?? 'Sistema' }}
                                        </x-td>

                                        <x-td class="text-center">
                                            {{ $entrada->fecha_accion->format('d/m/Y H:i') }}
                                        </x-td>
                                    </x-tr>
                    @empty
                        <x-tr>
                            <x-td colspan="5" class="text-center text-gray-500 py-4">
                                Sin historial de cambios.
                            </x-td>
                        </x-tr>
                    @endforelse
                </x-slot>
            </x-table>

            @if ($auditoriaHistorial->hasPages())
                <div class="mt-4">
                    {{ $auditoriaHistorial->links() }}
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">

            <x-button @click="$wire.set('mostrarAuditoria', false)">Cerrar</x-button>

        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>