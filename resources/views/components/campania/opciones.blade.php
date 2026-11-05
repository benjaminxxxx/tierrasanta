{{--
    Menú de opciones de una campaña (DESIGN.md), el mismo en todas las listas de campañas.

    <x-campania.opciones :campania="$campania" :cosecha="$cosecha[$campania->id] ?? null" />

    Props:
    - campania: CampoCampania.
    - cosecha: estado de CampaniaCosechaConsulta (opcional). Si recomienda cierre, la opción muestra la fecha sugerida;
      sin él, el modal "Cerrar campaña" calcula la fecha sugerida igual.
    - trigger: 'icono' (⋮, para filas) o 'boton' ("Opciones").
    - detalle: muestra "Detalle de la campaña" (no hace falta dentro del detalle mismo).
    - eliminar: muestra "Eliminar campaña" (el componente que lo incluye debe tener eliminarCampania($id)).
    - align: lado del menú.
    Slot: opciones propias de la pantalla (reportes, Excel…), van antes de las de gestión.

    Necesita en la página: campania-historia (verHistoriaCampania), el formulario de campaña (editarCampania),
    campania-corregir-fechas-component y campania-cerrar-component.
--}}
@props([
    'campania',
    'cosecha' => null,
    'trigger' => 'icono',
    'detalle' => true,
    'eliminar' => true,
    'align' => 'right',
])
@php
    $id = $campania->id;
    $cerrada = (bool) $campania->fecha_fin;
    $puedeGestionar = auth()->user()?->canAny([\App\Constants\Permisos::CAMPAÑA_GESTIONAR, \App\Constants\Permisos::CAMPAÑA_POR_CAMPO_GESTIONAR]);
    $sugerida = $cosecha && ($cosecha['recomendar_cierre'] ?? false) ? $cosecha['fecha_cierre_sugerida'] : null;
    $item = 'flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-left text-sm text-ink hover:bg-fog focus:bg-fog focus:outline-none';
    $peligro = 'flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-left text-sm text-red-700 hover:bg-red-50 focus:outline-none dark:text-red-400 dark:hover:bg-red-400/10';
    $icono = 'w-4 text-center text-steel';
@endphp

{{-- Menú en posición fija (no absoluta): así no lo recorta el overflow de la lista o de la tabla que lo contiene.
     Se ubica junto al botón y se abre hacia arriba si abajo no hay espacio; se cierra al hacer scroll. --}}
<div class="inline-block text-left" x-data="{
        open: false, top: 0, left: 0,
        abrir() {
            this.open = true;
            this.$nextTick(() => {
                const r = this.$refs.boton.getBoundingClientRect();
                const m = this.$refs.menu;
                const w = m.offsetWidth, h = m.offsetHeight, margen = 8;
                const arriba = window.innerHeight - r.bottom < h + margen && r.top > window.innerHeight - r.bottom;
                this.top = arriba ? Math.max(margen, r.top - h - 6) : r.bottom + 6;
                const izquierda = @js($align) === 'left' ? r.left : r.right - w;
                this.left = Math.min(Math.max(margen, izquierda), window.innerWidth - w - margen);
            });
        },
    }" @keydown.escape.window="open = false" @scroll.window="open = false" @resize.window="open = false">
    <div x-ref="boton" @click="open ? open = false : abrir()">
        @if ($trigger === 'boton')
            <button type="button" class="inline-flex h-9 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25">
                <i class="fa fa-gear text-steel"></i> Opciones <i class="fa fa-chevron-down text-[10px] text-steel"></i>
            </button>
        @else
            <button type="button" title="Opciones de la campaña"
                class="inline-flex h-8 items-center rounded-[10px] border border-mist bg-paper px-2 text-sm text-graphite hover:bg-fog hover:text-ink focus:outline-none focus:ring-[3px] focus:ring-carmin/25">
                <i class="fa fa-ellipsis-vertical text-steel"></i>
            </button>
        @endif
    </div>

    <div x-ref="menu" x-show="open" x-cloak style="display: none" :style="{ top: top + 'px', left: left + 'px' }"
        @click.outside="if (!$refs.boton.contains($event.target)) open = false" @click="open = false"
        class="fixed z-[9999] max-h-[80vh] w-72 max-w-[calc(100vw-16px)] overflow-y-auto rounded-[10px] border border-mist bg-paper shadow-[0_8px_24px_rgb(16_33_45/0.12)]">
        <div class="py-1">
            <p class="truncate px-4 pb-1 pt-1.5 text-xs text-pewter">{{ $campania->nombre_campania }} · campo {{ $campania->campo }}</p>

            @if ($detalle)
                <a href="{{ route('campania.por_campo', ['campania' => $id]) }}" class="{{ $item }}">
                    <i class="fa fa-folder-open {{ $icono }}"></i> Detalle de la campaña
                </a>
            @endif
            <button type="button" class="{{ $item }}" @click="$wire.dispatch('verHistoriaCampania', {campaniaId: {{ $id }}})">
                <i class="fa fa-book-open {{ $icono }}"></i> Historia de la campaña
            </button>

            @if (trim($slot) !== '')
                <div class="my-1 border-t border-hairline"></div>
                {{ $slot }}
            @endif

            @if ($puedeGestionar)
                <div class="my-1 border-t border-hairline"></div>
                <button type="button" class="{{ $item }}" @click="$wire.dispatch('editarCampania', {campaniaId: {{ $id }}})">
                    <i class="fa fa-pen {{ $icono }}"></i> Editar campaña
                </button>
                <button type="button" class="{{ $item }}" @click="$wire.dispatch('corregirFechasCampania', {campaniaId: {{ $id }}})">
                    <i class="fa fa-calendar-days {{ $icono }}"></i> Corregir fecha de inicio o cierre
                </button>
                @if (!$cerrada)
                    @if ($sugerida)
                        <button type="button" class="{{ $item }} font-medium"
                            @click="$wire.dispatch('cerrarCampania', {campaniaId: {{ $id }}, fecha: '{{ $sugerida->toDateString() }}'})">
                            <i class="fa fa-flag-checkered w-4 text-center text-amber-700 dark:text-amber-400"></i> Cerrar campaña ({{ formatear_fecha($sugerida) }})
                        </button>
                    @else
                        <button type="button" class="{{ $item }}" @click="$wire.dispatch('cerrarCampania', {campaniaId: {{ $id }}})">
                            <i class="fa fa-flag-checkered {{ $icono }}"></i> Cerrar campaña
                        </button>
                    @endif
                @endif
                @if ($eliminar)
                    <div class="my-1 border-t border-hairline"></div>
                    <button type="button" class="{{ $peligro }}"
                        wire:confirm="¿Eliminar la campaña {{ $campania->nombre_campania }} del campo {{ $campania->campo }}?"
                        wire:click="eliminarCampania({{ $id }})">
                        <i class="fa fa-trash w-4 text-center"></i> Eliminar campaña
                    </button>
                @endif
            @endif
        </div>
    </div>
</div>
