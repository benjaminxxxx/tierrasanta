<div class="space-y-4">
    <x-flex class="justify-between">
        <div>
            <x-title>Servicios en campo</x-title>
            <x-subtitle>Servicios externos (maquinaria con personal y combustible propios) asignados por campo y campaña</x-subtitle>
        </div>
        <x-flex>
            <x-button variant="secondary" wire:click="$toggle('mostrarResumenAnual')">
                <i class="fa fa-chart-bar"></i> {{ $mostrarResumenAnual ? 'Ocultar resumen anual' : 'Resumen anual' }}
            </x-button>
            <x-button @click="$wire.dispatch('crearServicioCampo')">
                <i class="fa fa-plus"></i> Agregar servicio
            </x-button>
        </x-flex>
    </x-flex>

    @if ($sinCampania->isNotEmpty())
        <x-callout variant="warning">
            <div class="w-full space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span>
                        {{ $sinCampania->count() }} registro(s) de servicio no tienen campaña vinculada
                        (posiblemente la campaña fue eliminada). Su costo no se está asignando a ninguna campaña.
                    </span>
                    <x-button size="sm" variant="warning" wire:click="reasignarCampanias">
                        <i class="fa fa-link"></i> Intentar vincular
                    </x-button>
                </div>
                <ul class="text-xs list-disc pl-5 max-h-32 overflow-y-auto">
                    @foreach ($sinCampania as $d)
                        <li>
                            {{ $d->fecha->format('d/m/Y') }} · {{ $d->campo }} · {{ $d->labor }} ·
                            {{ $d->servicio?->servicio }} · S/ {{ number_format($d->costo, 2) }}
                            <button type="button" class="underline ml-1"
                                @click="$wire.dispatch('editarServicioCampo', { id: {{ $d->servicio_campo_id }} })">editar</button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </x-callout>
    @endif

    @if ($mostrarResumenAnual)
        <livewire:campo.servicios-campo-resumen-anual-component />
    @endif

    <x-card class="space-y-4">
        <x-flex class="flex-wrap">
            <x-select-anios label="Año" wire:model.live="filtroAnio" class="w-auto" />
            <x-select-meses label="Mes" wire:model.live="filtroMes" class="w-auto" />
            <x-select label="Campo" wire:model.live="filtroCampo" class="w-auto">
                <option value="">-- Todos --</option>
                @foreach (\App\Models\Campo::orderBy('orden')->pluck('nombre') as $campo)
                    <option value="{{ $campo }}">{{ $campo }}</option>
                @endforeach
            </x-select>
            <x-input type="search" label="Servicio, labor o comprobante" wire:model.live.debounce.400ms="filtroTexto"
                class="w-auto" />
        </x-flex>
    </x-card>

    <x-card>
        <x-table>
            <x-slot name="thead">
                <x-tr>
                    <x-th>Fecha comp.</x-th>
                    <x-th>Servicio</x-th>
                    <x-th>Comprobante</x-th>
                    <x-th>Costo</x-th>
                    <x-th>Unitario</x-th>
                    <x-th>Subtotal</x-th>
                    <x-th>IGV</x-th>
                    <x-th>Total</x-th>
                    <x-th>Detalle por campo</x-th>
                    <x-th></x-th>
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                @forelse ($servicios as $s)
                    <x-tr wire:key="servicio-campo-{{ $s->id }}" class="align-top">
                        <x-td>{{ $s->fecha_comprobante->format('d/m/Y') }}</x-td>
                        <x-td class="!text-left font-semibold">{{ $s->servicio }}</x-td>
                        <x-td class="text-xs">
                            {{ $s->comprobante_nombre }}
                            @if ($s->numero_comprobante)
                                <span class="block text-muted-foreground">{{ $s->numero_comprobante }}</span>
                            @endif
                        </x-td>
                        <x-td>
                            <span @class([
                                'px-2 py-0.5 rounded text-xs font-semibold uppercase',
                                'bg-gray-100 text-gray-800 dark:bg-gray-200' => $s->tipo_costo === 'blanco',
                                'bg-gray-800 text-white dark:bg-black' => $s->tipo_costo === 'negro',
                            ])>{{ $s->tipo_costo }}</span>
                        </x-td>
                        <x-td class="text-xs whitespace-nowrap">
                            {{ number_format($s->costo_unitario, 2) }} / {{ $s->unidad }}
                            <span class="block text-muted-foreground">{{ $s->es_factura ? 'sin IGV' : 'con IGV' }}</span>
                        </x-td>
                        <x-td class="text-right">{{ number_format($s->subtotal, 2) }}</x-td>
                        <x-td class="text-right">{{ number_format($s->igv, 2) }}</x-td>
                        <x-td class="text-right font-semibold">{{ number_format($s->total, 2) }}</x-td>
                        <x-td class="!p-1">
                            <table class="w-full text-xs">
                                @foreach ($s->detalles as $d)
                                    <tr @class(['text-red-600 dark:text-red-400' => !$d->campania_id])>
                                        <td class="px-1">{{ $d->fecha->format('d/m') }}</td>
                                        <td class="px-1 font-semibold">{{ $d->campo }}</td>
                                        <td class="px-1">{{ $d->labor }}</td>
                                        <td class="px-1 text-right">{{ rtrim(rtrim(number_format($d->cantidad, 3), '0'), '.') }}</td>
                                        <td class="px-1 text-right">{{ number_format($d->costo, 2) }}</td>
                                        <td class="px-1 text-muted-foreground">
                                            {{ $d->campania?->nombre_campania ?? 'SIN CAMPAÑA' }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="font-semibold border-t border-border">
                                    <td class="px-1" colspan="3">Costo asignado</td>
                                    <td class="px-1 text-right">{{ rtrim(rtrim(number_format($s->cantidad_total, 3), '0'), '.') }}</td>
                                    <td class="px-1 text-right">{{ number_format($s->costo_total, 2) }}</td>
                                    <td></td>
                                </tr>
                            </table>
                        </x-td>
                        <x-td class="whitespace-nowrap">
                            <x-button size="xs" variant="secondary"
                                @click="$wire.dispatch('editarServicioCampo', { id: {{ $s->id }} })" title="Editar">
                                <i class="fa fa-edit"></i>
                            </x-button>
                            <x-button size="xs" variant="danger" wire:click="confirmarEliminacion({{ $s->id }})"
                                title="Eliminar">
                                <i class="fa fa-trash"></i>
                            </x-button>
                        </x-td>
                    </x-tr>
                @empty
                    <x-tr>
                        <x-td colspan="10" class="text-center text-muted-foreground">
                            No hay servicios registrados con los filtros seleccionados.
                        </x-td>
                    </x-tr>
                @endforelse
            </x-slot>
        </x-table>

        <div class="mt-4">
            {{ $servicios->links() }}
        </div>
    </x-card>

    <livewire:campo.servicio-campo-form-component />
    <x-loading wire:loading />
</div>
