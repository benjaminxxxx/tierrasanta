{{-- Componente anónimo: x-rda-badge --}}
{{-- resources/views/components/rda-badge.blade.php --}}
@props(['color'])

<span class="inline-block rounded-[5px] border px-2 py-0.5 text-[11px] font-bold tracking-[0.3px] whitespace-nowrap"
      style="background: {{ $color }}; color: #000; border-color: {{ $color }}">
    {{ $slot }}
</span>