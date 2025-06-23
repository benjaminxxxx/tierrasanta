<div>
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="2xl">
        <x-slot name="title">Nueva Transacción de Venta</x-slot>

        <x-slot name="content" class="space-y-6">
            {{-- VENTA --}}
            <div>
                <h3 class="font-semibold text-lg my-3 text-green-700">VENTA</h3>
                <div class="grid gap-4 md:grid-cols-3 items-end">
                    <x-input-date label="Fecha de Venta" wire:model.live="form.fecha_venta" />
                    <x-input-string label="Nombre del Comprador" wire:model="form.nombre_comprador" />
                    <x-group-field>
                        <x-button class="w-full" wire:click="buscarCosechas">
                            <i class="fa fa-search"></i> Buscar Cosechas
                        </x-button>
                    </x-group-field>
                    @if ($cosechaSeleccionada)
                        <x-input-string label="Infestadores del campo" wire:model="form.infestador_campo" />

                        <x-select label="Tipo de infestador" wire:model="form.estado">
                            <option value="">Seleccione</option>
                            <option value="malla">Malla</option>
                            <option value="carton">Carton</option>
                            <option value="tubo">Tubo</option>
                        </x-select>

                        <x-input-date label="Fecha de Ingreso" wire:model="form.fecha_ingreso" error="form.fecha_ingreso" />
                        <x-input-string label="Campo" wire:model="form.campo" error="form.campo" />
                        <x-input-string label="Procedencia" wire:model="form.procedencia" />
                        <x-input-number label="Cantidad Fresca (kg)" wire:model="form.kg" step="0.01" />
                        <x-input-date label="Fecha Filtrado" wire:model="form.fecha_filtrado" />
                        <x-input-number label="Cantidad Seca (kg)" wire:model="form.cantidad_seca" step="0.01" />
                        <x-select label="Condición" wire:model="form.condicion">
                            <option value="">Seleccione</option>
                            <option value="venta">Venta</option>
                        </x-select>
                        <div class="md:col-span-3">
                            <x-label value="Observaciones" />
                            <x-textarea wire:model="form.observaciones" rows="2" />
                        </div>
                    @endif

                </div>
            </div>

        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('mostrarFormulario', false)">Cancelar</x-secondary-button>
            <x-button wire:click="guardarTransaccion" class="ml-3">Guardar Transacción</x-button>
        </x-slot>
    </x-dialog-modal>
    <x-dialog-modal wire:model="mostrarBuscador" maxWidth="full">
        <x-slot name="title">Ultimas cosechas</x-slot>

        <x-slot name="content" class="space-y-6">

            <x-flex>
                <div>
                    <x-select label="Filtrar por venteado" wire:model.live="filtroVenteado">
                        <option value="">Todos</option>
                        <option value="conventeado">Con venteado</option>
                        <option value="sinventeado">Sin venteado</option>
                    </x-select>
                </div>
                <div>
                    <x-select label="Filtrar por filtrado" wire:model.live="filtroFiltrado">
                        <option value="">Todos</option>
                        <option value="confiltrado">Con Filtrado</option>
                        <option value="sinfiltrado">Sin Filtrado</option>
                    </x-select>
                </div>
            </x-flex>

            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">Campo</x-th>
                        <x-th class="text-center">Acciones</x-th>
                        <x-th class="text-center">Fecha de ingreso</x-th>
                        <x-th class="text-center">Lote</x-th>
                        <x-th class="text-center">Campaña</x-th>
                        <x-th class="text-center">Total Kilos</x-th>
                        <x-th class="text-center">Kg útil filtrado</x-th>
                        <x-th class="text-center">Observación</x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($ultimosIngresos)
                        @foreach ($ultimosIngresos as $cochinillaIngreso)
                            <x-tr>

                                <x-th class="text-center">
                                    {{ $cochinillaIngreso->campo }}
                                </x-th>
                                <x-th class="text-center">
                                    <x-flex>
                                        <x-flex>
                                            @if ($cochinillaIngreso->venteados->count() > 0)
                                                <x-badge class="bg-rose-200 text-black">
                                                    {{ $cochinillaIngreso->venteados->count() }}v
                                                </x-badge>
                                            @else
                                                <x-badge class="bg-gray-100 text-black">
                                                    0v
                                                </x-badge>
                                            @endif
                                            @if ($cochinillaIngreso->filtrados->count() > 0)
                                                <x-badge class="bg-lime-200 text-black">
                                                    {{ $cochinillaIngreso->filtrados->count() }}f
                                                </x-badge>
                                            @else
                                                <x-badge class="bg-gray-100 text-black">
                                                    0f
                                                </x-badge>
                                            @endif
                                        </x-flex>
                                        <x-button wire:click="venderDeAqui({{ $cochinillaIngreso->id }})">
                                            Vender de este lote
                                        </x-button>
                                    </x-flex>


                                </x-th>
                                <x-th class="text-center">
                                    {{ $cochinillaIngreso->fecha }}
                                </x-th>
                                <x-th class="text-center">
                                    {{ $cochinillaIngreso->lote }}
                                </x-th>


                                <x-th class="text-center">
                                    {{ $cochinillaIngreso->campoCampania?->nombre_campania }}
                                </x-th>
                                <x-th class="text-center">
                                    {{ $cochinillaIngreso->total_kilos }}
                                </x-th>
                                <x-th class="text-center">
                                    {{ $cochinillaIngreso->filtrado123 }}
                                </x-th>
                                <x-th class="text-center">
                                    {{ $cochinillaIngreso->observacionRelacionada->descripcion }}
                                </x-th>

                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="100%">No hay ingresos recientes</x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>

        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('mostrarBuscador', false)">Cerrar</x-secondary-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>