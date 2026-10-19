<div>
    <x-dialog-modal wire:model="mostrarFormularioLabores" maxWidth="full">
        <x-slot name="title">
            Lista de Labores
        </x-slot>
        <x-slot name="content">
            @php
                // Un color por grupo de mano de obra (clases completas para que Tailwind las incluya)
                $paleta = [
                    'border-l-emerald-500 bg-emerald-50 dark:bg-emerald-900/20',
                    'border-l-sky-500 bg-sky-50 dark:bg-sky-900/20',
                    'border-l-violet-500 bg-violet-50 dark:bg-violet-900/20',
                    'border-l-amber-500 bg-amber-50 dark:bg-amber-900/20',
                    'border-l-rose-500 bg-rose-50 dark:bg-rose-900/20',
                    'border-l-teal-500 bg-teal-50 dark:bg-teal-900/20',
                    'border-l-indigo-500 bg-indigo-50 dark:bg-indigo-900/20',
                    'border-l-lime-500 bg-lime-50 dark:bg-lime-900/20',
                    'border-l-fuchsia-500 bg-fuchsia-50 dark:bg-fuchsia-900/20',
                    'border-l-cyan-500 bg-cyan-50 dark:bg-cyan-900/20',
                    'border-l-orange-500 bg-orange-50 dark:bg-orange-900/20',
                    'border-l-pink-500 bg-pink-50 dark:bg-pink-900/20',
                    'border-l-blue-500 bg-blue-50 dark:bg-blue-900/20',
                ];
            @endphp

            {{-- x-data sin datos del servidor: si su atributo cambiara al abrir el modal (Livewire lo actualiza), Alpine crearía
                 otro componente y el buscador quedaría unido al anterior (escribir no filtraba). Código y nombre van en data-*. --}}
            <div x-data="{
                q: '',
                copiado: null,
                coincide(el) { return this.visible(el.dataset.codigo, el.dataset.nombre); },
                hayResultados() { return [...this.$root.querySelectorAll('[data-codigo]')].some(el => this.coincide(el)); },
                norm(t) { return (t ?? '').toString().toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, ''); },
                visible(codigo, nombre) {
                    const q = this.norm(this.q).trim();
                    if (!q) return true;
                    // Un número busca el código exacto o que empiece así; texto busca en el nombre
                    if (/^\d+$/.test(q)) return codigo.startsWith(q);
                    return this.norm(nombre).includes(q) || codigo === q;
                },
                copiar(codigo) {
                    try { navigator.clipboard?.writeText(codigo); } catch (e) {}
                    this.copiado = codigo;
                    setTimeout(() => { if (this.copiado === codigo) this.copiado = null; }, 1200);
                },
            }" x-init="$wire.$watch('mostrarFormularioLabores', v => v && setTimeout(() => $refs.buscador?.focus(), 150))" class="space-y-4">

                <div class="flex flex-wrap items-center gap-3 sticky top-0 z-10 bg-card py-2">
                    <div class="relative flex-1 min-w-[16rem]">
                        <i class="fa fa-search absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"></i>
                        <input type="search" x-ref="buscador" x-model.debounce.150ms="q"
                            placeholder="Busca por código (97) o por nombre (poda, riego…)"
                            class="w-full h-10 pl-9 pr-3 rounded-md border border-input bg-background text-foreground text-sm">
                    </div>
                    <div class="flex flex-wrap gap-3 text-xs text-muted-foreground">
                        <span><i class="fa fa-coins text-amber-500"></i> Tiene bono por tramos</span>
                        <span><i class="fa fa-bullseye text-sky-500"></i> Estándar de producción</span>
                        <span><i class="fa fa-user-clock text-rose-500"></i> Suspensión: va a FDM</span>
                        <span>Clic en una labor para copiar su código</span>
                    </div>
                </div>

                @foreach ($grupos as $i => $grupo)
                    @php
                        $estilo = $grupo['suspension']
                            ? 'border-l-rose-500 bg-rose-50 dark:bg-rose-900/20'
                            : $paleta[$i % count($paleta)];
                    @endphp
                    <section wire:key="grupo-{{ $grupo['clave'] }}"
                        x-show="[...$el.querySelectorAll('[data-codigo]')].some(el => coincide(el))"
                        class="rounded-lg border border-border border-l-4 {{ $estilo }} p-3">
                        <h3 class="font-semibold text-sm text-foreground mb-2 flex items-center gap-2">
                            @if ($grupo['suspension'])
                                <i class="fa fa-user-clock text-rose-500"></i>
                            @endif
                            {{ $grupo['nombre'] }}
                            <span class="text-xs font-normal text-muted-foreground">
                                (<span x-text="[...$el.closest('section').querySelectorAll('[data-codigo]')].filter(el => coincide(el)).length"></span>/{{ count($grupo['labores']) }})
                            </span>
                        </h3>
                        @if ($grupo['suspension'])
                            <p class="text-xs text-muted-foreground mb-2">
                                Para días no trabajados (o parte del día): asistencia A, campo FDM y este código. También
                                se puede poner directamente el código de asistencia (DM, V, FR…) con su total de horas.
                            </p>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-1.5">
                            @foreach ($grupo['labores'] as $labor)
                                <button type="button" wire:key="labor-{{ $labor['codigo'] }}"
                                    data-codigo="{{ $labor['codigo'] }}" data-nombre="{{ $labor['nombre'] }}"
                                    x-show="coincide($el)"
                                    @click="copiar(@js($labor['codigo']))"
                                    class="flex items-center gap-2 text-left rounded-md bg-background/80 hover:bg-background border border-border px-2 py-1.5 transition">
                                    <span class="shrink-0 min-w-[2.75rem] text-center font-mono font-bold text-sm rounded px-1.5 py-0.5 border border-black/10 text-gray-900"
                                        style="background-color: {{ $labor['color'] ?: '#E5E7EB' }}">
                                        {{ $labor['codigo'] }}
                                    </span>
                                    <span class="flex-1 min-w-0">
                                        <span class="block text-sm text-foreground truncate" title="{{ $labor['nombre'] }}">{{ $labor['nombre'] }}</span>
                                        @if ($labor['asistencia'])
                                            <span class="block text-xs text-rose-700 dark:text-rose-300">= {{ $labor['asistencia'] }} {{ $labor['asistencia_nombre'] }}</span>
                                        @endif
                                    </span>
                                    <span class="shrink-0 flex items-center gap-1.5 text-xs">
                                        @if ($labor['bono'])
                                            <i class="fa fa-coins text-amber-500"
                                                title="Bono por tramos {{ $labor['paga_con_jornal'] ? '(se paga con el jornal)' : '(se acumula)' }}"></i>
                                        @endif
                                        @if ($labor['estandar'])
                                            <i class="fa fa-bullseye text-sky-500" title="Estándar: {{ $labor['estandar'] }}"></i>
                                        @endif
                                        <span x-show="copiado === @js($labor['codigo'])" x-cloak class="text-green-600"><i class="fa fa-check"></i></span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <p x-show="q && !hayResultados()" x-cloak
                    class="text-sm text-muted-foreground text-center py-6">
                    Ninguna labor coincide con "<span x-text="q"></span>".
                </p>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-button type="button" wire:click="$set('mostrarFormularioLabores', false)">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>
</div>
