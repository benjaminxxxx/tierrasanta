@props([
    'numero'      => 1,
    'titulo'      => '',
    'descripcion' => '',
])

<div
    x-data="{ numero: {{ $numero }} }"
    class="flex-1 flex flex-col items-center px-2"
>
    {{-- Rail --}}
    <div class="flex items-center w-full mb-3">

        {{-- Línea izquierda --}}
        <div
            class="flex-1 h-0.5 rounded-sm transition-colors duration-300"
            :class="{
                'bg-transparent':                        numero === 1,
                'opacity-40 bg-[var(--pt-done)]':        numero <= pasoActivo && numero !== 1,
                'bg-[var(--border)]':                    numero > pasoActivo,
            }"
        ></div>

        {{-- Círculo --}}
        <div
            class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold border-2 shrink-0 z-10 transition-all duration-300 relative"
            :class="{
                'bg-green-900/20 border-green-600 text-transparent':         numero < pasoActivo,
                'bg-amber-500/10 border-amber-400 text-amber-400 shadow-[0_0_0_5px_theme(colors.amber.500/15)] animate-pulse': numero === pasoActivo,
                'opacity-40 bg-[var(--secondary)] border-[var(--border)] text-[var(--muted-foreground)]': numero > pasoActivo,
            }"
        >
            {{-- Checkmark SVG cuando completado --}}
            <svg
                x-show="numero < pasoActivo"
                class="w-3.5 h-3.5 text-green-500"
                fill="none" viewBox="0 0 12 9" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round"
            >
                <polyline points="1,4.5 4.5,8 11,1"/>
            </svg>

            <span x-show="numero >= pasoActivo">{{ $numero }}</span>
        </div>

        {{-- Línea derecha --}}
        <div
            class="flex-1 h-0.5 rounded-sm transition-colors duration-300"
            :class="{
                'bg-transparent':                        numero === 3,
                'opacity-40 bg-[var(--pt-done)]':        numero < pasoActivo,
                'bg-[var(--border)]':                    numero >= pasoActivo,
            }"
        ></div>

    </div>

    {{-- Contenido --}}
    <div class="text-center w-full">

        <div
            class="text-[0.68rem] uppercase font-semibold tracking-wide mb-1 transition-colors duration-300"
            :class="{
                'text-green-500':                numero < pasoActivo,
                'text-amber-400':                numero === pasoActivo,
                'text-[var(--muted-foreground)] opacity-50': numero > pasoActivo,
            }"
        >
            {{ $titulo }}
        </div>

        <div
            class="mt-2 rounded-[var(--radius)] px-4 py-3 border border-transparent transition-all duration-300"
            :class="{
                'opacity-50': numero > pasoActivo,
            }"
        >
            @if ($descripcion)
                <p class="text-sm text-[var(--muted-foreground)]">{{ $descripcion }}</p>
            @endif

            @if ($slot->isNotEmpty())
                <div class="mt-3">
                    {{ $slot }}
                </div>
            @endif
        </div>

    </div>
</div>