{{-- Modal "Corregir fechas de la campaña" (DESIGN.md): inicio / cierre / reabrir, vecina pegada y motivo --}}
@php
    $f = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : 'abierta';
    $input = 'h-9 w-full rounded-[10px] border border-mist bg-paper px-3 text-sm text-ink focus:border-carmin focus:outline-none focus:ring-[3px] focus:ring-carmin/25 disabled:bg-fog disabled:text-pewter';
@endphp
<div>
    <x-dialog-modal wire:model="mostrar" maxWidth="xl">
        <x-slot name="title">Corregir fechas de la campaña</x-slot>
        <x-slot name="content">
            @if ($campaniaId)
                <div class="space-y-4 text-sm">
                    <div class="rounded-[10px] border border-mist bg-fog px-3 py-2">
                        <p class="font-medium text-ink">{{ $actual['nombre'] }} · campo {{ $actual['campo'] }}</p>
                        <p class="text-graphite">Hoy: {{ $f($actual['inicio']) }} – {{ $f($actual['fin']) }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block font-medium text-ink">Fecha de inicio</span>
                            <input type="date" wire:model.live="inicio" class="{{ $input }}" />
                        </label>
                        <label class="block">
                            <span class="mb-1 block font-medium text-ink">Fecha de cierre</span>
                            <input type="date" wire:model.live="fin" class="{{ $input }}" @disabled($reabrir) />
                        </label>
                    </div>

                    <div class="space-y-2">
                        @if ($actual['fin'])
                            <label class="flex items-start gap-2">
                                <input type="checkbox" wire:model.live="reabrir" class="mt-0.5 rounded border-mist text-carmin focus:ring-carmin/25" />
                                <span><span class="font-medium text-ink">Reabrir la campaña</span>
                                    <span class="block text-xs text-pewter">Quita la fecha de cierre. Solo si es la última campaña del campo.</span></span>
                            </label>
                        @endif
                        <label class="flex items-start gap-2">
                            <input type="checkbox" wire:model.live="moverVecinas" class="mt-0.5 rounded border-mist text-carmin focus:ring-carmin/25" />
                            <span><span class="font-medium text-ink">Mover también la campaña vecina si estaba pegada</span>
                                <span class="block text-xs text-pewter">Si la siguiente empezaba el día después del cierre (o la anterior terminaba el día antes del inicio), se mueve para que sigan pegadas.</span></span>
                        </label>
                    </div>

                    @error('fechas')
                        <p class="rounded-[6px] bg-red-50 px-3 py-2 text-red-700 dark:bg-red-400/10 dark:text-red-400"><i class="fa fa-circle-exclamation"></i> {{ $message }}</p>
                    @enderror

                    {{-- Vista previa --}}
                    @if ($cambios)
                        <div class="rounded-[10px] border border-mist">
                            <p class="border-b border-hairline bg-fog px-3 py-1.5 text-xs font-medium text-graphite">Al guardar</p>
                            <ul class="divide-y divide-hairline">
                                @foreach ($cambios as $c)
                                    <li class="px-3 py-2">
                                        <span class="font-medium text-ink">{{ $c['nombre'] }}{{ $c['principal'] ? '' : ' (vecina)' }}:</span>
                                        <span class="text-graphite">{{ $f($c['antes'][0]) }} – {{ $f($c['antes'][1]) }}</span>
                                        <i class="fa fa-arrow-right mx-1 text-xs text-steel"></i>
                                        <span class="font-medium text-ink">{{ $f($c['despues'][0]) }} – {{ $f($c['despues'][1]) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @foreach ($vecinas as $lado => $v)
                        @if (!$v['se_mueve'])
                            <p class="rounded-[6px] bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">
                                {{ $v['nombre'] }} ({{ $lado }}) no se moverá: quedarán días entre las dos campañas.
                            </p>
                        @endif
                    @endforeach
                    @if ($avisos)
                        <ul class="list-disc space-y-0.5 rounded-[6px] bg-amber-50 py-2 pl-7 pr-3 text-xs text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">
                            @foreach ($avisos as $a)
                                <li>{{ $a }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <label class="block">
                        <span class="mb-1 block font-medium text-ink">Motivo de la corrección *</span>
                        <input type="text" wire:model="motivo" class="{{ $input }}" placeholder="Ej.: se cerró con el 05/02 y era el 06/02" />
                        @error('motivo') <span class="mt-1 block text-xs text-red-700 dark:text-red-400">{{ $message }}</span> @enderror
                        <span class="mt-1 block text-xs text-pewter">Queda en la historia y en la auditoría de la campaña.</span>
                    </label>
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrar', false)">Cancelar</x-button>
            <x-button wire:click="guardar" wire:loading.attr="disabled" wire:target="guardar" class="ml-2" :disabled="$sinCambio || $errors->has('fechas')">
                <i class="fa fa-check"></i> Guardar corrección
            </x-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading wire:target="guardar" />
</div>
