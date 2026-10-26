{{--
    Selector de mes compacto (DESIGN.md): ‹ [Mes] [Año] › en una sola línea, para poner junto al título.
    Usa el trait App\Traits\Selectores\ConSelectorMes (propiedades mes / anio, mesAnterior(), mesSiguiente()).
    Versión grande con botones "Mes anterior / siguiente": comun.selector-mes.
--}}
@php
    $mesesCompacto = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $aniosCompacto = range(now()->year + 1, \App\Services\Sistema\Vigencia\SistemaVigenciaReglas::anioMinimo(2015));
    $flechaCompacto = 'inline-flex h-8 w-8 items-center justify-center rounded-[10px] text-graphite hover:bg-fog hover:text-ink focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $selectCompacto = 'h-8 cursor-pointer rounded-[10px] border-0 bg-transparent py-0 pl-2 pr-7 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
@endphp
<div class="inline-flex items-center gap-0.5 rounded-[10px] border border-mist bg-paper p-0.5 dark:[color-scheme:dark]">
    <button type="button" wire:click="mesAnterior" class="{{ $flechaCompacto }}" title="Mes anterior" aria-label="Mes anterior">
        <i class="fa fa-chevron-left text-xs"></i>
    </button>
    <select wire:model.live="mes" class="{{ $selectCompacto }}" aria-label="Mes">
        @foreach ($mesesCompacto as $numero => $nombre)
            <option value="{{ $numero }}">{{ $nombre }}</option>
        @endforeach
    </select>
    <select wire:model.live="anio" class="{{ $selectCompacto }} tabular-nums" aria-label="Año">
        @foreach ($aniosCompacto as $a)
            <option value="{{ $a }}">{{ $a }}</option>
        @endforeach
    </select>
    <button type="button" wire:click="mesSiguiente" class="{{ $flechaCompacto }}" title="Mes siguiente" aria-label="Mes siguiente">
        <i class="fa fa-chevron-right text-xs"></i>
    </button>
</div>
