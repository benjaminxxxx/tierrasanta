{{-- Escritorio de archivos (DESIGN.md): íconos de Excel estilo escritorio. Clic = descargar; soltar el archivo encima = subir. --}}
<div class="rounded-[10px] border border-mist bg-paper p-5">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h2 class="text-base font-semibold">Archivos</h2>
            <p class="text-xs text-pewter">Clic en un archivo para bajarlo con los datos de hoy. Para subirlo, suéltalo sobre su ícono o usa "Subir".</p>
        </div>
        <span wire:loading wire:target="archivo" class="text-xs text-graphite"><i class="fa fa-spinner fa-spin"></i> Subiendo y revisando…</span>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-5 xl:grid-cols-6">
        @foreach ($archivos as $a)
            <div wire:key="archivo-{{ $a['clave'] }}"
                x-data="{ encima: false, async subir(archivo) { if (!archivo) return; await $wire.set('destino', @js($a['clave'])); $wire.upload('archivo', archivo); } }"
                @if ($a['subible'])
                    @dragover.prevent="encima = true" @dragleave.prevent="encima = false"
                    @drop.prevent="encima = false; subir($event.dataTransfer.files[0])"
                @endif
                :class="encima ? 'border-carmin bg-carmin/5' : 'border-transparent'"
                class="group flex flex-col items-center rounded-[10px] border-2 border-dashed px-2 pb-3 pt-4 text-center transition-colors">
                <a href="{{ $a['url'] }}" title="Descargar {{ $a['archivo'] }}"
                    class="flex flex-col items-center rounded-[10px] px-3 py-2 hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25">
                    {{-- Ícono de archivo Excel --}}
                    <span class="relative inline-flex h-14 w-12 items-end justify-center">
                        <i class="fa fa-file text-[52px] leading-none text-green-700 dark:text-green-500"></i>
                        <span class="absolute bottom-2 left-1/2 -translate-x-1/2 rounded-[3px] bg-white px-1 text-[10px] font-bold leading-4 text-green-700">XLS</span>
                    </span>
                    <span class="mt-2 text-sm font-medium leading-tight text-ink">{{ $a['nombre'] }}</span>
                </a>
                @if ($a['estado'])
                    <span class="mt-1 text-[11px] leading-tight {{ $a['estado']['al_dia'] ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400' }}">
                        <i class="fa {{ $a['estado']['al_dia'] ? 'fa-check' : 'fa-rotate' }}"></i>
                        Bajado {{ $a['estado']['cuando'] }}{{ $a['estado']['al_dia'] ? ' · al día' : ' · cambió: bájalo de nuevo' }}
                    </span>
                @endif
                @if ($a['subible'])
                    <label class="mt-1 cursor-pointer text-[11px] text-graphite underline-offset-4 hover:text-ink hover:underline">
                        <i class="fa fa-upload"></i> Subir
                        <input type="file" accept=".xlsx,.xlsm" class="hidden" @change="subir($event.target.files[0]); $event.target.value = ''" />
                    </label>
                @else
                    <span class="mt-1 text-[11px] leading-tight text-pewter">{{ $a['nota'] }}</span>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Vista previa y guardado de cada libro (se abre al soltar o subir su archivo) --}}
    @foreach ($archivos as $a)
        @if ($a['subible'])
            <livewire:sistema.sistema-libro-excel-component :libro="$a['clave']" :escritorio="true" wire:key="libro-{{ $a['clave'] }}" />
        @endif
    @endforeach
</div>
