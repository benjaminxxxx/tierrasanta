<div class="space-y-4">
    <x-card class="space-y-3">
        <div>
            <h3 class="font-semibold text-foreground">Ajustes PLAME</h3>
            <p class="text-sm text-muted-foreground">
                Montos puestos a mano en un concepto del PLAME de un trabajador: vacaciones calculadas aparte, cuadre de vida ley o SCTR contra
                la factura del seguro, redondeos… El monto ajustado reemplaza al calculado en el PLAME, el Excel, la ficha y los costos, y lo que
                depende de él se recalcula (p. ej. un 0118 distinto cambia la gratificación, CTS, descuentos, EsSalud y neto). El valor calculado
                se conserva como referencia. Las vacaciones personalizadas de "Vacaciones y bonos" son el ajuste del 0118.
            </p>
        </div>

        @can(\App\Constants\Permisos::PLANILLA_BLANCO_GESTIONAR)
            <div class="flex flex-wrap items-end gap-3">
                <div class="w-72">
                    <x-select wire:model.live="personalId" label="Trabajador" error="personalId">
                        <option value="">Seleccione</option>
                        @foreach ($personal as $p)
                            <option value="{{ $p->id }}">{{ $p->nombres }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="w-80">
                    <x-select wire:model.live="codigo" label="Concepto" error="codigo">
                        <option value="">Seleccione</option>
                        @foreach ($conceptos as $cod => $texto)
                            <option value="{{ $cod }}">{{ $texto }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="w-36">
                    <x-input type="number" step="0.01" min="0" wire:model="monto" label="Monto ajustado" error="monto" />
                    @if ($referencia !== null)
                        <p class="text-xs text-muted-foreground mt-0.5">Calculado: S/ {{ number_format($referencia, 2) }}</p>
                    @endif
                </div>
                <div class="flex-1 min-w-48">
                    <x-input wire:model="motivo" label="Motivo" placeholder="Ej: cuadre con la factura de vida ley" error="motivo" />
                </div>
                <x-button wire:click="guardar"><i class="fa fa-save"></i> Guardar ajuste</x-button>
                @if ($personalId || $codigo)
                    <x-button variant="secondary" wire:click="limpiar">Cancelar</x-button>
                @endif
            </div>
        @endcan
    </x-card>

    <x-card>
        <div class="overflow-x-auto">
            <x-table>
                <x-slot name="thead">
                    <tr>
                        <x-th>Trabajador</x-th>
                        <x-th>Concepto</x-th>
                        <x-th class="text-right">Calculado</x-th>
                        <x-th class="text-right">Ajustado</x-th>
                        <x-th class="text-right">Diferencia</x-th>
                        <x-th>Motivo</x-th>
                        <x-th></x-th>
                    </tr>
                </x-slot>
                <x-slot name="tbody">
                    @forelse ($ajustes as $a)
                        <x-tr wire:key="aj-{{ $a['personal_id'] }}-{{ $a['codigo'] }}">
                            <x-td>{{ $a['nombres'] }}</x-td>
                            <x-td>{{ $a['concepto'] }}</x-td>
                            <x-td class="text-right">{{ $a['calculado'] === null ? '—' : number_format($a['calculado'], 2) }}</x-td>
                            <x-td class="text-right font-semibold">{{ number_format($a['monto'], 2) }}</x-td>
                            <x-td class="text-right {{ ($a['diferencia'] ?? 0) == 0 ? '' : (($a['diferencia'] > 0) ? 'text-green-700 dark:text-green-400' : 'text-red-600') }}">
                                {{ $a['diferencia'] === null ? '—' : sprintf('%+.2f', $a['diferencia']) }}
                            </x-td>
                            <x-td class="text-sm text-muted-foreground">{{ $a['motivo'] }}</x-td>
                            <x-td class="text-right whitespace-nowrap">
                                @can(\App\Constants\Permisos::PLANILLA_BLANCO_GESTIONAR)
                                    <x-button size="sm" variant="secondary" wire:click="editar({{ $a['personal_id'] }}, '{{ $a['codigo'] }}')"><i class="fa fa-edit"></i></x-button>
                                    <x-button size="sm" variant="danger" wire:click="quitar({{ $a['personal_id'] }}, '{{ $a['codigo'] }}')"
                                        wire:confirm="¿Quitar el ajuste? El concepto vuelve al valor calculado."><i class="fa fa-trash"></i></x-button>
                                @endcan
                            </x-td>
                        </x-tr>
                    @empty
                        <x-tr>
                            <x-td colspan="7" class="text-center text-muted-foreground">Sin ajustes este mes: todo el PLAME es el calculado por el sistema.</x-td>
                        </x-tr>
                    @endforelse
                </x-slot>
            </x-table>
        </div>
    </x-card>

    <x-loading wire:loading wire:target="guardar,quitar" />
</div>
