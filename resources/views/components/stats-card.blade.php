@props([
    'title' => 'Estadística',
    'value' => '0',
    'icon' => 'fa-chart-line',
    'description' => 'Descripción',
    'trend' => 0,
    'trendLabel' => 'Cambio',
    'isPositive' => false,
])

<x-card>
    <!-- Header con Icono -->
    <div class="flex items-start justify-between mb-4">
        <div>
            <p class="text-card-foreground text-sm font-medium mb-1">{{ $title }}</p>
            <p class="text-3xl font-bold text-muted-foreground">{{ $value }}</p>
        </div>
        <div class="bg-muted p-3 rounded-lg">
            <i class="fas {{ $icon }} text-muted-foreground text-xl"></i>
        </div>
    </div>

    <!-- Descripción -->
    <p class="text-card-foreground text-xs mb-4">{{ $description }}</p>

    <!-- Trend -->
    <div class="flex items-center gap-2 pt-4 border-t border-border">
        @if ($trend >= 0 && $isPositive)
            <span class="text-green-500 text-sm font-semibold flex items-center gap-1">
                <i class="fas fa-arrow-up text-xs"></i>
                {{ abs($trend) }}%
            </span>
        @elseif ($trend < 0 && $isPositive)
            <span class="text-green-500 text-sm font-semibold flex items-center gap-1">
                <i class="fas fa-arrow-down text-xs"></i>
                {{ abs($trend) }}%
            </span>
        @elseif ($trend >= 0)
            <span class="text-green-500 text-sm font-semibold flex items-center gap-1">
                <i class="fas fa-arrow-up text-xs"></i>
                {{ $trend }}%
            </span>
        @else
            <span class="text-red-500 text-sm font-semibold flex items-center gap-1">
                <i class="fas fa-arrow-down text-xs"></i>
                {{ abs($trend) }}%
            </span>
        @endif
        <span class="text-slate-500 text-xs">{{ $trendLabel }}</span>
    </div>
</x-card>