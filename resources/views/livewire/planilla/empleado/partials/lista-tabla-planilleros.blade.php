<x-card class="mt-5">
    <x-table noScroll>
        <x-slot name="thead">

            <tr>
                <x-th value="N°" class="text-center" />
                <x-th value="Empleado" />
                <x-th value="Vigencia de contrato" class="text-center" />
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
                    @php
                        // Se muestra el contrato vigente; si no hay, el último que tuvo.
                        $contrato = $empleado->contratoVigente ?? $empleado->ultimoContrato;
                        $colorGrupo = $contrato?->grupo?->color;
                        $hoy = now()->startOfDay();
                        $inicio = $contrato?->fecha_inicio ? \Carbon\Carbon::parse($contrato->fecha_inicio) : null;
                        $fin = $contrato?->fecha_fin ? \Carbon\Carbon::parse($contrato->fecha_fin) : null;

                        [$estadoTexto, $estadoClase] = match (true) {
                            !$contrato => ['Sin contrato', 'bg-muted text-muted-foreground'],
                            (bool) $empleado->contratoVigente && $fin && $fin->diffInDays($hoy, true) <= 30
                                => ['Vence pronto', 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'],
                            (bool) $empleado->contratoVigente => ['Vigente', 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'],
                            $inicio && $inicio->gt($hoy) => ['Por iniciar', 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'],
                            default => ['Vencido', 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'],
                        };
                    @endphp
                    <x-tr wire:key="empleado-{{ $empleado->id }}" :class="$empleado->trashed() ? 'opacity-60' : ''">
                        {{-- Franja sutil con el color del grupo --}}
                        <x-th value="{{ ($empleados->firstItem() ?? 1) + $indice }}" class="text-center"
                            style="box-shadow: inset 4px 0 0 {{ $colorGrupo ?? 'transparent' }};" />
                        <x-td>
                            <button type="button" class="font-medium text-left hover:underline"
                                @click="$wire.dispatch('abrirEmpleado', { id: {{ $empleado->id }}, tab: 'perfil' })"
                                title="Ver perfil del trabajador">
                                {{ $empleado->nombreCompleto }}
                            </button>
                            <div class="text-xs text-muted-foreground">
                                DNI {{ $empleado->documento }}
                                @if ($empleado->trashed())
                                    <span class="ml-1 px-1.5 rounded bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Eliminado</span>
                                @endif
                            </div>
                        </x-td>
                        <x-td class="text-center whitespace-nowrap">
                            @if ($contrato)
                                <div class="text-xs">
                                    {{ formatear_fecha($contrato->fecha_inicio) }} →
                                    {{ $contrato->fecha_fin ? formatear_fecha($contrato->fecha_fin) : 'Indefinido' }}
                                </div>
                            @endif
                            <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $estadoClase }}">
                                {{ $estadoTexto }}
                            </span>
                        </x-td>
                        <x-td class="text-center whitespace-nowrap">
                            @if ($contrato?->grupo_codigo)
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-block size-2.5 rounded-full border border-border"
                                        style="background-color: {{ $colorGrupo ?? 'transparent' }}"></span>
                                    {{ $contrato->grupo_codigo }}
                                </span>
                            @else
                                -
                            @endif
                        </x-td>
                        <x-td value="{{ $contrato?->compensacion_vacacional }}" class="text-center" />
                        <x-td value="{{ $contrato?->descuento?->codigo }}" class="text-center" />
                        <x-td value="{{ $empleado->nombreCargoActual }}" class="text-center" />
                        <x-td value="{{ $contrato?->modalidad_pago }}" class="text-center" />
                        <x-td value="{{ match ($contrato?->tipo_planilla) { 'agraria' => 'P. AGRARIA', 'oficina' => 'P. OFICINA', null => '-', default => mb_strtoupper($contrato->tipo_planilla) } }}"
                            class="text-center" />

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
                                        {{-- Todas las opciones abren el panel del empleado en la pestaña correspondiente --}}
                                        <x-dropdown-link @click="$wire.dispatch('abrirEmpleado', { id: {{ $empleado->id }}, tab: 'perfil' })">
                                            <i class="fa fa-timeline"></i> Ver perfil
                                        </x-dropdown-link>

                                        @if (!$empleado->trashed())

                                            @can(\App\Constants\Permisos::PERSONAL_CONTRATOS)
                                                <x-dropdown-link @click="$wire.dispatch('abrirEmpleado', { id: {{ $empleado->id }}, tab: 'contratos' })">
                                                    <i class="fa fa-table"></i> Gestionar Contratos
                                                </x-dropdown-link>

                                                <x-dropdown-link @click="$wire.dispatch('abrirEmpleado', { id: {{ $empleado->id }}, tab: 'sueldos' })">
                                                    <i class="fa fa-money-bill"></i> Gestionar Sueldos
                                                </x-dropdown-link>
                                            @endcan

                                            @can(\App\Constants\Permisos::PERSONAL_CARGOS)
                                                <x-dropdown-link @click="$wire.dispatch('abrirEmpleado', { id: {{ $empleado->id }}, tab: 'cargos' })">
                                                    <i class="fa fa-id-badge"></i> Gestionar Cargo
                                                </x-dropdown-link>
                                            @endcan

                                            @can(\App\Constants\Permisos::PERSONAL_FAMILIARES)
                                                <x-dropdown-link @click="$wire.dispatch('abrirEmpleado', { id: {{ $empleado->id }}, tab: 'familiares' })">
                                                    <i class="fa-solid fa-people-roof"></i> Gestionar Derecho Habientes
                                                </x-dropdown-link>
                                            @endcan

                                            @can(\App\Constants\Permisos::PERSONAL_EDITAR)
                                                <x-dropdown-link @click="$wire.dispatch('abrirEmpleado', { id: {{ $empleado->id }}, tab: 'datos' })">
                                                    <i class="fa fa-pencil"></i> Editar Datos
                                                </x-dropdown-link>
                                            @endcan

                                            @can(\App\Constants\Permisos::PERSONAL_ELIMINAR)
                                                <x-dropdown-link wire:click="eliminarEmpleado({{ $empleado->id }})"
                                                    class="text-red-600 hover:text-red-700">
                                                    <i class="fa fa-remove"></i> Eliminar Empleado
                                                </x-dropdown-link>
                                            @endcan

                                        @else

                                            @can(\App\Constants\Permisos::PERSONAL_RESTAURAR)
                                                <x-dropdown-link wire:click="restaurarEmpleado({{ $empleado->id }})"
                                                    class="text-green-600 hover:text-green-700">
                                                    <i class="fa fa-undo"></i> Restaurar Empleado
                                                </x-dropdown-link>
                                            @endcan

                                        @endif
                                    </div>
                                </x-slot>
                            </x-dropdown>
                        </x-td>


                    </x-tr>
                @endforeach
            @else
                <x-tr>
                    <x-td colspan="100%">No hay Empleados registrados.</x-td>
                </x-tr>
            @endif
        </x-slot>
    </x-table>
</x-card>
<div class="mt-5">
    {{ $empleados->links() }}
</div>