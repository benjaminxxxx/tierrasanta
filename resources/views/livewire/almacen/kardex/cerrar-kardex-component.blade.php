<x-dialog-modal wire:model.live="mostrarModal" maxWidth="full">
    <x-slot name="title">
        @if ($modo === 'cerrar')
            Cerrar Kardex — {{ $kardex?->producto?->nombre_comercial }} ({{ $kardex?->anio }})
        @else
            Reabrir Kardex — {{ $kardex?->producto?->nombre_comercial }} ({{ $kardex?->anio }})
        @endif
    </x-slot>

    <x-slot name="content">
        @if ($kardex)
            <div class="space-y-5">

                {{-- ── DATOS DEL KARDEX ACTUAL ─────────────────────────────── --}}
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="text-muted-foreground">Producto:</span> {{ $kardex->producto->nombre_comercial }}</div>
                    <div><span class="text-muted-foreground">Tipo Kardex:</span> {{ strtoupper($kardex->tipo) }}</div>
                    <div><span class="text-muted-foreground">Año:</span> {{ $kardex->anio }}</div>
                    <div><span class="text-muted-foreground">Estado actual:</span>
                        <span class="font-semibold {{ $kardex->estado === 'cerrado' ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ ucfirst($kardex->estado) }}
                        </span>
                    </div>
                </div>

                {{-- ── SALDOS FINALES RESALTADOS ───────────────────────────── --}}
                <div class="border-2 border-emerald-500/40 bg-emerald-500/5 rounded-lg p-4">
                    <p class="text-xs uppercase font-bold text-emerald-600 mb-2">
                        Saldo final de {{ $kardex->anio }} — se usará como saldo inicial del siguiente periodo
                    </p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs text-muted-foreground block">Stock final</span>
                            <span class="text-xl font-bold">{{ number_format($kardex->stock_final, 3) }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-muted-foreground block">Costo total final</span>
                            <span class="text-xl font-bold">{{ number_format($kardex->costo_final, 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- ── ESTADO DEL KARDEX DEL AÑO SIGUIENTE ─────────────────── --}}
                @if (!$kardexSiguiente)
                    <div class="border border-amber-500/40 bg-amber-500/10 rounded-lg p-4 space-y-3">
                        <p class="text-sm">
                            No se ha creado el kardex del año <strong>{{ $kardex->anio + 1 }}</strong>
                            para este producto y tipo de kardex.
                            ¿Desea aperturarlo con los datos de este kardex?
                        </p>
                        <x-button size="sm" wire:click="aperturarSiguiente" wire:loading.attr="disabled">
                            Aperturar {{ $kardex->anio + 1 }} con estos saldos
                        </x-button>
                    </div>
                @else
                    <div class="border rounded-lg p-4 space-y-3 {{ $comparacion['coincide'] ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-red-500/40 bg-red-500/10' }}">
                        <p class="text-sm">
                            Ya existe un kardex para <strong>{{ $kardexSiguiente->anio }}</strong>
                            (estado: {{ ucfirst($kardexSiguiente->estado) }}).
                        </p>

                        @if ($comparacion['coincide'])
                            <p class="text-sm text-emerald-700">
                                ✓ Sus saldos iniciales ya coinciden con el saldo final de este periodo.
                            </p>
                        @else
                            <p class="text-sm text-red-700 font-medium">
                                ⚠ Los saldos iniciales de {{ $kardexSiguiente->anio }} no coinciden con el saldo final de {{ $kardex->anio }}.
                            </p>
                            <div class="grid grid-cols-2 gap-4 text-xs">
                                <div class="border rounded p-2">
                                    <p class="text-muted-foreground mb-1">Actual en {{ $kardexSiguiente->anio }}</p>
                                    <p>Stock: <strong>{{ number_format($comparacion['stock_inicial_siguiente'], 3) }}</strong></p>
                                    <p>Costo: <strong>{{ number_format($comparacion['costo_inicial_siguiente'], 2) }}</strong></p>
                                </div>
                                <div class="border rounded p-2 border-emerald-500/40">
                                    <p class="text-emerald-600 mb-1">Propuesto (saldo final de {{ $kardex->anio }})</p>
                                    <p>Stock: <strong>{{ number_format($comparacion['stock_final_actual'], 3) }}</strong></p>
                                    <p>Costo: <strong>{{ number_format($comparacion['costo_final_actual'], 2) }}</strong></p>
                                </div>
                            </div>
                            <x-button size="sm" variant="secondary" wire:click="actualizarSiguiente" wire:loading.attr="disabled">
                                Actualizar saldos iniciales de {{ $kardexSiguiente->anio }}
                            </x-button>
                        @endif
                    </div>
                @endif

            </div>
        @endif
    </x-slot>

    <x-slot name="footer">
        <x-button variant="secondary" wire:click="cerrarModal">Cancelar</x-button>

        @if ($modo === 'cerrar' && $kardex?->estado === 'activo')
            <x-button wire:click="confirmarCerrar" wire:loading.attr="disabled">
                Confirmar Cierre
            </x-button>
        @elseif ($modo === 'reabrir' && $kardex?->estado === 'cerrado')
            <x-button variant="danger" wire:click="confirmarReabrir" wire:loading.attr="disabled">
                Confirmar Reapertura
            </x-button>
        @endif
    </x-slot>
</x-dialog-modal>