{{--
    Selector de grupo de cuadrilla con buscador y etiquetas de uso (DESIGN.md).

    <x-cuadrilla.selector-grupo :grupos="$grupos" model="codigo_grupo" />

    - grupos: CuadrillaGrupoConsulta::paraSelector() — ya vienen ordenados: en esta semana, usados recientemente, el resto.
    - model: propiedad Livewire del grupo elegido (se asigna con $wire.set, así corre su updated…()).
    Se escribe código o nombre para filtrar; flechas + Enter para elegir; Escape cierra.
--}}
@props(['grupos' => [], 'model' => 'codigo_grupo', 'placeholder' => 'Seleccionar grupo'])
@php
    $secciones = ['tramo' => 'En esta semana', 'reciente' => 'Usados recientemente', 'otros' => 'Otros grupos'];
@endphp
<div class="relative" x-data="{
        abierto: false, buscar: '', activo: 0,
        grupos: @js($grupos),
        valor: $wire.entangle(@js($model)),
        secciones: @js($secciones),
        get filtrados() {
            const t = this.buscar.trim().toLowerCase();
            return t ? this.grupos.filter(g => (g.codigo + ' ' + g.nombre).toLowerCase().includes(t)) : this.grupos;
        },
        get elegido() { return this.grupos.find(g => g.codigo === this.valor) || null; },
        abrir() { this.abierto = true; this.buscar = ''; this.activo = Math.max(0, this.filtrados.findIndex(g => g.codigo === this.valor)); this.$nextTick(() => this.$refs.buscar.focus()); },
        elegir(g) { if (!g) return; this.abierto = false; $wire.set(@js($model), g.codigo); },
        mover(d) { const n = this.filtrados.length; if (!n) return; this.activo = (this.activo + d + n) % n; this.$nextTick(() => this.$refs.lista.querySelector('[data-activo]')?.scrollIntoView({ block: 'nearest' })); },
    }" @keydown.escape.stop="abierto = false" @click.outside="abierto = false">

    <button type="button" @click="abierto ? abierto = false : abrir()"
        class="mt-1 flex h-10 w-full items-center gap-2 rounded-[10px] border border-mist bg-paper px-3 text-left text-sm text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25">
        <template x-if="elegido">
            <span class="flex min-w-0 flex-1 items-center gap-2">
                <span class="h-3 w-3 shrink-0 rounded-full border border-mist" :style="`background:${elegido.color || '#7b7b7b'}`"></span>
                <span class="font-semibold" x-text="elegido.codigo"></span>
                <span class="truncate text-graphite" x-text="elegido.nombre"></span>
            </span>
        </template>
        <span x-show="!elegido" class="flex-1 text-pewter">{{ $placeholder }}</span>
        <i class="fa fa-chevron-down text-[10px] text-steel"></i>
    </button>

    <div x-show="abierto" x-cloak x-transition.opacity.duration.100ms
        class="absolute left-0 right-0 z-50 mt-1 overflow-hidden rounded-[10px] border border-mist bg-paper shadow-[0_8px_24px_rgb(16_33_45/0.12)]">
        <div class="border-b border-hairline p-2">
            <div class="relative">
                <i class="fa fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-steel"></i>
                <input type="text" x-ref="buscar" x-model="buscar" @input="activo = 0" autocomplete="off"
                    @keydown.arrow-down.prevent="mover(1)" @keydown.arrow-up.prevent="mover(-1)" @keydown.enter.prevent="elegir(filtrados[activo])"
                    placeholder="Buscar por código o nombre…"
                    class="h-9 w-full rounded-[10px] border border-mist bg-paper pl-8 pr-3 text-sm text-ink focus:border-carmin focus:outline-none focus:ring-[3px] focus:ring-carmin/25" />
            </div>
        </div>
        <ul x-ref="lista" class="max-h-80 overflow-y-auto py-1 text-sm">
            <template x-for="(g, i) in filtrados" :key="g.codigo">
                <li>
                    <p x-show="i === 0 || filtrados[i - 1].seccion !== g.seccion"
                        class="px-3 pb-1 pt-2 text-[11px] font-medium uppercase tracking-wide text-pewter" x-text="secciones[g.seccion]"></p>
                    <button type="button" @click="elegir(g)" @mouseenter="activo = i" :data-activo="activo === i ? true : null"
                        :class="activo === i ? 'bg-fog' : ''"
                        class="flex w-full items-center gap-2 px-3 py-1.5 text-left">
                        <span class="h-3 w-3 shrink-0 rounded-full border border-mist" :style="`background:${g.color || '#7b7b7b'}`"></span>
                        <span class="w-24 shrink-0 font-semibold text-ink" x-text="g.codigo"></span>
                        <span class="min-w-0 flex-1 truncate text-graphite" x-text="g.nombre"></span>
                        <span class="shrink-0 rounded-[6px] px-1.5 py-0.5 text-[11px] font-medium"
                            :class="{
                                'bg-carmin/10 text-carmin': g.seccion === 'tramo',
                                'bg-emerald-50 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-400': g.seccion === 'reciente',
                                'bg-fog text-pewter': g.seccion === 'otros',
                            }" x-text="g.etiqueta"></span>
                        <i x-show="g.codigo === valor" class="fa fa-check shrink-0 text-xs text-carmin"></i>
                    </button>
                </li>
            </template>
            <li x-show="filtrados.length === 0" class="px-3 py-3 text-center text-pewter">Ningún grupo coincide con "<span x-text="buscar"></span>".</li>
        </ul>
    </div>
</div>
