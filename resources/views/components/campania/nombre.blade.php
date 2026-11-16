{{--
    Nombre de una campaña en una lista (DESIGN.md): enlace que abre la tarjeta de la campaña (CampaniaTarjetaComponent,
    evento verTarjetaCampania) y, si se pide, muestra su campo para saber de qué campo es.

    <x-campania.nombre :campania="$campania" />                 → "T.2026 · campo 1"
    <x-campania.nombre :campania="$campania" :campo="false" />  → "T.2026" (cuando el campo ya se ve al lado)
    <x-campania.nombre :id="5" nombre="T.2026" campo="1" />

    La página debe tener <livewire:campania.campania-tarjeta-component /> (y los modales a los que lleva la tarjeta).
--}}
@props(['campania' => null, 'id' => null, 'nombre' => null, 'campo' => true])
@php
    $id ??= $campania?->id;
    $nombre ??= $campania?->nombre_campania;
    $nombreCampo = is_string($campo) ? $campo : ($campo ? $campania?->campo : null);
@endphp
@if ($id)
    <button type="button" title="Ver la campaña {{ $nombre }}{{ $nombreCampo ? ' del campo ' . $nombreCampo : '' }}"
        x-on:click.stop="Livewire.dispatch('verTarjetaCampania', { campaniaId: {{ (int) $id }} })"
        {{ $attributes->merge(['class' => 'inline-flex items-baseline gap-1 rounded-[4px] text-left font-semibold text-ink underline decoration-mist decoration-dotted underline-offset-4 hover:text-carmin hover:decoration-carmin focus:outline-none focus-visible:ring-[3px] focus-visible:ring-carmin/25']) }}>
        <span class="truncate">{{ $nombre }}</span>
        @if ($nombreCampo)
            <span class="shrink-0 text-xs font-normal text-graphite">· campo {{ $nombreCampo }}</span>
        @endif
    </button>
@else
    <span {{ $attributes }}>{{ $nombre ?? '—' }}</span>
@endif
