<div>
    <!--MODULO COCHINILLA INGRESO FORMULARIO PRINCIPAL-->
    <x-loading wire:loading />

    <x-flex>
        <x-h3>
            Ingreso de Cochinilla
        </x-h3>
        <x-button @click="$wire.dispatch('agregarIngreso')">
            <i class="fa fa-plus"></i> Agregar ingreso
        </x-button>
        @if (count($filasSeleccionadas) > 0)
            <x-button wire:click="sincronizarDatos">
                <i class="fa fa-sync"></i> Sincronizar datos de {{ count($filasSeleccionadas) }}
                {{ count($filasSeleccionadas) == 1 ? 'lote' : 'lotes' }}
            </x-button>
        @endif

    </x-flex>
    <x-card class="mt-3">
        <x-spacing>
            <x-flex>
                <div>
                    <x-input-number label="Filtrar por lote" wire:model.live="lote" />
                </div>
                <div>
                    <x-select label="Filtrar por año" wire:model.live="anioSeleccionado">
                        <option value="">Todos los años</option>
                        @foreach ($aniosDisponibles as $anioDisponible)
                            <option value="{{ $anioDisponible }}">{{ $anioDisponible }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <x-select-campo label="Filtrar por Campo" wire:model.live="campoSeleccionado" />
                </div>
                <div>
                    <x-select-campanias label="Filtrar por Campaña" wire:model.live="campaniaSeleccionado" />
                </div>
                <div>
                    <x-select label="Filtrar por observación" wire:model.live="observacionSeleccionado">
                        <option value="">Todas las observaciones</option>
                        @foreach ($observaciones as $observacion)
                            <option value="{{ $observacion->codigo }}">{{ $observacion->descripcion }}</option>
                        @endforeach
                    </x-select>
                </div>
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
        </x-spacing>
    </x-card>
    <x-card class="mt-3">
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th rowspan="2" class="text-center">
                            <input type="checkbox" wire:model.live="seleccionarTodo" x-data
                                @click="
               let seleccionados = [];
               let checkboxes = document.querySelectorAll('input[data-lote-checkbox]');
               checkboxes.forEach(cb => {
                   cb.checked = $el.checked;
                   if (cb.checked) seleccionados.push(cb.value);
               });
               $wire.set('filasSeleccionadas', seleccionados);
           ">
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Lote
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Sub Lote
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Fecha
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Campo
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Área
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Campaña
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Cultivo
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            Fecha Siembra
                        </x-th>
                        <x-th colspan="2" class="text-center">
                            PROVEEDOR
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            TOTAL KILOS
                        </x-th>
                        <x-th rowspan="2" class="text-center">
                            OBS
                        </x-th>
                        <x-th colspan="2" class="text-center">
                            KILOS FINALES
                        </x-th>
                        <x-th class="text-center" rowspan="2">
                            ACCIONES
                        </x-th>
                    </x-tr>
                    <x-tr>

                        <x-th class="text-center">
                            KG Expor.
                        </x-th>
                        <x-th class="text-center">
                            KG / HA
                        </x-th>

                        <x-th class="text-center">
                            Diferencia
                        </x-th>
                        <x-th class="text-center">
                            % Diferencia
                        </x-th>

                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($cochinillaIngresos as $indice => $cochinillaIngreso)
                        @if ($cochinillaIngreso->detalles)
                            @foreach ($cochinillaIngreso->detalles as $detalle)
                                <x-tr>
                                    <x-td class="text-center">

                                    </x-td>
                                    <x-td class="text-center">

                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $detalle->sublote_codigo }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $detalle->fecha }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaIngreso->campo }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaIngreso->area }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaIngreso->campoCampania?->nombre_campania }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaIngreso->campoCampania?->variedad_tuna }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $cochinillaIngreso->fecha_siembra }}
                                    </x-td>
                                    <x-td class="text-center">

                                    </x-td>
                                    <x-td class="text-center">

                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $detalle->total_kilos }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $detalle->observacionRelacionada->descripcion }}
                                    </x-td>
                                    <x-td class="text-center">

                                    </x-td>
                                    <x-td class="text-center">

                                    </x-td>
                                </x-tr>
                            @endforeach
                        @endif
                        <x-tr>
                            <x-th class="text-center">
                                <input type="checkbox" data-lote-checkbox wire:model.live="filasSeleccionadas"
                                    value="{{ $cochinillaIngreso->lote }}"
                                    wire:key="filaSeleccionada{{ $cochinillaIngreso->lote }}">
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->lote }}
                            </x-th>
                            <x-th class="text-center">

                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->fecha }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->campo }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->area }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->campoCampania?->nombre_campania }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->campoCampania?->variedad_tuna }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->fecha_siembra }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->filtrado123 }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->filtrado123_x_ha }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->total_kilos }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->observacionRelacionada->descripcion }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $cochinillaIngreso->diferencia_filtrado }}
                            </x-th>
                            <x-th class="text-center">
                                {{ number_format($cochinillaIngreso->porcentaje_diferencia_filtrado, 2) }}%
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
                                    <div class="ms-3 relative">
                                        <x-dropdown align="right" width="60">
                                            <x-slot name="trigger">
                                                <span class="inline-flex rounded-md">
                                                    <button type="button"
                                                        class="inline-flex items-center px-3 py-2 border border-transparent leading-4 font-medium rounded-md dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                                        Acciones

                                                        <svg class="ms-2 -me-0.5 h-4 w-4"
                                                            xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24" stroke-width="1.5"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                                        </svg>
                                                    </button>
                                                </span>
                                            </x-slot>

                                            <x-slot name="content">
                                                <div class="w-60">
                                                    <x-dropdown-link class="text-center"
                                                        @click="$wire.dispatch('editarIngreso',{ingresoId:{{ $cochinillaIngreso->id }}})">
                                                        <i class="fa fa-edit"></i> Editar
                                                    </x-dropdown-link>
                                                    <x-dropdown-link class="text-center"
                                                        @click="$wire.dispatch('agregarDetalle',{ingresoId:{{ $cochinillaIngreso->id }}})">
                                                        <i class="fa fa-list"></i> Sublotes
                                                    </x-dropdown-link>
                                                    <x-dropdown-link class="text-center"
                                                        @click="$wire.dispatch('agregarVenteado',{ingresoId:{{ $cochinillaIngreso->id }}})">
                                                        <i class="fas fa-wind"></i> Venteado
                                                    </x-dropdown-link>
                                                    <x-dropdown-link class="text-center"
                                                        @click="$wire.dispatch('agregarFiltrado',{ingresoId:{{ $cochinillaIngreso->id }}})">
                                                        <i class="fa-solid fa-filter"></i> Filtrado
                                                    </x-dropdown-link>
                                                    <x-dropdown-link class="text-center"
                                                        @click="$wire.dispatch('abrirMapa',{ingresoId:{{ $cochinillaIngreso->id }}})">
                                                        <i class="fa fa-table"></i> Detalle gráfico
                                                    </x-dropdown-link>
                                                    @if ($cochinillaIngreso->lote >= 5000)
                                                        <x-dropdown-link class="text-center"
                                                            wire:click="eliminarIngreso({{ $cochinillaIngreso->id }})">
                                                            <i class="fa fa-remove"></i> Eliminar ingreso
                                                        </x-dropdown-link>
                                                    @endif

                                                </div>
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                </x-flex>


                            </x-th>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
            <div class="my-4">
                {{ $cochinillaIngresos->links() }}
            </div>
        </x-spacing>
    </x-card>
</div>
