@props(['matriz'])

@if(empty($matriz['cuadrilleros']))
    <div class="alert alert-warning shadow-sm my-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span>No se encontraron jornadas pendientes de pago para el periodo y filtro seleccionados.</span>
    </div>
@else
    <div class="overflow-x-auto border border-base-300 rounded-lg shadow-sm bg-base-100">
        <x-table class="w-full text-xs">
            @php
                // Agrupa el rango continuo de fechas por mes, preservando el orden.
                // Con 28-mar al 05-abr esto produce dos grupos: marzo (4 días) y abril (5 días).
                $gruposPorMes = collect($matriz['fechas'])
                    ->groupBy(fn($fecha) => \Carbon\Carbon::parse($fecha)->format('Y-m'));
                $diasCortos = ['L', 'M', 'M', 'J', 'V', 'S', 'D']; // índice 0 = lunes (dayOfWeekIso 1)
            @endphp

            <x-slot name="thead">
                {{-- Fila 1: mes, agrupado con colspan --}}
                <x-tr class="bg-base-200/60 text-center">
                    <x-th sticky rowspan="2">Grupo</x-th>
                    <x-th sticky rowspan="2">Cuadrillero</x-th>

                    @foreach($gruposPorMes as $mesKey => $fechasDelMes)
                                <x-th :level="1" colspan="{{ $fechasDelMes->count() }}" class="border-r border-base-200">
                                    {{ \Illuminate\Support\Str::ucfirst(
                            \Carbon\Carbon::parse($mesKey . '-01')->locale('es')->translatedFormat('F Y')
                        ) }}
                                </x-th>
                    @endforeach

                    <x-th sticky rowspan="2">Total Pagar</x-th>
                </x-tr>

                {{-- Fila 2: día de la semana + número --}}
                <x-tr class="bg-base-200/60 text-center">
                    @foreach($matriz['fechas'] as $fecha)
                        @php $f = \Carbon\Carbon::parse($fecha); @endphp
                        <x-th :level="2" class="min-w-[48px] border-r border-base-200">
                            {{ $diasCortos[$f->dayOfWeekIso - 1] }}<br>
                            {{ $f->format('d') }}
                        </x-th>
                    @endforeach
                </x-tr>
            </x-slot>

            <x-slot name="tbody">
                @foreach($matriz['cuadrilleros'] as $item)
                    <x-tr class="hover:bg-base-200/30 transition-colors">


                        <x-td compact class="text-center border-r border-base-200">
                            <span
                                class="inline-flex items-center justify-center px-1.5 py-0.2 rounded text-[10px] font-semibold"
                                style="background-color: {{ $item['color_grupo'] }}; color: {{ \App\Support\ColorContraste::textoPara($item['color_grupo']) }};">
                                {{ $item['codigo_grupo'] }}
                            </span>
                        </x-td>
                        <x-td compact sticky class="font-medium shadow-sm">
                            <div class="font-semibold text-xs leading-tight">{{ $item['nombre'] }}</div>
                            @if(!empty($item['codigo']))
                                <span class="text-[9px] badge badge-ghost badge-xs px-1 py-0">{{ $item['codigo'] }}</span>
                            @endif
                        </x-td>
                        @foreach($matriz['fechas'] as $fecha)
                            @php $dia = $item['dias'][$fecha] ?? null; @endphp
                            <x-td compact class="text-center border-r border-base-200">
                                @if($dia)
                                    <button type="button" wire:click="verDetalleDia({{ $dia['registro_diario_id'] }})"
                                        class="group inline-flex flex-col items-center justify-center w-full py-0.5 px-1 rounded hover:bg-primary/10 hover:border-primary/30 border border-transparent transition-all cursor-pointer leading-tight"
                                        title="Ver desglose del día">
                                        <span class="text-xs font-semibold">{{ number_format($dia['subtotal'], 2) }}</span>
                                        @if($dia['bono_con_jornal'] > 0)
                                            <span
                                                class="text-[8px] opacity-80">{{ number_format($dia['costo_dia']) }}+{{ number_format($dia['bono_con_jornal'], 2) }}</span>
                                        @endif
                                    </button>
                                @else
                                    <span class="text-base-300 font-light text-xs">-</span>
                                @endif
                            </x-td>
                        @endforeach

                        <x-td compact sticky class="text-right font-bold shadow-sm right-0 text-xs">
                            {{ number_format($item['total_cuadrillero'], 2) }}
                        </x-td>
                    </x-tr>

                    {{-- Fila de Total de Grupo (si aplica al cerrar un grupo) --}}
                    @if(!empty($item['es_ultimo_del_grupo']))
                        <x-tr level="1">
                            <x-th compact sticky level="1" class="text-left text-xs font-bold uppercase">
                                Total {{ $item['codigo_grupo'] }}
                            </x-th>
                            <x-th compact level="1" class="border-r border-base-200"></x-th>

                            @foreach($matriz['fechas'] as $fecha)
                                <x-th compact level="1" class="text-center border-r border-base-200 text-xs font-bold">
                                    {{ number_format($item['totales_grupo'][$fecha] ?? 0, 2) }}
                                </x-th>
                            @endforeach

                            <x-th compact sticky level="1" class="text-right font-black right-0 text-xs">
                                {{ number_format($item['total_general_grupo'], 2) }}
                            </x-th>
                        </x-tr>
                    @endif
                @endforeach
            </x-slot>

            <x-slot name="tfoot">
                <x-tr class="bg-base-200/90 font-bold">
                    <x-td sticky class="text-left">
                        TOTAL GENERAL
                    </x-td>

                    {{-- Totales por columna de día --}}
                    @foreach($matriz['fechas'] as $fecha)
                        @php
                            $totalDia = array_reduce($matriz['cuadrilleros'], function ($carry, $c) use ($fecha) {
                                return $carry + ($c['dias'][$fecha]['subtotal'] ?? 0);
                            }, 0);
                        @endphp
                        <x-td class="text-center">
                            @if($totalDia > 0)
                                {{ number_format($totalDia, 2) }}
                            @else
                                <span class="text-base-300 font-light">-</span>
                            @endif
                        </x-td>
                    @endforeach

                    {{-- Gran Total resaltado --}}
                    <x-td sticky class="text-right text-sm right-0">
                        {{ number_format($matriz['gran_total'], 2) }}
                    </x-td>
                </x-tr>
            </x-slot>
        </x-table>
    </div>
@endif