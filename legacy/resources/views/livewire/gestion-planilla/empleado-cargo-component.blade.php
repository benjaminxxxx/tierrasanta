<div>
    <x-dialog-modal wire:model.live="mostrarModal" maxWidth="2xl">
        <x-slot name="title">
            Gestionar Cargo - {{ $empleadoNombre }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-6">
                {{-- Historial --}}
                <x-table>
                    <x-slot name="thead">
                        <x-tr>
                            <x-th>Cargo</x-th>
                            <x-th>Inicio</x-th>
                            <x-th>Fin</x-th>
                            <x-th>Estado</x-th>
                            <x-th class="text-right">Acciones</x-th>
                        </x-tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @forelse ($historial as $registro)
                            <x-tr wire:key="hist-{{ $registro->id }}"
                                class="{{ is_null($registro->fecha_fin) ? 'bg-green-50 dark:bg-green-950' : '' }}">
                                <x-th>{{ $registro->cargo->nombre }}</x-th>
                                <x-th>{{ $registro->fecha_inicio->format('m/Y') }}</x-th>
                                <x-th>{{ $registro->fecha_fin?->format('m/Y') ?? '—' }}</x-th>
                                <x-th>
                                    @if (is_null($registro->fecha_fin))
                                        <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Vigente</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600">Finalizado</span>
                                    @endif
                                </x-th>
                                <x-th class="text-right">
                                    @if (is_null($registro->fecha_fin))
                                        <x-button variant="danger" wire:click="eliminarCargoAbierto"
                                            wire:confirm="¿Eliminar este registro de cargo? Es la asignación vigente.">
                                            <i class="fa fa-trash"></i> Eliminar
                                        </x-button>
                                    @elseif ($historial->first()?->id === $registro->id)
                                        <x-button variant="secondary" wire:click="reabrirCargo({{ $registro->id }})"
                                            wire:confirm="¿Reaperturar este cargo como vigente?">
                                            <i class="fa fa-refresh"></i> Reaperturar
                                        </x-button>
                                    @endif
                                </x-th>
                            </x-tr>
                        @empty
                            <x-tr>
                                <x-th colspan="5" class="py-6 text-center text-gray-400">
                                    Este empleado no tiene cargos registrados.
                                </x-th>
                            </x-tr>
                        @endforelse
                    </x-slot>
                </x-table>

                {{-- Acción según estado --}}
                @if ($cargoVigente)
                    <x-card class="space-y-3">
                        <x-warning class="text-sm">
                            El empleado tiene el cargo vigente <strong>{{ $cargoVigente->cargo->nombre }}</strong> desde
                            {{ $cargoVigente->fecha_inicio->format('m/Y') }}. Para asignar un nuevo cargo, primero debe
                            finalizar este.
                        </x-warning>

                        <div class="flex items-end gap-3">
                            <x-input type="month" label="Mes de fin" wire:model="mesFin" error="mesFin" class="w-48" />
                            <x-button wire:click="finalizarCargoActual">
                                Finalizar cargo actual
                            </x-button>
                        </div>
                    </x-card>
                @else
                    <x-card class="space-y-3">
                        <p class="text-sm font-medium">Asignar nuevo cargo</p>

                        <x-select label="Cargo" wire:model="planCargoId" fullWidth="true" error="planCargoId">
                            <option value="">Seleccionar cargo</option>
                            @foreach ($cargos as $c)
                                <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                            @endforeach
                        </x-select>

                        <x-input type="month" label="Mes de inicio de vigencia" wire:model="mesInicio" error="mesInicio"
                            class="w-full" />

                        <x-input label="Grupo (opcional)" wire:model="grupoCodigo" error="grupoCodigo" class="w-full" />

                        <x-select label="Motivo" wire:model="motivoCambio" fullWidth="true" error="motivoCambio">
                            <option value="ingreso">Ingreso</option>
                            <option value="ascenso">Ascenso</option>
                            <option value="rotacion">Rotación</option>
                            <option value="reactivacion">Reactivación tras ausencia</option>
                        </x-select>


                    </x-card>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarModal', false)" wire:loading.attr="disabled">
                Cerrar
            </x-button>
            <x-button wire:click="asignarCargo">
                Asignar cargo
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>