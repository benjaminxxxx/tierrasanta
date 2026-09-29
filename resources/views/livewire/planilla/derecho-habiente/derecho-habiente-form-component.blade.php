<x-dialog-modal wire:model.live="show" maxWidth="2xl">
    <x-slot name="title">
        Registro de derecho-habientes y sus vínculos con empleados
    </x-slot>

    <x-slot name="content">

        {{-- PASO 1: buscar por documento antes de mostrar cualquier campo --}}
        @if ($paso === 'buscar')
            <div class="max-w-sm">
                <x-label value="DNI del derecho-habiente" />
                <div class="flex gap-2">
                    <x-input wire:model="documentoBusqueda" wire:keydown.enter="buscarDocumento"
                        placeholder="Ej. 71234567" maxlength="8" error="documentoBusqueda" />
                    <x-button wire:click="buscarDocumento"><i class="fa fa-search"></i> Buscar</x-button>
                </div>
                <p class="text-sm text-gray-400 mt-2">
                    Si el documento ya está registrado, se cargarán sus datos para editar o agregar un nuevo vínculo.
                </p>
            </div>
        @endif

        {{-- PASO 2: panel de registro/edición --}}
        @if ($paso === 'panel')

            @if (!$empleadoContextoId)
                <div class="mb-4">
                    <x-button size="sm" variant="secondary" wire:click="volverABuscar">
                        <i class="fa fa-arrow-left"></i> Buscar otro documento
                    </x-button>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div><x-label value="Nombres" /><x-input wire:model="nombres" error="nombres" /></div>
                <div><x-label value="Documento" /><x-input wire:model="documento" disabled /></div>
                <div><x-label value="Fecha de Nacimiento" /><x-input type="date" wire:model="fecha_nacimiento" error="fecha_nacimiento" /></div>
                <div>
                    <x-label value="Tipo" />
                    <x-select wire:model.live="tipo">
                        <option value="hijo">Hijo</option>
                        <option value="conyuge">Cónyuge</option>
                    </x-select>
                </div>

                @if ($tipo === 'hijo')
                    <div class="col-span-2 flex items-center gap-2">
                        <x-input type="checkbox" label="Discapacidad severa certificada (sin límite de edad)"
                            wire:model.live="discapacidad_severa" />
                    </div>

                    @if ($discapacidad_severa)
                        <div class="col-span-2 flex items-center gap-2">
                            <x-input type="checkbox"
                                label="Percibe Pensión No Contributiva por Discapacidad Severa (Ley 29973)"
                                wire:model="percibe_pension_no_contributiva" />
                        </div>
                        <div class="col-span-2">
                            <x-callout variant="warning"
                                heading="Si percibe esta pensión, la ley excluye la asignación familiar por esta causal, aunque la discapacidad esté certificada." />
                        </div>
                    @endif

                    @unless ($discapacidad_severa)
                        <div class="col-span-2 flex items-center gap-2">
                            <x-input type="checkbox" label="Está estudiando (educación superior)"
                                wire:model.live="esta_estudiando" />
                        </div>

                        @if ($esta_estudiando)
                            <div>
                                <x-label value="Inicio de estudios" />
                                <x-input type="date" wire:model="fecha_inicio_estudios" error="fecha_inicio_estudios" />
                            </div>
                            <div>
                                <x-label value="Fin de estudios (vacío si aún no termina)" />
                                <x-input type="date" wire:model="fecha_fin_estudios" />
                            </div>
                        @endif
                    @endunless
                @endif
            </div>

            <div class="mt-6 mb-2">
                <x-subtitle>Vínculos con empleados</x-subtitle>
            </div>

            @foreach ($vinculos as $i => $v)
                <div class="border rounded-md p-3 mb-2 flex items-center gap-3 border-border bg-muted">
                    <span class="flex-1 font-medium">{{ $v['empleado_nombre'] }} - {{ ucfirst($v['rol']) }}</span>

                    @if ($tipo === 'hijo')
                        <x-select wire:model="vinculos.{{ $i }}.mes_vigencia" class="w-32">
                            @foreach (range(1, 12) as $m)
                                <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                            @endforeach
                        </x-select>
                        <x-input type="number" wire:model="vinculos.{{ $i }}.anio_vigencia"
                            class="w-24" min="2000" max="2100" />
                    @endif

                    <x-button variant="danger" size="sm" wire:click="confirmarQuitarVinculo({{ $i }})">
                        <i class="fa fa-unlink"></i>
                    </x-button>
                </div>
            @endforeach

            <div class="border-t pt-4 mt-4 border-border">
                <x-subtitle class="mb-2">Agregar otro empleado vinculado</x-subtitle>
                <p class="text-sm text-gray-400 mb-3">
                    Útil cuando el otro padre/madre también trabaja aquí - evita volver a registrar al hijo.
                </p>

                <div class="flex flex-wrap items-end gap-3">
                    <div class="w-64">
                        <x-label value="Empleado" />
                        <x-select-dropdown wire:model="nuevoVinculoEmpleadoId" source="getEmpleados"
                            placeholder="Buscar empleado..." />
                    </div>
                    <div class="w-36">
                        <x-label value="Rol" />
                        <x-select wire:model="nuevoVinculoRol">
                            <option value="padre">Padre</option>
                            <option value="madre">Madre</option>
                            <option value="conyuge">Cónyuge</option>
                            <option value="tutor">Tutor</option>
                        </x-select>
                    </div>

                    @if ($tipo === 'hijo')
                        <div class="w-32">
                            <x-label value="Mes vigencia" />
                            <x-select wire:model="nuevoVinculoMes">
                                @foreach (range(1, 12) as $m)
                                    <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="w-24">
                            <x-label value="Año" />
                            <x-input type="number" wire:model="nuevoVinculoAnio" min="2000" max="2100" />
                        </div>
                    @endif

                    <x-button wire:click="agregarVinculoDesdeBuscador" variant="secondary">
                        <i class="fa fa-plus"></i> Agregar
                    </x-button>
                </div>
            </div>
        @endif
    </x-slot>

    <x-slot name="footer">
        <x-button wire:click="cerrar" variant="secondary">Cancelar</x-button>
        @if ($paso === 'panel')
            <x-button wire:click="guardar"><i class="fa fa-save"></i> Guardar</x-button>
        @endif
    </x-slot>
</x-dialog-modal>