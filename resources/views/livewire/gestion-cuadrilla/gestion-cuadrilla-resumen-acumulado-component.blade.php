<div>
    <x-dialog-modal maxWidth="full" wire:model="mostrarCuadroResumenCuadrilleroSemanal">
        <x-slot name="title">
            <span class="text-zinc-900 dark:text-zinc-100 font-semibold">Cuadro resumen</span>
        </x-slot>

        <x-slot name="content">
            <!-- Barra superior de controles -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 mb-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <x-label class="text-zinc-700 dark:text-zinc-300 font-medium">
                        ¿Hasta dónde se calcula el bono?
                    </x-label>
                    <x-selector-dia wire:model="fechaHastaBono" />
                </div>
                <x-button wire:click="recalcularResumenTramo" size="xs" variant="success" class="self-end sm:self-auto">
                    <i class="fa fa-sync mr-1"></i> Recalcular resumen
                </x-button>
            </div>

            <!-- Contenedor con Scroll (Sticky Header y Footer) -->
            <div class="relative max-h-[60vh] overflow-y-auto overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700 shadow-sm bg-white dark:bg-zinc-900">
                <table class="w-full text-sm text-left border-collapse">
                    <thead class="sticky top-0 z-20 bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 shadow-md">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold border-b border-zinc-200 dark:border-zinc-700">Descripción</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold border-b border-zinc-200 dark:border-zinc-700">Acumulación actual</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold border-b border-zinc-200 dark:border-zinc-700">Condición</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold border-b border-zinc-200 dark:border-zinc-700">Fecha</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold border-b border-zinc-200 dark:border-zinc-700">Recibo</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold border-b border-zinc-200 dark:border-zinc-700">Monto Pagado</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold border-b border-zinc-200 dark:border-zinc-700">Deuda acumulada</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700/60">
                        @php
                            $totalAPagarEnTramo = 0;
                            $totalPagadoAcumulado = 0;
                        @endphp
                        @forelse ($resumenes as $resumen)
                            @php
                                $montoPagado = $resumen['monto_pagado'] ?? 0;
                                if ($resumen['condicion'] == 'Pagado') {
                                    $totalAPagarEnTramo += $resumen['deuda_actual'];
                                }
                                $totalPagadoAcumulado += $montoPagado;
                            @endphp

                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="!py-1.5 px-4 font-semibold uppercase text-zinc-900 dark:text-zinc-100"
                                    style="background-color: {{ $resumen['color'] }}33; border-left: 4px solid {{ $resumen['color'] }}">
                                    {{ $resumen['descripcion_alias'] ?? $resumen['descripcion'] }}
                                </td>
                                <td class="!py-1.5 px-4 text-right text-zinc-800 dark:text-zinc-200 font-mono">
                                    {{ formatear_numero($resumen['deuda_actual']) }}
                                </td>
                                <td class="!py-1.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $resumen['condicion'] == 'Pagado' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300' }}">
                                        {{ $resumen['condicion'] }}
                                    </span>
                                </td>
                                <td class="!py-1.5 px-4 text-center text-zinc-600 dark:text-zinc-400">
                                    {{ $resumen['fecha_ultimo_pago'] ?? $resumen['fecha'] ?? '-' }}
                                </td>
                                <td class="!py-1.5 px-4 text-center text-zinc-600 dark:text-zinc-400">
                                    {{ $resumen['recibo'] ?? '-' }}
                                </td>
                                <td class="!py-1.5 px-4 text-right font-mono font-semibold {{ $montoPagado > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400 dark:text-zinc-500' }}">
                                    {{ $montoPagado > 0 ? formatear_numero($montoPagado) : '-' }}
                                </td>
                                <td class="!py-1.5 px-4 text-right text-zinc-800 dark:text-zinc-200 font-mono">
                                    {{ formatear_numero($resumen['deuda_acumulada']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                                    No hay datos para mostrar
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot class="sticky bottom-0 z-20 bg-zinc-100 dark:bg-zinc-800 font-bold border-t-2 border-zinc-300 dark:border-zinc-600 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]">
                        <tr>
                            <td class="px-4 py-3 text-right text-zinc-900 dark:text-zinc-100">Total</td>
                            <td class="px-4 py-3 text-right text-zinc-900 dark:text-zinc-100 font-mono">
                                {{ formatear_numero($totalAPagarEnTramo) }}
                            </td>
                            <td colspan="3"></td>
                            <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400 font-mono">
                                {{ $totalPagadoAcumulado > 0 ? formatear_numero($totalPagadoAcumulado) : '-' }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarCuadroResumenCuadrilleroSemanal', false)" wire:loading.attr="disabled">
                Aceptar
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>