{{-- Agregar regadores a una fecha: habituales primero; cualquier planillero agrario con contrato o cuadrillero en días especiales --}}
@php
    $btn2 = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btnPrincipal = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25 disabled:opacity-50';
    $marcado = fn($id, $tipo) => collect($elegidos)->contains(fn($e) => $e['tipo'] === $tipo && (int) $e['id'] === (int) $id);
    $tipoTexto = ['empleados' => 'Planilla', 'cuadrilleros' => 'Cuadrilla'];
    // Planilla = lo normal (neutro); Cuadrilla resalta en ámbar para que no se agregue por error
    $chipTipo = [
        'empleados' => 'rounded-[6px] bg-fog px-1.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-graphite',
        'cuadrilleros' => 'rounded-[6px] bg-amber-100 px-1.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-amber-800 dark:bg-amber-400/15 dark:text-amber-300',
    ];
    $cuadElegidos = collect($elegidos)->where('tipo', 'cuadrilleros');
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
                                        <span class="mt-0.5 flex items-center gap-1.5 text-xs text-pewter"><span class="{{ $chipTipo[$h['tipo']] }}">@if ($h['tipo'] === 'cuadrilleros')<i class="fa fa-people-group"></i> @endif{{ $tipoTexto[$h['tipo']] }}</span> regó {{ $h['dias'] }} día(s)</span>
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
                                    class="h-8 rounded-[8px] px-3 text-sm font-medium {{ $tipoOtro === $valor ? ($valor === 'cuadrilleros' ? 'bg-amber-100 text-amber-800 dark:bg-amber-400/15 dark:text-amber-300' : 'bg-fog text-ink') : 'text-graphite hover:text-ink' }}">
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
                            <span class="{{ $chipTipo[$e['tipo']] }}">{{ $tipoTexto[$e['tipo']] }}</span>
                            <span class="flex-1 text-sm text-ink">
                                {{ $e['nombre'] }}
                                @if ($e['tipo'] === 'cuadrilleros' && in_array((int) $e['id'], $sinRegistroCuadrilla, true))
                                    <span class="ml-1 text-xs text-amber-700 dark:text-amber-400"><i class="fa fa-triangle-exclamation"></i> no figura en el registro de cuadrilla de esta fecha</span>
                                @endif
                            </span>
                            <button type="button" wire:click="alternar({{ $e['id'] }}, '{{ $e['tipo'] }}')" class="text-steel hover:text-red-700" title="Quitar">
                                <i class="fa fa-xmark"></i>
                            </button>
                        </div>
                    @empty
                        <p class="px-3 py-4 text-center text-sm text-pewter">Marca regadores habituales o busca otro trabajador.</p>
                    @endforelse
                </div>

                {{-- Agregar como cuadrilla es la excepción: se avisa claro antes de guardar --}}
                @if ($cuadElegidos->isNotEmpty())
                    <div class="rounded-[10px] border border-amber-300 bg-amber-50 px-3 py-2.5 text-sm text-amber-800 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-300">
                        <p class="font-semibold"><i class="fa fa-people-group"></i> {{ $cuadElegidos->count() }} se agregará{{ $cuadElegidos->count() === 1 ? '' : 'n' }} como CUADRILLA:
                            {{ $cuadElegidos->pluck('nombre')->implode(', ') }}</p>
                        <p class="mt-0.5 text-[13px]">Su riego se paga en el registro de cuadrilla (FDM/81), no en planilla. Si es un trabajador de planilla, quítalo y búscalo en la pestaña Planilla.</p>
                    </div>
                @endif
            </div>

            {{-- Cuadrillero que parece ser trabajador de planilla: su riego debe ir a su nombre de planilla --}}
            @if ($sospechosos)
                <div class="mt-4 rounded-[10px] border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-300">
                    <p class="font-medium"><i class="fa fa-triangle-exclamation"></i> ¿Son de planilla?</p>
                    <ul class="mt-1 list-disc pl-5 text-[13px]">
                        @foreach ($sospechosos as $s)
                            <li><b>{{ $s['cuadrillero'] }}</b> (cuadrillero) parece ser <b>{{ $s['empleado'] }}</b>, de planilla con contrato ese día.</li>
                        @endforeach
                    </ul>
                    <p class="mt-1 text-[13px]">Si es su jornada de planilla, usa su ficha de planilla. Si es trabajo extra que se paga como cuadrilla (domingo, horas de más), puedes agregarlo como cuadrillero, pero en la fecha real en que trabajó.</p>
                    <div class="mt-2 flex flex-wrap justify-end gap-2">
                        <button type="button" wire:click="guardar(true)" class="{{ $btn2 }}">Es otra persona, agregar igual</button>
                        <button type="button" wire:click="usarPlanilla" class="{{ $btnPrincipal }}"><i class="fa fa-user-check"></i> Usar su ficha de planilla</button>
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <button type="button" wire:click="$set('abierto', false)" class="{{ $btn2 }}">Cancelar</button>
            <button type="button" wire:click="guardar" wire:loading.attr="disabled" @disabled(!$elegidos) class="{{ $btnPrincipal }} ml-2"
                @if ($cuadElegidos->isNotEmpty()) wire:confirm="Se agregará{{ $cuadElegidos->count() === 1 ? '' : 'n' }} como CUADRILLA: {{ $cuadElegidos->pluck('nombre')->implode(', ') }}. ¿Es correcto?" @endif>
                <i class="fa fa-plus"></i> Agregar {{ count($elegidos) ?: '' }} regador{{ count($elegidos) === 1 ? '' : 'es' }}
                @if ($cuadElegidos->isNotEmpty())
                    <span class="font-normal opacity-90">({{ $cuadElegidos->count() }} de cuadrilla)</span>
                @endif
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
