{{-- cochinilla-infestacion-stats-component.blade.php --}}
<div class="space-y-3">
    <p class="text-xs font-semibold uppercase text-muted-foreground tracking-wider">
        Cochinilla — Infestaciones del mes
    </p>

    {{-- Fila 1: conteos por tipo --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <x-stats-card
            title="Total registros"
            :value="$this->e('cochinilla_total_infestaciones')"
            icon="fa-bug"
            description="Infestaciones + reinfestaciones"
            :trend="$this->t('cochinilla_total_infestaciones')"
            trendLabel="vs mes anterior" />

        <x-stats-card
            title="Campos infestados"
            :value="$this->e('cochinilla_campos_infestacion')"
            icon="fa-map"
            description="Campos con 1ra infestación"
            :trend="$this->t('cochinilla_campos_infestacion')"
            trendLabel="vs mes anterior" />

        <x-stats-card
            title="Campos reinfestados"
            :value="$this->e('cochinilla_campos_reinfestacion')"
            icon="fa-redo"
            description="Campos con refuerzo de material"
            :trend="$this->t('cochinilla_campos_reinfestacion')"
            trendLabel="vs mes anterior" />

        <x-stats-card
            title="Área total infestada"
            :value="number_format($this->e('cochinilla_area_infestacion') + $this->e('cochinilla_area_reinfestacion'), 2) . ' ha'"
            icon="fa-layer-group"
            description="Infestación + reinfestación"
            :trend="$this->t('cochinilla_area_infestacion')"
            trendLabel="vs mes anterior" />
    </div>

    {{-- Fila 2: KG madres --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <x-stats-card
            title="KG Madres (infest.)"
            :value="number_format($this->e('cochinilla_kg_madres_infestacion'), 0)"
            icon="fa-weight"
            description="Material usado en 1ra infestación"
            :trend="$this->t('cochinilla_kg_madres_infestacion')"
            trendLabel="vs mes anterior" />

        <x-stats-card
            title="KG Madres (reinfest.)"
            :value="number_format($this->e('cochinilla_kg_madres_reinfestacion'), 0)"
            icon="fa-weight-hanging"
            description="Material de refuerzo"
            :trend="$this->t('cochinilla_kg_madres_reinfestacion')"
            trendLabel="vs mes anterior" />

        <x-stats-card
            title="KG Madres / Ha"
            :value="$this->e('cochinilla_kg_madres_ha_promedio')"
            icon="fa-chart-area"
            description="Densidad promedio del mes"
            :trend="$this->t('cochinilla_kg_madres_ha_promedio')"
            trendLabel="vs mes anterior" />

        <x-stats-card
            title="Infestadores / Ha"
            :value="number_format($this->e('cochinilla_infestadores_ha_promedio'))"
            icon="fa-th"
            description="Cobertura promedio del mes"
            :trend="$this->t('cochinilla_infestadores_ha_promedio')"
            trendLabel="vs mes anterior" />
    </div>

    {{-- Fila 3: métodos --}}
    <div class="grid grid-cols-3 gap-4">
        <x-stats-card
            title="Malla"
            :value="$this->e('cochinilla_malla_count')"
            icon="fa-grip-lines"
            description="Registros con método malla"
            :trend="$this->t('cochinilla_malla_count')"
            trendLabel="vs mes anterior" />

        <x-stats-card
            title="Tubo"
            :value="$this->e('cochinilla_tubo_count')"
            icon="fa-grip-lines-vertical"
            description="Registros con método tubo"
            :trend="$this->t('cochinilla_tubo_count')"
            trendLabel="vs mes anterior" />

        <x-stats-card
            title="Cartón"
            :value="$this->e('cochinilla_carton_count')"
            icon="fa-box"
            description="Registros con método cartón"
            :trend="$this->t('cochinilla_carton_count')"
            trendLabel="vs mes anterior" />
    </div>
</div>