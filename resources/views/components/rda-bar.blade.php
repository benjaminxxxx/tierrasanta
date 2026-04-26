{{-- Componente anónimo: x-rda-bar --}}
{{-- resources/views/components/rda-bar.blade.php --}}
@props(['pct', 'color'])

<div class="h-[5px] overflow-hidden rounded-full bg-white/[0.07]">
    <div class="h-full min-w-[2px] rounded-full transition-[width] duration-500 ease-out"
         style="width: {{ $pct }}%; background: {{ $color }}">
    </div>
</div>