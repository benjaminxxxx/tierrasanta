@props(['label', 'value'])

<div {{ $attributes->merge(['class' => 'flex items-center justify-between px-4 py-2 bg-muted rounded-md border border-border']) }}>
    <span class="text-xs font-bold text-muted-foreground uppercase">
        {{ $label }}
    </span>
    <span class="text-base font-semibold text-card-foreground">
        {{ $value }}
    </span>
</div>