{{-- Componente anónimo: x-rda-metric --}}
{{-- resources/views/components/rda-metric.blade.php --}}
@props(['label', 'pct' => null, 'valueClass' => 'text-slate-200'])

<div class="flex flex-col gap-0.5 bg-muted px-5 py-4">
    <span class="{{ $valueClass }} text-[26px] font-bold leading-none tracking-tight">
        {{ $slot }}
    </span>
    <span class="text-[11px] uppercase tracking-wide text-muted-foreground">{{ $label }}</span>
    @if($pct !== null)
        <span class="mt-0.5 text-xs font-semibold {{ $valueClass }}">{{ $pct }}%</span>
    @endif
</div>