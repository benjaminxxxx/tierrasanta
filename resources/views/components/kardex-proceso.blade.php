{{--
    Componente: x-kardex-proceso
    Props:
      - pasoActivo          (int)         : 1 | 2 | 3
      - linkEntradasYSalidas(string|null) : URL al módulo de asignaciones
      - linkResumenBlanco   (string|null) : URL al resumen blanco
      - linkResumenNegro    (string|null) : URL al resumen negro
      - accionCrearKardex   (string|null) : nombre del método Livewire para crear kardex
                                            ej: 'nuevoInsumoKardex' — se dispara con dispatch
      - accionGenerarResumen(string|null) : nombre del método Livewire para regenerar resumen
                                            ej: 'generarDetalleKardexInsumo'

    Uso desde módulo de Kardex principal (paso 1 activo, con wire:click):
      <x-kardex-proceso
          :pasoActivo="1"
          accionCrearKardex="nuevoInsumoKardex"
          linkEntradasYSalidas="{{ route('gestion_insumos.kardex_asignacion', $kardex->id) }}"
          linkResumenBlanco="{{ route('gestion_insumos.kardex.detalle', [$kardex->id, 'blanco']) }}"
          linkResumenNegro="{{ route('gestion_insumos.kardex.detalle', [$kardex->id, 'negro']) }}"
      />

    Uso desde módulo de asignaciones (paso 2 activo, con wire:click para regenerar):
      <x-kardex-proceso
          :pasoActivo="2"
          linkEntradasYSalidas="{{ route('gestion_insumos.kardex_asignacion', $kardex->id) }}"
          linkResumenBlanco="{{ route('gestion_insumos.kardex.detalle', [$kardex->id, 'blanco']) }}"
          linkResumenNegro="{{ route('gestion_insumos.kardex.detalle', [$kardex->id, 'negro']) }}"
          accionGenerarResumen="generarDetalleKardexInsumo"
      />

    NOTA sobre wire:click dinámico:
      Los wire:click NO pueden ser dinámicos en Blade (Livewire los compila en build time).
      La solución es pasar el nombre de la función y usar @this.call() desde Alpine,
      o bien usar wire:click con @if según el contexto — aquí usamos Alpine @click con $wire.call()
      para que funcione independientemente del componente Livewire que lo contenga.
--}}

@props([
    'pasoActivo' => 1,
    'linkEntradasYSalidas' => null,
    'linkResumenBlanco' => null,
    'linkResumenNegro' => null,
    'accionCrearKardex' => null,
    'accionGenerarResumen' => null,
])

<x-proceso-timeline :pasoActivo="$pasoActivo">

    {{-- ── PASO 1: Crear Kardex ─────────────────────────────────── --}}
    <x-proceso-paso numero="1" titulo="Crear Kardex"
        descripcion="Define el tipo de Kardex, la cantidad inicial heredada del período anterior y los datos de apertura del ciclo.">
        @if ($accionCrearKardex)
            {{--
                Alpine llama al método Livewire por nombre dinámicamente.
                Funciona sin importar en qué componente esté montado.
            --}}
            <x-button @click="$wire.dispatch('{{ $accionCrearKardex }}')">
                Crear el Kardex
            </x-button>
        @else
            <x-button href="{{ route('gestion_insumos.kardex') }}">
                Ir a Kardexes
            </x-button>
        @endif
    </x-proceso-paso>

    {{-- ── PASO 2: Asignar movimientos ─────────────────────────── --}}
    <x-proceso-paso numero="2" titulo="Asignar Entradas y Salidas"
        descripcion="¿Tienes entradas y salidas registradas? Para que aparezcan en el Kardex que deseas, asígnalas al período correspondiente.">
        @if ($linkEntradasYSalidas)
            <x-button href="{{ $linkEntradasYSalidas }}">
                Asignar Entradas y Salidas
            </x-button>
        @endif
    </x-proceso-paso>

    {{-- ── PASO 3: Generar Resumen ─────────────────────────────── --}}
    <x-proceso-paso numero="3" titulo="Generar Resumen"
        descripcion="Consolida los movimientos asignados y calcula el costo por unidad (PEPS o Promedio). Se ejecuta automáticamente al guardar asignaciones.">
        @if ($accionGenerarResumen)
            <p class="proceso-paso__descripcion" style="font-size:0.78rem; font-style:italic; margin-bottom:0.5rem;">
                Se ejecuta automáticamente al guardar.
            </p>
            <x-button x-on:click="$wire.call('{{ $accionGenerarResumen }}')">
                Regenerar manualmente
            </x-button>
        @endif

        @if ($linkResumenBlanco || $linkResumenNegro)
            <x-flex class="w-full justify-center">
                @if ($linkResumenBlanco)
                    <x-button href="{{ $linkResumenBlanco }}" variant="secondary">
                        Ver Resumen Blanco
                    </x-button>
                @endif
                @if ($linkResumenNegro)
                    <x-button href="{{ $linkResumenNegro }}" variant="secondary">
                        Ver Resumen Negro
                    </x-button>
                @endif
            </x-flex>
        @endif
    </x-proceso-paso>

</x-proceso-timeline>
