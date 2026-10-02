<div>
    <x-dialog-modal wire:model="mostrar" maxWidth="full">
        <x-slot name="title">{{ $movimientoId ? 'Editar movimiento de caja' : 'Nuevo movimiento de caja' }}</x-slot>
        <x-slot name="content">
            @if ($opciones)
                @php
                    $clasificadores = collect($opciones['clasificadores'])->where('tipo', $tipo)->values();
                    $actual = collect($opciones['clasificadores'])->firstWhere('id', (int) $caja_clasificador_id);
                @endphp
                <form wire:submit="guardar" id="frmCajaMovimiento" class="space-y-4" wire:key="frm-caja-{{ $tipo }}-{{ $movimientoId ?? 'nuevo' }}"
                    x-data="{
                        lista: @js($clasificadores),
                        c1: @js($actual['clasificador_1'] ?? ''),
                        get grupos() { return [...new Set(this.lista.map(c => c.clasificador_1))]; },
                        get segundos() { return this.lista.filter(c => c.clasificador_1 === this.c1); },
                    }">

                    {{-- Tipo, fecha y caja --}}
                    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                        <div class="col-span-2">
                            <x-label>Tipo</x-label>
                            <div class="flex rounded-md border border-input overflow-hidden h-9">
                                <button type="button" wire:click="$set('tipo', 'INGRESO')"
                                    class="flex-1 text-sm font-semibold {{ $tipo === 'INGRESO' ? 'bg-green-600 text-white' : 'bg-background text-foreground' }}">Ingreso</button>
                                <button type="button" wire:click="$set('tipo', 'EGRESO')"
                                    class="flex-1 text-sm font-semibold {{ $tipo === 'EGRESO' ? 'bg-red-600 text-white' : 'bg-background text-foreground' }}">Egreso</button>
                            </div>
                        </div>
                        <x-input type="date" label="Fecha" wire:model.live="fecha" error="fecha" />
                        <x-select label="Semana" wire:model="semana" error="semana">
                            @foreach (range(1, 5) as $s)
                                <option value="{{ $s }}">SEM-{{ $s }}</option>
                            @endforeach
                        </x-select>
                        <x-input type="number" label="N° de caja" wire:model="numero_caja" error="numero_caja" :disabled="$es_contable" />
                        <x-select label="Condición" wire:model="condicion" error="condicion" :disabled="$es_contable">
                            <option value="NEG">NEG.</option>
                            <option value="BLA">BLA.</option>
                        </x-select>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model.live="es_contable" class="rounded">
                        Viene de la caja contable (sin N° de caja; siempre BLA.)
                    </label>

                    {{-- Quién y qué --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <x-input label="Beneficiario" wire:model="beneficiario" list="caja-beneficiarios" error="beneficiario" />
                            <datalist id="caja-beneficiarios">
                                @foreach ($opciones['beneficiarios'] as $b)
                                    <option value="{{ $b }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="md:col-span-2">
                            <x-input label="Gastos B+N (descripción)" wire:model="descripcion" error="descripcion" />
                        </div>
                    </div>

                    {{-- Clasificación --}}
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="md:col-span-2">
                            <x-label>Clasificador 1</x-label>
                            <select x-model="c1" @change="$wire.set('caja_clasificador_id', null)"
                                class="w-full h-9 px-2 rounded-md border border-input bg-background text-foreground text-sm">
                                <option value="">— Elegir —</option>
                                <template x-for="g in grupos" :key="g">
                                    <option :value="g" x-text="g" :selected="g === c1"></option>
                                </template>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <x-label>Clasificador 2</x-label>
                            <select wire:model="caja_clasificador_id"
                                class="w-full h-9 px-2 rounded-md border border-input bg-background text-foreground text-sm">
                                <option value="">— Elegir —</option>
                                <template x-for="c in segundos" :key="c.id">
                                    <option :value="c.id" x-text="c.clasificador_2" :selected="c.id == $wire.caja_clasificador_id"></option>
                                </template>
                            </select>
                            @error('caja_clasificador_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input label="Sub-grupo NG" wire:model="subgrupo_ng" list="caja-subgrupos-ng" />
                            <datalist id="caja-subgrupos-ng">
                                @foreach ($opciones['subgrupos_ng'] as $s)
                                    <option value="{{ $s }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <x-input label="Sub-grupo BL" wire:model="subgrupo_bl" list="caja-subgrupos-bl" />
                            <datalist id="caja-subgrupos-bl">
                                @foreach ($opciones['subgrupos_bl'] as $s)
                                    <option value="{{ $s }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <x-input label="Categoría" wire:model="categoria" list="caja-categorias" placeholder="F/E001-208, Contable…" />
                            <datalist id="caja-categorias">
                                @foreach (array_slice($opciones['categorias'], 0, 200) as $s)
                                    <option value="{{ $s }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <x-input label="Código" wire:model="codigo" />
                    </div>

                    {{-- Documento --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <x-input label="T. Doc (o cualquier observación)" wire:model="tipo_documento" />
                        <x-input label="N° Doc (recibo, rendición, concepto…)" wire:model="numero_documento" />
                        <x-input label="Situación cheque (OP, depósito…)" wire:model="situacion_cheque" />
                    </div>

                    {{-- Importe --}}
                    <div class="rounded-lg border border-border p-3 space-y-3">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model.live="pagado_en_dolares" class="rounded"> Se pagó en dólares
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                            @if ($pagado_en_dolares)
                                <x-input type="number" step="0.01" label="Importe US$" wire:model.live.debounce.400ms="importe_usd" error="importe_usd" />
                                <x-input type="number" step="0.0001" label="TC del pago" wire:model.live.debounce.400ms="tipo_cambio_operacion" error="tipo_cambio_operacion" />
                            @else
                                <div class="md:col-span-2">
                                    <x-input label="Importe S/ (número u operación: 100-15+3.5)" wire:model.live.debounce.400ms="importe_texto" error="importe_texto" />
                                </div>
                            @endif
                            <div>
                                <span class="text-xs text-muted-foreground block">Se registrará</span>
                                <span class="text-lg font-bold tabular-nums {{ $tipo === 'EGRESO' ? 'text-red-600' : 'text-green-600' }}">
                                    @if ($this->importeCalculado !== null)
                                        {{ $tipo === 'EGRESO' ? '−' : '+' }} S/ {{ number_format($this->importeCalculado, 2) }}
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                            <x-input type="number" step="0.0001" label="TC del día (dolarizado)" wire:model="tipo_cambio" />
                        </div>
                    </div>
                </form>
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrar', false)">Cancelar</x-button>
            <x-button type="submit" form="frmCajaMovimiento"><i class="fa fa-save"></i> Guardar</x-button>
        </x-slot>
    </x-dialog-modal>
</div>
