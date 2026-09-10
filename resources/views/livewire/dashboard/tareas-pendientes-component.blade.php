<div>
    <div x-data="tareaPendienteBubble()" @mousedown="startDrag($event)" @touchstart="startDrag($event)"
        :style="`position: fixed; top: ${top}px; left: ${left}px; z-index: 9999; cursor: ${dragging ? 'grabbing' : 'grab'};`"
        class="select-none">
        <button type="button" @click="onClick()"
            class="w-14 h-14 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg flex items-center justify-center opacity-70 hover:opacity-100 transition-all active:scale-95"
            title="Tareas pendientes">
            <i class="fa fa-list-check text-xl"></i>
        </button>
    </div>
    <x-dialog-modal wire:model="mostrarFormularioTareasPendientes" maxWidth="full">
        <x-slot name="title">
            <x-flex class="justify-between">
                <x-title>
                    Tareas pendientes del sistema
                </x-title>
                <x-button wire:click="detectarTareasPendientes">
                    <i class="fa fa-search"></i> Buscar tareas pendientes
                </x-button>
            </x-flex>
        </x-slot>
        <x-slot name="content">
            @php
                // Único lugar del sistema con clases de diseño para tareas pendientes —
                // escritas literales, así el compilador de Tailwind las detecta.
                $variantes = [
                    'info' => 'bg-blue-50 border-blue-300 text-blue-800',
                    'warning' => 'bg-amber-50 border-amber-300 text-amber-800',
                    'danger' => 'bg-red-50 border-red-300 text-red-800',
                    'success' => 'bg-green-50 border-green-300 text-green-800',
                ];
            @endphp

            @forelse ($tareas as $tarea)
                <div class="border rounded-lg p-4 mb-3 {{ $variantes[$tarea->variante] ?? $variantes['warning'] }}">
                    <div class="flex justify-between items-start gap-4">
                        <div>
                            <p class="font-semibold">{{ $tarea->titulo }}</p>
                            <p class="text-sm">{{ $tarea->descripcion }}</p>
                            <p class="text-xs mt-1 opacity-75">{{ $tarea->cantidad_afectados }} registro(s) afectado(s)</p>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            @foreach ($tarea->acciones ?? [] as $i => $accion)
                                <x-button wire:click="ejecutar({{ $tarea->id }}, {{ $i }})">
                                    {{ $accion['titulo'] }}
                                </x-button>
                            @endforeach
                        </div>
                    </div>

                    @if ($tarea->subtareas->isNotEmpty())
                        <div class="mt-3 pl-4 border-l-2 border-current/30 space-y-2">
                            @foreach ($tarea->subtareas as $sub)
                                <div class="flex justify-between items-center text-sm">
                                    <span>{{ $sub->titulo }} ({{ $sub->cantidad_afectados }})</span>
                                    <div class="flex gap-2">
                                        @foreach ($sub->acciones ?? [] as $i => $accion)
                                            <x-button wire:click="ejecutar({{ $sub->id }}, {{ $i }})" variant="secondary">
                                                {{ $accion['titulo'] }}
                                            </x-button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">No hay tareas pendientes.</p>
            @endforelse
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarFormularioTareasPendientes', false)">
                Cerrar
            </x-button>
        </x-slot>
    </x-dialog-modal>
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
                localStorage.setItem(this.storageKey, JSON.stringify({ top: this.top, left: this.left }));
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
            window.addEventListener('touchmove', onMove, { passive: false });
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