@php
    // Secciones del informe: id => [título, parcial]. El orden y cuáles están abiertas los recuerda cada navegador.
    $secciones = [
        'info-general' => ['Información general', 'livewire.campania.partials.campania-x-campo-selector-informacion-general'],
        'poblacion-plantas' => ['Población de plantas', 'livewire.campania.partials.campania-x-campo-poblacion-plantas'],
        'brotes' => ['Brotes', 'livewire.campania.partials.campania-x-campo-brotes'],
        'infestacion' => ['Infestación', 'livewire.campania.partials.campania-x-campo-infestacion'],
        'reinfestacion' => ['Reinfestación', 'livewire.campania.partials.campania-x-campo-reinfestacion'],
        'cosechamadres' => ['Cosecha de madres', 'livewire.campania.partials.campania-x-campo-cosecha-madres'],
        'cosecha' => ['Cosecha', 'livewire.campania.partials.campania-x-campo-cosecha'],
        'riego' => ['Riego', 'livewire.campania.partials.campania-x-campo-riego'],
        'nutrientes' => ['Nutrientes', 'livewire.campania.partials.campania-x-campo-nutrientes'],
        'analisis_financiero' => ['Análisis financiero', 'livewire.campania.partials.campania-x-campo-analisis-financiero'],
    ];
@endphp
<div x-data="informeCampania(@js(array_keys($secciones)))">
    @if ($lineaTiempo)
        @include('livewire.campania.partials.campania-linea-tiempo', ['lineaTiempo' => $lineaTiempo])
    @endif

    {{-- Barra de herramientas --}}
    <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
        <span class="text-xs text-muted-foreground">Arrastra una sección por <i class="fa fa-grip-vertical"></i> (o usa las flechas) para cambiar su orden.</span>
        <div class="flex flex-wrap gap-2">
            <x-button size="xs" variant="outline" @click="compacto = !compacto; guardar()">
                <i class="fa" :class="compacto ? 'fa-expand' : 'fa-compress'"></i>
                <span x-text="compacto ? 'Vista normal' : 'Vista compacta'"></span>
            </x-button>
            <x-button size="xs" variant="outline" @click="abrirTodo()"><i class="fa fa-folder-open"></i> Abrir todo</x-button>
            <x-button size="xs" variant="outline" @click="cerrarTodo()"><i class="fa fa-folder"></i> Cerrar todo</x-button>
            <x-button size="xs" variant="ghost" @click="restablecer()" title="Volver al orden original"><i class="fa fa-undo"></i> Orden original</x-button>
        </div>
    </div>

    {{-- Secciones: el orden se aplica con CSS (order), así no se vuelve a pintar nada al reordenar --}}
    <div class="mt-2 flex flex-col gap-2" :class="compacto ? '[&_td]:!py-0.5 [&_th]:!py-1 text-xs' : ''">
        @foreach ($secciones as $id => [$titulo, $parcial])
            <section class="rounded-lg border border-border bg-card shadow-sm overflow-hidden" wire:key="sec-{{ $id }}"
                :style="{ order: orden.indexOf('{{ $id }}') }"
                :class="arrastrando === '{{ $id }}' ? 'opacity-50' : (sobre === '{{ $id }}' ? 'ring-2 ring-primary' : '')"
                @dragover.prevent="sobre = '{{ $id }}'" @dragleave="sobre = null" @drop.prevent="soltar('{{ $id }}')">
                <header class="flex items-center gap-2 px-3 py-2 cursor-pointer select-none hover:bg-muted/60"
                    :class="abierta('{{ $id }}') ? 'bg-muted/60 border-b border-border' : ''" @click="alternar('{{ $id }}')">
                    <span class="cursor-grab text-muted-foreground px-1" draggable="true" title="Arrastrar para ordenar"
                        @click.stop @dragstart="arrastrando = '{{ $id }}'" @dragend="arrastrando = null; sobre = null">
                        <i class="fa fa-grip-vertical"></i>
                    </span>
                    <span class="font-semibold text-sm text-foreground uppercase tracking-wide">{{ $titulo }}</span>
                    <span class="ml-auto flex items-center gap-1">
                        <button type="button" class="px-1.5 text-muted-foreground hover:text-foreground disabled:opacity-30" title="Subir"
                            @click.stop="mover('{{ $id }}', -1)" :disabled="orden.indexOf('{{ $id }}') === 0"><i class="fa fa-arrow-up text-xs"></i></button>
                        <button type="button" class="px-1.5 text-muted-foreground hover:text-foreground disabled:opacity-30" title="Bajar"
                            @click.stop="mover('{{ $id }}', 1)" :disabled="orden.indexOf('{{ $id }}') === orden.length - 1"><i class="fa fa-arrow-down text-xs"></i></button>
                        <i class="fa fa-chevron-down text-xs text-muted-foreground transition-transform ml-1" :class="abierta('{{ $id }}') ? 'rotate-180' : ''"></i>
                    </span>
                </header>
                <div x-show="abierta('{{ $id }}')" x-collapse>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-[11px] uppercase text-muted-foreground">
                                <tr class="border-b border-border">
                                    <th class="px-3 py-1.5 text-left font-medium">Concepto</th>
                                    <th class="px-3 py-1.5 text-right font-medium w-36">Datos</th>
                                    <th class="px-3 py-1.5 text-right font-medium w-36">Datos/ha</th>
                                    <th class="px-3 py-1.5 font-medium w-56"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @include($parcial)
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endforeach
    </div>

    <livewire:evaluacion.evaluacion-poblacion-planta-form-component />
    <livewire:evaluacion.evaluacion-brotes-form-component />
    <livewire:evaluacion.evaluacion-infestacion-form-component />
    <livewire:evaluacion.evaluacion-reinfestacion-form-component />
    <livewire:riego.riego-campania-form-component />

    <livewire:campo.siembra-form-component />
    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('informeCampania', (porDefecto) => ({
            orden: [...porDefecto],
            abiertas: [],
            compacto: false,
            arrastrando: null,
            sobre: null,
            CLAVE: 'campania-informe-secciones',

            init() {
                try {
                    const guardado = JSON.parse(localStorage.getItem(this.CLAVE) || '{}');
                    // Orden guardado + secciones nuevas que no estaban cuando se guardó
                    const orden = (guardado.orden || []).filter(id => porDefecto.includes(id));
                    this.orden = [...orden, ...porDefecto.filter(id => !orden.includes(id))];
                    this.compacto = !!guardado.compacto;
                    this.abiertas = JSON.parse(sessionStorage.getItem(this.CLAVE + '-abiertas') || '["info-general"]');
                } catch (e) { /* almacenamiento bloqueado: se usa el orden original */ }
            },
            guardar() {
                try {
                    localStorage.setItem(this.CLAVE, JSON.stringify({ orden: this.orden, compacto: this.compacto }));
                    sessionStorage.setItem(this.CLAVE + '-abiertas', JSON.stringify(this.abiertas));
                } catch (e) { }
            },
            abierta(id) { return this.abiertas.includes(id); },
            alternar(id) {
                this.abiertas = this.abierta(id) ? this.abiertas.filter(x => x !== id) : [...this.abiertas, id];
                this.guardar();
            },
            abrirTodo() { this.abiertas = [...this.orden]; this.guardar(); },
            cerrarTodo() { this.abiertas = []; this.guardar(); },
            mover(id, paso) {
                const i = this.orden.indexOf(id), j = i + paso;
                if (j < 0 || j >= this.orden.length) return;
                const nuevo = [...this.orden];
                [nuevo[i], nuevo[j]] = [nuevo[j], nuevo[i]];
                this.orden = nuevo;
                this.guardar();
            },
            soltar(destino) {
                const origen = this.arrastrando;
                this.arrastrando = null; this.sobre = null;
                if (!origen || origen === destino) return;
                const nuevo = this.orden.filter(x => x !== origen);
                nuevo.splice(nuevo.indexOf(destino), 0, origen);
                this.orden = nuevo;
                this.guardar();
            },
            restablecer() { this.orden = [...porDefecto]; this.guardar(); },
        }));
    </script>
@endscript
