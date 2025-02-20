<div>
    <x-loading wire:loading />
    <x-h3>
        Registros de Costos Fijos y Operativos
    </x-h3>
    <x-card class="mt-2">
        <x-spacing>
            <div class="grid grid-cols-2 gap-4">
                {{-- Tipo de Costo --}}
                <div>
                    <x-label for="tipoCosto" value="Tipo de Costo" />
                    <x-select wire:model.live="tipoCosto">
                        <option value="operativo">OPERATIVO</option>
                        <option value="fijo">FIJO</option>
                    </x-select>
                    <x-input-error for="tipoCosto" />
                </div>

                {{-- Nombre del Costo --}}
                <div>
                    <x-label for="tipoCostoId" value="Nombre del Costo" />
                    <x-select wire:model="tipoCostoId">
                        <option value="">SELECCIONE EL NOMBRE DEL COSTO</option>
                        @foreach ($contabilidadCostoTipos as $contabilidadCostoTipo)
                            <option value="{{ $contabilidadCostoTipo->id }}">{{ $contabilidadCostoTipo->nombre_costo }}
                            </option>
                        @endforeach
                    </x-select>
                    <x-input-error for="tipoCostoId" />
                </div>

                {{-- Fecha --}}
                <div>
                    <x-label for="fecha" value="Fecha" />
                    <x-input type="date" wire:model="fecha" />
                    <x-input-error for="fecha" />
                </div>

                {{-- Valor --}}
                <div>
                    <x-label for="valor" value="Valor" />
                    <x-input type="number" step="0.01" wire:model="valor" />
                    <x-input-error for="valor" />
                </div>
            </div>

            {{-- Campos --}}
            <div class="mt-4">
                <x-label value="Campos" />
                <div class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-8 gap-2">
                    @foreach ($campos as $campo)
                        <label>
                            <input type="checkbox" wire:model="camposSeleccionados" value="{{ $campo->nombre }}">
                            {{ $campo->nombre }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Botón --}}
            <x-flex class="justify-end w-full">
                <div class="mt-4">
                    <x-button wire:click="agregarRegistroCosto">
                        <i class="fa fa-save"></i> Agregar Costo
                    </x-button>
                </div>
            </x-flex>
        </x-spacing>
    </x-card>
    <x-h3 class="mt-4">
        Lista de Costos Fijos y Operativos
    </x-h3>
    <x-card class="mt-3">
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">
                            N°
                        </x-th>
                        <x-th class="text-center">
                            Fecha
                        </x-th>
                        <x-th class="text-center">
                            Tipo
                        </x-th>
                        <x-th>
                            Nombre
                        </x-th>
                        <x-th class="text-center">
                            Valor
                        </x-th>
                        <x-th class="text-center">
                            Campos
                        </x-th>
                        <x-th class="text-center">

                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($contabilidadCostoRegistros)
                        @foreach ($contabilidadCostoRegistros as $indice => $contabilidadCostoRegistro)
                            <x-tr>
                                <x-td class="text-center">
                                    {{ $indice + 1 }}
                                </x-td>
                                <x-td class="text-center">
                                    {{ $contabilidadCostoRegistro->fecha }}
                                </x-td>
                                <x-td class="text-center">
                                    {{ mb_strtoupper($contabilidadCostoRegistro->tipoCosto->tipo_costo) }}
                                </x-td>
                                <x-td>
                                    {{ $contabilidadCostoRegistro->tipoCosto->nombre_costo }}
                                </x-td>

                                <x-td class="text-center">
                                    {{ $contabilidadCostoRegistro->valor }}
                                </x-td>
                                <x-td class="text-center">
                                    {{ implode(',', $contabilidadCostoRegistro->detalles->pluck('campo')->toArray()) }}
                                </x-td>
                                <x-td class="text-center">
                                    @if (!$contabilidadCostoRegistroId)
                                        <x-flex class="justify-end w-full">
                                          
                                            <x-danger-button type="button"
                                                wire:click="preguntarEliminarContabilidadCostoRegistro({{ $contabilidadCostoRegistro->id }})">
                                                <i class="fa fa-trash"></i> Eliminar
                                            </x-danger-button>
                                        </x-flex>
                                    @endif
                                </x-td>
                            </x-tr>
                        @endforeach
                    @else
                        <p>No hay resultados aún.</p>
                    @endif

                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>
</div>
