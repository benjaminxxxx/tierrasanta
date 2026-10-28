<div>
    <div x-data="tareaPendienteBubble()" @mousedown="startDrag($event)" @touchstart="startDrag($event)"
        :style="`position: fixed; top: ${top}px; left: ${left}px; z-index: 9999; cursor: ${dragging ? 'grabbing' : 'grab'};`"
        class="select-none">
        <button type="button" @click="onClick()"
            class="w-14 h-14 rounded-full bg-noche text-white ring-1 ring-white/15 shadow-[0_8px_24px_rgb(16_33_45/0.24)] flex items-center justify-center opacity-80 hover:opacity-100 transition-all active:scale-95 focus:outline-none focus:ring-[3px] focus:ring-carmin/25"
            title="Tareas pendientes">
            <i class="fa fa-list-check text-xl"></i>
        </button>
    </div>

    @php
        // Estilos de DESIGN.md (claro y oscuro), literales para que Tailwind los detecte.
        // Los colores de estado solo comunican la gravedad: franja, ícono y chip.
        $estados = [
            'info' => ['franja' => 'bg-blue-600 dark:bg-blue-400', 'texto' => 'text-blue-700 dark:text-blue-400', 'chip' => 'bg-[#EFF4FF] text-blue-700 dark:bg-blue-400/10 dark:text-blue-400', 'icono' => 'fa-circle-info'],
            'warning' => ['franja' => 'bg-amber-500 dark:bg-amber-400', 'texto' => 'text-amber-700 dark:text-amber-400', 'chip' => 'bg-[#FFF8EB] text-amber-700 dark:bg-amber-400/10 dark:text-amber-400', 'icono' => 'fa-triangle-exclamation'],
            'danger' => ['franja' => 'bg-red-600 dark:bg-red-400', 'texto' => 'text-red-700 dark:text-red-400', 'chip' => 'bg-[#FEF2F2] text-red-700 dark:bg-red-400/10 dark:text-red-400', 'icono' => 'fa-circle-exclamation'],
            'success' => ['franja' => 'bg-green-600 dark:bg-green-400', 'texto' => 'text-green-700 dark:text-green-400', 'chip' => 'bg-[#ECFDF3] text-green-700 dark:bg-green-400/10 dark:text-green-400', 'icono' => 'fa-circle-check'],
        ];
        $btn2 = 'inline-flex h-8 shrink-0 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-xs font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
        $btnFantasma = 'inline-flex h-8 shrink-0 items-center gap-1.5 rounded-[10px] px-2.5 text-xs font-medium text-graphite hover:bg-fog hover:text-ink focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    @endphp

    {{-- Cabecera y pie fijos; solo la lista hace scroll (el botón Cerrar siempre se ve) --}}
    <x-modal wire:model="mostrarFormularioTareasPendientes" maxWidth="full">
        <div class="flex max-h-[calc(100vh-2.5rem)] md:max-h-[calc(100vh-4.5rem)] flex-col rounded-lg bg-paper text-ink font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-hairline px-4 py-4 md:px-6">
                <div class="min-w-0">
                    <h2 class="text-xl font-semibold text-ink">Tareas pendientes del sistema</h2>
                    <p class="text-xs text-pewter">
                        {{ $tareas->count() }} tarea(s) en {{ $mes ? ucfirst(\Illuminate\Support\Carbon::parse($mes . '-01')->translatedFormat('F Y')) : 'el periodo' }}.
                        Abre "Ver detalle" para ir al registro de cada caso.
                    </p>
                </div>
                <div class="flex items-end gap-2">
                    {{-- Periodo: hoy por mes; internamente se trabaja con un rango de fechas --}}
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-graphite">Periodo</span>
                        <input type="month" wire:model.live="mes"
                            class="h-9 w-44 rounded-[10px] border border-mist bg-paper px-3 text-sm text-ink focus:border-carmin focus:outline-none focus:ring-[3px] focus:ring-carmin/25 dark:[color-scheme:dark]">
                    </label>
                    <button type="button" wire:click="detectarTareasPendientes" wire:loading.attr="disabled" wire:target="detectarTareasPendientes"
                        class="inline-flex h-9 items-center gap-2 rounded-[10px] bg-carmin px-4 text-sm font-medium text-white hover:bg-carmin-hover disabled:opacity-60 focus:outline-none focus:ring-[3px] focus:ring-carmin/25">
                        <i class="fa fa-search" wire:loading.remove wire:target="detectarTareasPendientes"></i>
                        <i class="fa fa-spinner fa-spin" wire:loading wire:target="detectarTareasPendientes"></i>
                        Buscar tareas pendientes
                    </button>
                    <button type="button" wire:click="$set('mostrarFormularioTareasPendientes', false)" title="Cerrar"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-[10px] text-graphite hover:bg-fog hover:text-ink focus:outline-none focus:ring-[3px] focus:ring-carmin/25">
                        <i class="fa fa-xmark text-lg"></i>
                    </button>
                </div>
            </div>

            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-fog px-4 py-4 md:px-6">
                @forelse ($tareas as $tarea)
                    @php $e = $estados[$tarea->variante] ?? $estados['warning']; @endphp
                    {{-- El detalle (subtareas) empieza colapsado: se abre con "Ver detalle" --}}
                    <div class="relative overflow-hidden rounded-[10px] border border-mist bg-paper" x-data="{ detalle: false }" wire:key="tarea-{{ $tarea->id }}">
                        <span class="absolute inset-y-0 left-0 w-[3px] {{ $e['franja'] }}"></span>
                        <div class="flex flex-wrap items-start justify-between gap-3 px-4 py-3 pl-5">
                            <div class="flex min-w-0 flex-1 gap-3">
                                <i class="fa {{ $e['icono'] }} mt-0.5 {{ $e['texto'] }}"></i>
                                <div class="min-w-0">
                                    <p class="text-base font-semibold text-ink">{{ $tarea->titulo }}</p>
                                    <p class="text-sm text-graphite">{{ $tarea->descripcion }}</p>
                                    <p class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-pewter">
                                        <span class="rounded-md px-2 py-0.5 font-medium tabular-nums {{ $e['chip'] }}">{{ $tarea->cantidad_periodo }} afectado(s)</span>
                                        @if ($tarea->cantidad_periodo != $tarea->cantidad_afectados)
                                            <span>en este periodo · {{ $tarea->cantidad_afectados }} en total</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($tarea->acciones ?? [] as $i => $accion)
                                    @if (($accion['tipo_accion'] ?? 'metodo') === 'link')
                                        <a href="{{ $accion['url'] }}" target="_blank" class="{{ $btn2 }}">
                                            <i class="fa fa-up-right-from-square text-[10px]"></i> {{ $accion['titulo'] }}
                                        </a>
                                    @else
                                        <button type="button" wire:click="ejecutar({{ $tarea->id }}, {{ $i }})" wire:loading.attr="disabled" class="{{ $btn2 }}">
                                            {{ $accion['titulo'] }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        @if ($tarea->subtareas->isNotEmpty())
                            <div class="border-t border-hairline px-4 py-2 pl-5">
                                <button type="button" @click="detalle = !detalle"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-ink underline-offset-2 hover:underline">
                                    <i class="fa fa-chevron-right text-[10px] text-steel transition-transform" :class="detalle ? 'rotate-90' : ''"></i>
                                    <span x-text="detalle ? 'Ocultar detalle' : 'Ver detalle'"></span>
                                    <span class="text-pewter tabular-nums">({{ $tarea->subtareas->count() }})</span>
                                </button>
                            </div>
                            <div x-show="detalle" x-collapse x-cloak class="divide-y divide-hairline border-t border-hairline">
                                @foreach ($tarea->subtareas as $sub)
                                    @php $es = $estados[$sub->variante] ?? $e; @endphp
                                    <div class="flex flex-wrap items-start justify-between gap-2 px-4 py-2.5 pl-5 hover:bg-fog/60" wire:key="sub-{{ $sub->id }}">
                                        <div class="flex min-w-0 flex-1 gap-2.5">
                                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $es['franja'] }}"></span>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-ink">
                                                    {{ $sub->titulo }}
                                                    @if ($sub->cantidad_afectados > 1)
                                                        <span class="font-normal text-pewter tabular-nums">({{ $sub->cantidad_afectados }})</span>
                                                    @endif
                                                </p>
                                                @if ($sub->descripcion)
                                                    <p class="text-xs text-graphite">{{ $sub->descripcion }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($sub->acciones ?? [] as $i => $accion)
                                                @if (($accion['tipo_accion'] ?? 'metodo') === 'link')
                                                    <a href="{{ $accion['url'] }}" target="_blank" class="{{ $btn2 }}">
                                                        <i class="fa fa-up-right-from-square text-[10px]"></i> {{ $accion['titulo'] }}
                                                    </a>
                                                @else
                                                    <button type="button" wire:click="ejecutar({{ $sub->id }}, {{ $i }})" wire:loading.attr="disabled" class="{{ $btnFantasma }}">
                                                        {{ $accion['titulo'] }}
                                                    </button>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-[10px] border border-mist bg-paper px-4 py-10 text-center">
                        <i class="fa fa-circle-check text-2xl text-green-700 dark:text-green-400"></i>
                        <p class="mt-2 text-sm text-graphite">No hay tareas pendientes en este periodo.</p>
                    </div>
                @endforelse
            </div>

            <div class="flex justify-end gap-3 border-t border-hairline px-4 py-3 md:px-6">
                <button type="button" wire:click="$set('mostrarFormularioTareasPendientes', false)" class="{{ $btn2 }} h-9 px-4 text-sm">
                    Cerrar
                </button>
            </div>
        </div>
    </x-modal>
</div>

@script
    <script>
        Alpine.data('tareaPendienteBubble', () => ({
            top: 0,
            left: 0,
            dragging: false,
            moved: false,
            offsetX: 0,
            offsetY: 0,
            storageKey: 'tareas-pendientes-bubble-position',
            bubbleSize: 56, // w-14 = 3.5rem = 56px

            init() {
                this.restorePosition();
                window.addEventListener('resize', () => this.clampToViewport());
            },

            defaultPosition() {
                // Esquina superior derecha, a 3/4 de la altura de la pantalla
                return {
                    left: window.innerWidth - this.bubbleSize - 24,
                    top: window.innerHeight * 0.75,
                };
            },

            restorePosition() {
                try {
                    const raw = localStorage.getItem(this.storageKey);
                    if (raw) {
                        const pos = JSON.parse(raw);
                        if (typeof pos.top === 'number' && typeof pos.left === 'number') {
                            this.top = pos.top;
                            this.left = pos.left;
                            this.clampToViewport();
                            return;
                        }
                    }
                } catch (e) {
                    // localStorage corrupto o no disponible: se usa el valor por defecto
                }

                const def = this.defaultPosition();
                this.top = def.top;
                this.left = def.left;
            },

            savePosition() {
                try {
                    localStorage.setItem(this.storageKey, JSON.stringify({
                        top: this.top,
                        left: this.left
                    }));
                } catch (e) {
                    // modo privado, cuota excedida, etc. — simplemente no persiste
                }
            },

            clampToViewport() {
                const maxLeft = Math.max(window.innerWidth - this.bubbleSize, 0);
                const maxTop = Math.max(window.innerHeight - this.bubbleSize, 0);
                this.left = Math.min(Math.max(this.left, 0), maxLeft);
                this.top = Math.min(Math.max(this.top, 0), maxTop);
            },

            startDrag(event) {
                this.dragging = true;
                this.moved = false;

                const point = event.touches ? event.touches[0] : event;
                this.offsetX = point.clientX - this.left;
                this.offsetY = point.clientY - this.top;

                const onMove = (e) => this.onDrag(e);
                const onEnd = () => this.endDrag(onMove, onEnd);

                window.addEventListener('mousemove', onMove);
                window.addEventListener('touchmove', onMove, {
                    passive: false
                });
                window.addEventListener('mouseup', onEnd);
                window.addEventListener('touchend', onEnd);
            },

            onDrag(event) {
                if (!this.dragging) return;
                if (event.touches) event.preventDefault(); // evita scroll de la página al arrastrar en móvil

                const point = event.touches ? event.touches[0] : event;
                const newLeft = point.clientX - this.offsetX;
                const newTop = point.clientY - this.offsetY;

                if (Math.abs(newLeft - this.left) > 3 || Math.abs(newTop - this.top) > 3) {
                    this.moved = true; // más de unos px ya cuenta como arrastre, no clic
                }

                this.left = newLeft;
                this.top = newTop;
                this.clampToViewport();
            },

            endDrag(onMove, onEnd) {
                this.dragging = false;

                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('touchmove', onMove);
                window.removeEventListener('mouseup', onEnd);
                window.removeEventListener('touchend', onEnd);

                if (this.moved) {
                    this.savePosition();
                }
            },

            onClick() {
                // Si hubo arrastre justo antes, no se interpreta como clic
                if (this.moved) {
                    this.moved = false;
                    return;
                }

                $wire.set('mostrarFormularioTareasPendientes', true);
            },
        }));
    </script>
@endscript
