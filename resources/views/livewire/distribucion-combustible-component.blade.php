<div>
    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center gap-3">
                Distribución de combustible
                @if ($maquinaria)
                    <span
                        class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-foreground">
                        <i class="fa fa-truck"></i>
                        {{ $maquinaria->nombre }}
                    </span>
                @endif
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="flex gap-5 items-start min-h-[400px]">

                {{-- ── PANEL IZQUIERDO: Formulario ─────────────────── --}}
                <aside
                    class="w-[300px] shrink-0 flex flex-col self-stretch border border-border rounded-xl overflow-hidden">

                    <div
                        class="flex items-center gap-2 px-4 py-3 text-xs font-bold uppercase tracking-widest bg-muted text-muted-foreground border-b border-border">
                        <i class="fa fa-plus-circle"></i>
                        Nueva distribución
                    </div>

                    <div class="flex flex-col gap-3.5 p-4 flex-1 overflow-y-auto">
                        <div>
                            <x-selector-dia label="Fecha" wire:model="fecha" />
                            <x-input-error for="fecha" />
                        </div>

                        <div>
                            <x-select-campo wire:model="campo" label="Campo" error="campo" />
                        </div>

                        <div class="flex gap-2">
                            <div class="flex-1">
                                <x-label for="horaInicio" value="Hora inicio" />
                                <x-input type="time" wire:model="horaInicio" class="w-full" />
                                <x-input-error for="horaInicio" />
                            </div>
                            <div class="flex-1">
                                <x-label for="horaFin" value="Hora fin" />
                                <x-input type="time" wire:model="horaFin" class="w-full" />
                                <x-input-error for="horaFin" />
                            </div>
                        </div>

                        <div>
                            <x-label for="descripcion" value="Descripción del trabajo" />
                            <x-textarea wire:model="descripcion" class="w-full"
                                placeholder="E.g., Recogida de infestadores mallita"></x-textarea>
                            <x-input-error for="descripcion" />
                        </div>
                    </div>

                    <div class="px-4 py-3 border-t border-border bg-muted">
                        <x-button wire:click="guardarDistribucion" class="w-full justify-center">
                            <i class="fa fa-save mr-1.5"></i>
                            Guardar distribución
                        </x-button>
                    </div>

                </aside>

                {{-- ── PANEL DERECHO: Tabla ─────────────────────────── --}}
                <div class="flex-1 min-w-0 flex flex-col border border-border rounded-xl overflow-hidden">

                    <div
                        class="flex items-center gap-2 px-4 py-3 text-xs font-bold uppercase tracking-widest bg-muted text-muted-foreground border-b border-border shrink-0">
                        <i class="fa fa-list"></i>
                        Distribuciones registradas
                    </div>

                    <div class="overflow-x-auto overflow-y-auto max-h-[520px]">
                        <x-table class="w-full">
                            <x-slot name="thead">
                                <x-tr>
                                    <x-th></x-th>
                                    <x-th>FECHA</x-th>
                                    <x-th>HORA INICIO</x-th>
                                    <x-th>HORA SALIDA</x-th>
                                    <x-th>TOTAL HORAS</x-th>
                                    <x-th>CAMPO</x-th>
                                    <x-th>CANT. COMB.</x-th>
                                    <x-th>COSTO COMB. S/.</x-th>
                                    <x-th>INGRESO</x-th>
                                    <x-th>LABOR</x-th>
                                    <x-th>TRACTOR</x-th>
                                    <x-th>PRECIO</x-th>
                                    <x-th>RATIO</x-th>
                                    <x-th>VALOR/COSTO</x-th>
                                </x-tr>
                            </x-slot>
                            <x-slot name="tbody">
                                @foreach ($listaSalidas as $salida)
                                    {{-- Fila cabecera de salida --}}
                                    <x-tr class="bg-blue-100 dark:bg-blue-900/40">
                                        <x-td></x-td>
                                        <x-td class="font-semibold text-sm">{{ $salida->fecha_reporte }}</x-td>
                                        <x-td></x-td>
                                        <x-td></x-td>
                                        <x-td></x-td>
                                        <x-td></x-td>
                                        <x-td></x-td>
                                        <x-td></x-td>
                                        <x-td class="font-bold text-red-500">{{ $salida->cantidad }}</x-td>
                                        <x-td></x-td>
                                        <x-td
                                            class="font-semibold text-sm">{{ $salida->maquinaria?->nombre ?? 'N/A' }}</x-td>
                                        <x-td
                                            class="font-semibold text-sm">{{ 'S/. ' . formatear_numero($salida->costo_por_kg) }}</x-td>
                                        <x-td></x-td>
                                        <x-td
                                            class="font-semibold text-sm">{{ 'S/. ' . formatear_numero($salida->total_costo) }}</x-td>
                                        {{-- sin botón en fila de salida --}}
                                    </x-tr>

                                    {{-- Filas de distribuciones del grupo --}}
                                    @foreach ($salida['distribuciones'] as $dist)
                                        <x-tr class="bg-muted text-sm">
                                            <x-td>
                                                <x-button variant="danger"
                                                    wire:click="eliminarDistribucion({{ $dist->id }})"
                                                    wire:confirm="¿Eliminar esta distribución?">
                                                    <i class="fa fa-remove"></i> Quitar
                                                </x-button>
                                            </x-td>
                                            <x-td>{{ formatear_fecha($dist->fecha) }}</x-td>
                                            <x-td>{{ formatear_tiempo($dist->hora_inicio) }}</x-td>
                                            <x-td>{{ formatear_tiempo($dist->hora_salida) }}</x-td>
                                            <x-td>{{ $dist->horas }}</x-td>
                                            <x-td>{{ $dist->campo }}</x-td>
                                            <x-td>{{ $dist->cantidad_combustible }}</x-td>
                                            <x-td>{{ $dist->costo_maquinaria }}</x-td>
                                            <x-td></x-td>
                                            <x-td class="!text-left">{{ $dist->actividad }}</x-td>
                                            <x-td>{{ $dist->maquinaria_nombre }}</x-td>
                                            <x-td></x-td>
                                            <x-td>{{ $dist->ratio }}</x-td>
                                            <x-td>{{ formatear_numero($dist->valor_costo) }}</x-td>

                                        </x-tr>
                                    @endforeach
                                @endforeach
                            </x-slot>
                        </x-table>
                        <div class="mt-4">
                            <x-warning>
                                No tienes costo por kg?, es probable que su Kardex de este combustible no haya sido
                                creado, debes un kardex de este producto y seguir los pasos indicados
                                <x-button href="{{ route('gestion_insumos.kardex') }}">
                                    Ir a Kardex
                                </x-button>
                            </x-warning>
                        </div>

                    </div>

                </div>

            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                Cerrar
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>
