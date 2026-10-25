{{-- Pensión y pago (DESIGN.md): partículas del empleado, solo "desde"; siguen al empleado entre contratos. --}}
@php
    $f = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '—';
    $btn2 = 'inline-flex h-8 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-xs font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btnLink = 'text-xs text-graphite underline-offset-4 hover:text-ink hover:underline';
    $btnPrincipal = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $cuenta = fn($banco, $moneda, $numero) => $numero ? trim(($banco ?? '') . ($moneda === 'USD' ? ' $' : '') . ' ' . $numero) : '—';
@endphp

<div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
    <div class="space-y-4">
        {{-- Pensión --}}
        <div class="rounded-[10px] border border-mist bg-paper p-4">
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-base font-semibold text-ink">Sistema de pensión</h3>
                    <p class="text-xs text-pewter">Hoy: <span class="font-medium text-ink">{{ $pensionActual?->plan_sp_codigo ?? 'Sin sistema de pensión' }}</span>
                        {{ $pensionActual?->esta_jubilado ? '· jubilado' : '' }}. Un cambio de AFP u ONP no cierra el contrato: agrega una fila desde el mes en que rige.</p>
                </div>
                <button type="button" wire:click="nuevaPension" class="{{ $btn2 }}"><i class="fa fa-plus"></i> Cambio</button>
            </div>
            <table class="w-full text-[13px]">
                <thead class="bg-fog text-left text-xs text-graphite">
                    <tr><th class="px-2 py-1.5 font-medium">Desde</th><th class="px-2 py-1.5 font-medium">Sistema</th><th class="px-2 py-1.5 font-medium">Jubilado</th><th class="px-2 py-1.5 font-medium">Susp. 4ta</th><th class="px-2 py-1.5 font-medium">Motivo</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($pensiones as $x)
                        <tr wire:key="pen-{{ $x->id }}" class="border-t border-hairline">
                            <td class="px-2 py-1.5 tabular-nums">{{ $f($x->desde) }}</td>
                            <td class="px-2 py-1.5">{{ $x->plan_sp_codigo ?? '—' }} <span class="text-pewter">{{ $x->descuento?->descripcion }}</span></td>
                            <td class="px-2 py-1.5">{{ $x->esta_jubilado ? 'Sí' : 'No' }}</td>
                            <td class="px-2 py-1.5">{{ $x->suspension_cuarta ? 'Sí' : 'No' }}</td>
                            <td class="px-2 py-1.5 text-graphite">{{ $x->motivo }}</td>
                            <td class="whitespace-nowrap px-2 py-1.5 text-right">
                                <button type="button" wire:click="editarPension({{ $x->id }})" class="text-steel hover:text-ink" title="Editar"><i class="fa fa-pen"></i></button>
                                <button type="button" wire:click="eliminar('pension', {{ $x->id }})" wire:confirm="¿Quitar esta fila?" class="ml-2 text-steel hover:text-red-700" title="Quitar"><i class="fa fa-xmark"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-2 py-2 text-xs text-amber-700 dark:text-amber-400"><i class="fa fa-triangle-exclamation"></i> Sin sistema de pensión: la planilla no podrá calcular su descuento.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Cuentas de pago --}}
        <div class="rounded-[10px] border border-mist bg-paper p-4">
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-base font-semibold text-ink">Cuentas de pago</h3>
                    <p class="text-xs text-pewter">La principal recibe lo de planilla (blanco); la secundaria, la diferencia.</p>
                </div>
                <button type="button" wire:click="nuevoPago" class="{{ $btn2 }}"><i class="fa fa-plus"></i> Cambio</button>
            </div>
            <table class="w-full text-[13px]">
                <thead class="bg-fog text-left text-xs text-graphite">
                    <tr><th class="px-2 py-1.5 font-medium">Desde</th><th class="px-2 py-1.5 font-medium">Método</th><th class="px-2 py-1.5 font-medium">Principal</th><th class="px-2 py-1.5 font-medium">Secundaria</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($pagos as $x)
                        <tr wire:key="pago-{{ $x->id }}" class="border-t border-hairline">
                            <td class="px-2 py-1.5 tabular-nums">{{ $f($x->desde) }}</td>
                            <td class="px-2 py-1.5">{{ $x->metodo_pago ? ucfirst($x->metodo_pago) : '—' }}</td>
                            <td class="px-2 py-1.5 tabular-nums">{{ $cuenta($x->banco, $x->moneda_cuenta, $x->numero_cuenta) }}</td>
                            <td class="px-2 py-1.5 tabular-nums">{{ $cuenta($x->banco_secundario, $x->moneda_cuenta_secundaria, $x->numero_cuenta_secundaria) }}</td>
                            <td class="whitespace-nowrap px-2 py-1.5 text-right">
                                <button type="button" wire:click="editarPago({{ $x->id }})" class="text-steel hover:text-ink" title="Editar"><i class="fa fa-pen"></i></button>
                                <button type="button" wire:click="eliminar('pago', {{ $x->id }})" wire:confirm="¿Quitar esta fila?" class="ml-2 text-steel hover:text-red-700" title="Quitar"><i class="fa fa-xmark"></i></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-2 py-2 text-xs text-pewter">Sin cuentas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Panel de edición --}}
    <div>
        @if ($panel)
            <div class="space-y-4 rounded-[10px] border border-mist bg-paper p-4 xl:sticky xl:top-2">
                <h4 class="text-base font-semibold text-ink">{{ $panel === 'pension' ? 'Sistema de pensión' : 'Cuenta de pago' }}</h4>
                <div class="grid grid-cols-2 gap-3">
                    <x-fecha label="Desde *" wire:model="p.desde" />
                    @if ($panel === 'pension')
                        <x-select-planilla-descuentos label="Sistema" textoTodos="Sin sistema" wire:model="p.plan_sp_codigo" />
                        <x-select label="Jubilado" wire:model="p.esta_jubilado"><option value="0">No</option><option value="1">Sí</option></x-select>
                        <x-select label="Suspensión de 4ta" wire:model="p.suspension_cuarta"><option value="0">No</option><option value="1">Sí</option></x-select>
                    @else
                        <x-select label="Método" wire:model="p.metodo_pago">
                            <option value="">—</option><option value="transferencia">Transferencia</option><option value="efectivo">Efectivo</option><option value="cheque">Cheque</option>
                        </x-select>
                        <x-input label="Banco (principal)" wire:model="p.banco" placeholder="BCP, BBVA…" />
                        <x-select label="Tipo de cuenta" wire:model="p.tipo_cuenta"><option value="">—</option><option value="ahorros">Ahorros</option><option value="corriente">Corriente</option></x-select>
                        <x-select label="Moneda" wire:model="p.moneda_cuenta"><option value="">—</option><option value="PEN">Soles</option><option value="USD">Dólares</option></x-select>
                        <x-input label="N° de cuenta (principal)" wire:model="p.numero_cuenta" class="col-span-2" />
                        <x-input label="Banco (diferencia)" wire:model="p.banco_secundario" />
                        <x-select label="Tipo de cuenta" wire:model="p.tipo_cuenta_secundaria"><option value="">—</option><option value="ahorros">Ahorros</option><option value="corriente">Corriente</option></x-select>
                        <x-select label="Moneda" wire:model="p.moneda_cuenta_secundaria"><option value="">—</option><option value="PEN">Soles</option><option value="USD">Dólares</option></x-select>
                        <x-input label="N° de cuenta (diferencia)" wire:model="p.numero_cuenta_secundaria" />
                    @endif
                    <x-input label="Motivo" wire:model="p.motivo" class="col-span-2" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="cerrarPanel" class="{{ $btn2 }}">Cancelar</button>
                    <button type="button" wire:click="guardar" class="{{ $btnPrincipal }}"><i class="fa fa-check"></i> Guardar</button>
                </div>
            </div>
        @else
            <div class="rounded-[10px] border border-dashed border-mist p-6 text-sm text-pewter">
                Elige "Cambio" o edita una fila para verla aquí.
            </div>
        @endif
    </div>

    <x-loading wire:loading wire:target="guardar,eliminar" />
</div>
