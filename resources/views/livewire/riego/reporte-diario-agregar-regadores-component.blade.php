{{-- Agregar regadores a una fecha: habituales primero; cualquier planillero agrario con contrato o cuadrillero en días especiales --}}
@php
    $btn2 = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btnPrincipal = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25 disabled:opacity-50';
    $marcado = fn($id, $tipo) => collect($elegidos)->contains(fn($e) => $e['tipo'] === $tipo && (int) $e['id'] === (int) $id);
    $tipoTexto = ['empleados' => 'Planilla', 'cuadrilleros' => 'Cuadrilla'];
@endphp
<div>
    <x-dialog-modal wire:model="abierto" maxWidth="3xl">
        <x-slot name="title">Agregar regadores</x-slot>

        <x-slot name="content">
            <div class="space-y-5 font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">
                {{-- Fecha: se puede cambiar aquí sin perder lo elegido --}}
                <div class="flex flex-wrap items-end gap-3 rounded-[10px] border border-mist bg-fog px-4 py-3">
                    <div>
                        <x-label value="Fecha del riego" />
                        <x-input type="date" wire:model.live="fecha" class="mt-1" />
                    </div>
                    <p class="pb-2 text-sm text-graphite">
                        @if ($fecha)
                            {{ ucfirst(\Illuminate\Support\Carbon::parse($fecha)->translatedFormat('l d \d\e F Y')) }}
                        @endif
                    </p>
                </div>
                @if ($aviso)
                    <p class="text-sm text-amber-700 dark:text-amber-400"><i class="fa fa-triangle-exclamation"></i> {{ $aviso }}</p>
                @endif

                {{-- Habituales --}}
                <div>
                    <p class="text-sm font-semibold text-ink">Regadores habituales</p>
                    <p class="mb-2 text-xs text-pewter">Los que más regaron en los últimos {{ \App\Services\Riego\Regador\RiegoRegadorConsulta::DIAS_HABITUALES }} días. Marca los que riegan hoy.</p>
                    @if ($habituales)
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach ($habituales as $h)
                                @php $on = $marcado($h['id'], $h['tipo']); @endphp
                                <button type="button" wire:key="hab-{{ $h['tipo'] }}-{{ $h['id'] }}" wire:click="alternar({{ $h['id'] }}, '{{ $h['tipo'] }}')"
                                    class="flex items-center gap-3 rounded-[10px] border px-3 py-2 text-left transition-colors focus:outline-none focus:ring-[3px] focus:ring-carmin/25 {{ $on ? 'border-carmin bg-fog' : 'border-mist bg-paper hover:bg-fog' }}">
                                    <span class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-[6px] border {{ $on ? 'border-carmin bg-carmin text-white' : 'border-mist' }}">
                                        @if ($on)<i class="fa fa-check text-[10px]"></i>@endif
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-ink">{{ $h['name'] }}</span>
                                        <span class="block text-xs text-pewter">{{ $tipoTexto[$h['tipo']] }} · regó {{ $h['dias'] }} día(s)</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-pewter">Todavía no hay regadores habituales para esta fecha: elige abajo.</p>
                    @endif
                </div>

                {{-- Otro trabajador (días especiales) --}}
                <div>
                    <p class="text-sm font-semibold text-ink">Otro trabajador</p>
                    <p class="mb-2 text-xs text-pewter">Planilla: solo trabajadores agrarios con contrato vigente en la fecha. Cuadrilla: cuadrilleros activos.</p>
                    <div class="flex flex-wrap items-start gap-2">
                        <div class="inline-flex rounded-[10px] border border-mist p-0.5">
                            @foreach ($tipoTexto as $valor => $texto)
                                <button type="button" wire:click="$set('tipoOtro', '{{ $valor }}')"
                                    class="h-8 rounded-[8px] px-3 text-sm font-medium {{ $tipoOtro === $valor ? 'bg-fog text-ink' : 'text-graphite hover:text-ink' }}">
                                    {{ $texto }} <span class="text-xs text-pewter">({{ count($valor === 'empleados' ? $planilleros : $cuadrilleros) }})</span>
                                </button>
                            @endforeach
                        </div>
                        <div class="min-w-[16rem] flex-1" wire:key="otro-{{ $tipoOtro }}-{{ $fecha }}">
                            <x-searchable-select :options="$tipoOtro === 'empleados' ? $planilleros : $cuadrilleros"
                                search-placeholder="Escribe el nombre…" wire:model.live="otroSeleccionado" />
                        </div>
                    </div>
                </div>

                {{-- Elegidos --}}
                <div class="rounded-[10px] border border-mist">
                    <p class="border-b border-hairline bg-fog px-3 py-2 text-xs font-medium text-graphite">Se agregarán ({{ count($elegidos) }})</p>
                    @forelse ($elegidos as $e)
                        <div class="flex items-center gap-3 border-t border-hairline px-3 py-2 first:border-t-0" wire:key="el-{{ $e['tipo'] }}-{{ $e['id'] }}">
                            <span class="rounded-[6px] bg-fog px-1.5 py-0.5 text-xs font-medium text-graphite">{{ $tipoTexto[$e['tipo']] }}</span>
                            <span class="flex-1 text-sm text-ink">{{ $e['nombre'] }}</span>
                            <button type="button" wire:click="alternar({{ $e['id'] }}, '{{ $e['tipo'] }}')" class="text-steel hover:text-red-700" title="Quitar">
                                <i class="fa fa-xmark"></i>
                            </button>
                        </div>
                    @empty
                        <p class="px-3 py-4 text-center text-sm text-pewter">Marca regadores habituales o busca otro trabajador.</p>
                    @endforelse
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <button type="button" wire:click="$set('abierto', false)" class="{{ $btn2 }}">Cancelar</button>
            <button type="button" wire:click="guardar" wire:loading.attr="disabled" @disabled(!$elegidos) class="{{ $btnPrincipal }} ml-2">
                <i class="fa fa-plus"></i> Agregar {{ count($elegidos) ?: '' }} regador{{ count($elegidos) === 1 ? '' : 'es' }}
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
