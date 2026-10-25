<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <x-table>
        <x-slot name="thead">
            <x-tr>
                <x-th>Cargo</x-th>
                <x-th>Inicio</x-th>
                <x-th>Fin</x-th>
                <x-th>Motivo</x-th>
                <x-th>Estado</x-th>
                <x-th class="text-right">Acciones</x-th>
            </x-tr>
        </x-slot>
        <x-slot name="tbody">
            @forelse ($historial as $registro)
                <x-tr wire:key="cargo-{{ $registro->id }}">
                    <x-td class="font-medium">{{ $registro->cargo->nombre }}</x-td>
                    <x-td>{{ $registro->fecha_inicio->format('m/Y') }}</x-td>
                    <x-td>{{ $registro->fecha_fin?->format('m/Y') ?? '—' }}</x-td>
                    <x-td class="text-xs">{{ ucfirst($registro->motivo_cambio ?? '—') }}</x-td>
                    <x-td>
                        @if (is_null($registro->fecha_fin))
                            <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Vigente</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-xs bg-muted text-muted-foreground">Finalizado</span>
                        @endif
                    </x-td>
                    <x-td class="text-right">
                        @if (is_null($registro->fecha_fin))
                            <x-button size="xs" variant="danger" wire:click="eliminarCargoAbierto"
                                wire:confirm="¿Eliminar este registro de cargo? Es la asignación vigente.">
                                <i class="fa fa-trash"></i> Eliminar
                            </x-button>
                        @elseif ($historial->first()?->id === $registro->id)
                            <x-button size="xs" variant="secondary" wire:click="reabrirCargo({{ $registro->id }})"
                                wire:confirm="¿Reaperturar este cargo como vigente?">
                                <i class="fa fa-refresh"></i> Reaperturar
                            </x-button>
                        @endif
                    </x-td>
                </x-tr>
            @empty
                <x-tr>
                    <x-td colspan="6" class="py-6 text-center text-muted-foreground">Este empleado no tiene cargos registrados.</x-td>
                </x-tr>
            @endforelse
        </x-slot>
    </x-table>

    <div class="rounded-lg border border-border p-4 space-y-3">
        @if ($cargoVigente)
            <x-warning class="text-sm">
                Cargo vigente: <strong>{{ $cargoVigente->cargo->nombre }}</strong> desde
                {{ $cargoVigente->fecha_inicio->format('m/Y') }}. Para asignar un nuevo cargo, primero finalízalo.
            </x-warning>
            <div class="flex items-end gap-3">
                <x-input type="month" label="Mes de fin" wire:model="mesFin" error="mesFin" class="w-48" />
                <x-button wire:click="finalizarCargoActual">Finalizar cargo actual</x-button>
            </div>
        @else
            <x-subtitle>Asignar nuevo cargo</x-subtitle>
            <x-select label="Cargo" wire:model="planCargoId" error="planCargoId">
                <option value="">Seleccionar cargo</option>
                @foreach ($cargos as $c)
                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                @endforeach
            </x-select>
            <x-input type="month" label="Mes de inicio de vigencia" wire:model="mesInicio" error="mesInicio" />
            <x-input label="Grupo (opcional)" wire:model="grupoCodigo" error="grupoCodigo" />
            <x-select label="Motivo" wire:model="motivoCambio" error="motivoCambio">
                <option value="ingreso">Ingreso</option>
                <option value="ascenso">Ascenso</option>
                <option value="rotacion">Rotación</option>
                <option value="reactivacion">Reactivación tras ausencia</option>
            </x-select>
            <div class="flex justify-end">
                <x-button wire:click="asignarCargo"><i class="fa fa-save"></i> Asignar cargo</x-button>
            </div>
        @endif
    </div>
</div>
