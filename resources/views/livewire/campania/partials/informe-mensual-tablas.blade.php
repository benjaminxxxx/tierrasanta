{{-- Tablas del informe mensual (mismas que el Excel): CampaniaInformeMensualFormatoReglas::tablas() --}}
@php
    $formato = function ($valor, string $tipo) {
        if ($valor === null || $valor === '') {
            return '';
        }
        if ($tipo === 'texto') {
            return $valor;
        }
        if ($tipo === 'fecha') {
            return \Carbon\Carbon::parse($valor)->format('d/m/Y');
        }
        $v = (float) $valor;
        if (abs($v) < 0.005 && $tipo !== 'cantidad') {
            return '-';
        }
        return match ($tipo) {
            'entero' => number_format($v, 0),
            'cantidad' => number_format($v, 3),
            'usd' => '$ ' . number_format($v, 2),
            'soles' => 'S/ ' . number_format($v, 2),
            'pct' => number_format($v * 100, 2) . ' %',
            default => number_format($v, 2),
        };
    };
    $estilos = [
        'seccion' => 'bg-sky-100 dark:bg-sky-900/40 font-semibold',
        'grupo' => 'bg-slate-50 dark:bg-slate-800/60',
        'total' => 'bg-amber-100 dark:bg-amber-900/40 font-bold',
        'error' => 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200',
        'aviso' => 'bg-amber-50 dark:bg-amber-900/20 text-amber-900 dark:text-amber-200',
    ];
    $hojas = ['resumen' => 'Resumen', 'detalle' => 'Detalle'];
@endphp

<div x-data="{ hoja: 'resumen' }" class="space-y-4">
    <div class="flex gap-2">
        @foreach ($hojas as $clave => $nombre)
            <button type="button" @click="hoja = '{{ $clave }}'"
                class="px-3 py-1 rounded-lg border text-sm font-semibold"
                :class="hoja === '{{ $clave }}' ? 'bg-primary text-white border-primary' : 'border-gray-300 dark:border-gray-600'">
                {{ $nombre }}
            </button>
        @endforeach
    </div>

    @foreach ($tablas as $tabla)
        <section x-show="hoja === '{{ $tabla['hoja'] }}'" wire:key="tabla-{{ $tabla['clave'] }}">
            <h4 class="text-sm font-bold uppercase tracking-wide text-white bg-[#1F4E78] px-3 py-1.5 rounded-t-lg">{{ $tabla['titulo'] }}</h4>
            <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-b-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-[#D9E1F2] dark:bg-slate-700">
                        <tr>
                            @foreach ($tabla['columnas'] as $col)
                                <th class="px-2 py-1.5 font-semibold whitespace-nowrap {{ $col['tipo'] === 'texto' ? 'text-left' : 'text-right' }}">{{ trim($col['titulo']) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tabla['filas'] as $fila)
                            <tr class="border-t border-gray-100 dark:border-gray-700 {{ $estilos[$fila['estilo']] ?? '' }}">
                                @foreach ($fila['celdas'] as $i => $valor)
                                    @php $tipo = $tabla['columnas'][$i]['tipo'] ?? 'texto'; @endphp
                                    <td class="px-2 py-1 {{ $tipo === 'texto' ? 'text-left' : 'text-right whitespace-nowrap' }} {{ $i === 0 ? 'whitespace-pre' : '' }}">{{ $formato($valor, $tipo) }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($tabla['columnas']) }}" class="px-2 py-2 italic text-muted-foreground">{{ $tabla['vacio'] }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tabla['nota'])
                <p class="text-xs italic text-muted-foreground mt-1">{{ $tabla['nota'] }}</p>
            @endif
        </section>
    @endforeach
</div>
