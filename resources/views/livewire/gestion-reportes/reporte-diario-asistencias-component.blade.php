<div wire:key="asistencias-{{ $fecha }}" x-data="asistenciasChart(@js($totales), @js($totalPlanilla))" x-init="init()"
    @resumen-actualizado.window="refrescar($event.detail.totales)" class="rda-wrapper">

    {{-- ══ HEADER ══════════════════════════════════════════════════════ --}}
    <div class="rda-header">
        <div class="rda-header-left">
            <span class="rda-icon">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 20h5v-2a4 4 0 00-5.356-3.765M9 20H4v-2a4 4 0 015.356-3.765m0 0A4 4 0 1112 6a4 4 0 012.644 9.235m-5.288 0A4 4 0 009 20" />
                </svg>
            </span>
            <div>
                <h2 class="rda-title">Asistencias del día</h2>
                <p class="rda-subtitle">
                    {{ \Carbon\Carbon::parse($fecha)->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </p>
            </div>
        </div>
        <div class="rda-header-actions">
            <button wire:click="actualizar" class="rda-btn rda-btn-ghost" title="Actualizar datos">
                <span wire:loading wire:target="actualizar" class="rda-spin">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5">
                        <path d="M21 12a9 9 0 11-6.219-8.56" />
                    </svg>
                </span>
                <span wire:loading.remove wire:target="actualizar">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </span>
                <span wire:loading.remove wire:target="actualizar">Actualizar</span>
                <span wire:loading wire:target="actualizar">Actualizando...</span>
            </button>
            <button class="rda-btn rda-btn-export" title="Exportar (próximamente)" disabled>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Exportar
            </button>
        </div>
    </div>

    {{-- ══ SIN DATOS ════════════════════════════════════════════════════ --}}
    @if($sinDatos)
        <div class="rda-empty">
            <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"
                opacity="0.35">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <p>No hay resumen generado para esta fecha.</p>
            <span>Genera el reporte desde el módulo de planilla diaria.</span>
        </div>
    @else

        {{-- ══ MÉTRICAS RÁPIDAS ════════════════════════════════════════ --}}
        <div class="rda-metrics">
            <div class="rda-metric rda-metric-total">
                <span class="rda-metric-value">{{ $totalPlanilla }}</span>
                <span class="rda-metric-label">Total planilla</span>
            </div>
            @php
                $asistidos = collect($totales)->where('acumula', 1)->sum('total');
                $ausentes = $totalPlanilla - $asistidos;
                $pctAsist = $totalPlanilla > 0 ? round(($asistidos / $totalPlanilla) * 100, 1) : 0;
            @endphp
            <div class="rda-metric rda-metric-ok">
                <span class="rda-metric-value">{{ $asistidos }}</span>
                <span class="rda-metric-label">Acumulan asistencia</span>
                <span class="rda-metric-pct">{{ $pctAsist }}%</span>
            </div>
            <div class="rda-metric rda-metric-out">
                <span class="rda-metric-value">{{ $ausentes }}</span>
                <span class="rda-metric-label">Sin acumular</span>
                <span
                    class="rda-metric-pct">{{ $totalPlanilla > 0 ? round(($ausentes / $totalPlanilla) * 100, 1) : 0 }}%</span>
            </div>
            <div class="rda-metric rda-metric-act">
                <span class="rda-metric-value">{{ $resumen->total_actividades ?? 0 }}</span>
                <span class="rda-metric-label">Actividades</span>
            </div>
        </div>

        {{-- ══ CUERPO: GRÁFICO + TABLA ════════════════════════════════ --}}
        <div class="rda-body">

            {{-- Gráfico doughnut --}}
            <div class="rda-chart-wrap">
                <div class="rda-chart-inner">
                    <canvas id="chartAsistencias" x-ref="canvas"></canvas>
                    <div class="rda-chart-center">
                        <span class="rda-chart-center-val">{{ $totalPlanilla }}</span>
                        <span class="rda-chart-center-lbl">empleados</span>
                    </div>
                </div>
            </div>

            {{-- Tabla de desglose --}}
            <div class="rda-table-wrap">
                <table class="rda-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Tipo</th>
                            <th class="rda-th-num">Total</th>
                            <th class="rda-th-num">%</th>
                            <th class="rda-th-bar"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($totales as $item)
                            <tr class="rda-row">
                                <td>
                                    <span class="rda-badge"
                                        style="background: {{ $item['color'] }}1A; color: {{ $item['color'] }}; border-color: {{ $item['color'] }}40">
                                        {{ $item['codigo'] }}
                                    </span>
                                </td>
                                <td class="rda-desc">
                                    {{ $item['descripcion'] }}
                                    @if(!$item['acumula'])
                                        <span class="rda-tag-noac">no acumula</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="rda-tipo">{{ $item['tipo'] }}</span>
                                </td>
                                <td class="rda-num rda-num-bold">{{ $item['total'] }}</td>
                                <td class="rda-num rda-pct-cell">{{ $item['porcentaje'] }}%</td>
                                <td class="rda-bar-cell">
                                    <div class="rda-bar-track">
                                        <div class="rda-bar-fill"
                                            style="width: {{ $item['porcentaje'] }}%; background: {{ $item['color'] }}">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    @endif
    {{-- ══ ESTILOS ══════════════════════════════════════════════════════════ --}}
    <style>
        /* ── Variables ─────────────────────────────────────────── */
        .rda-wrapper {
            --rda-bg: #0f1117;
            --rda-surface: #181c26;
            --rda-border: rgba(255, 255, 255, 0.07);
            --rda-text: #e2e6f0;
            --rda-muted: #6b7280;
            --rda-accent: #4f7cff;
            --rda-ok: #34d399;
            --rda-warn: #f87171;
            --rda-radius: 12px;
            font-family: 'DM Sans', 'Geist', ui-sans-serif, system-ui, sans-serif;
            color: var(--rda-text);
        }

        /* ── Wrapper ────────────────────────────────────────────── */
        .rda-wrapper {
            background: var(--rda-surface);
            border: 1px solid var(--rda-border);
            border-radius: var(--rda-radius);
            overflow: hidden;
        }

        /* ── Header ────────────────────────────────────────────── */
        .rda-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 22px;
            border-bottom: 1px solid var(--rda-border);
            flex-wrap: wrap;
        }

        .rda-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .rda-icon {
            width: 38px;
            height: 38px;
            background: rgba(79, 124, 255, 0.12);
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--rda-accent);
            flex-shrink: 0;
        }

        .rda-title {
            font-size: 15px;
            font-weight: 600;
            margin: 0;
        }

        .rda-subtitle {
            font-size: 12px;
            color: var(--rda-muted);
            margin: 2px 0 0;
            text-transform: capitalize;
        }

        .rda-header-actions {
            display: flex;
            gap: 8px;
        }

        /* ── Botones ────────────────────────────────────────────── */
        .rda-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all .15s;
        }

        .rda-btn-ghost {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--rda-border);
            color: var(--rda-text);
        }

        .rda-btn-ghost:hover {
            background: rgba(255, 255, 255, 0.09);
        }

        .rda-btn-export {
            background: rgba(79, 124, 255, 0.1);
            border-color: rgba(79, 124, 255, 0.25);
            color: var(--rda-accent);
            opacity: .6;
            cursor: not-allowed;
        }

        .rda-spin svg {
            animation: rdaSpin .7s linear infinite;
        }

        @keyframes rdaSpin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ── Sin datos ──────────────────────────────────────────── */
        .rda-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 56px 24px;
            color: var(--rda-muted);
            text-align: center;
        }

        .rda-empty p {
            font-size: 14px;
            font-weight: 500;
            margin: 8px 0 0;
            color: var(--rda-text);
        }

        .rda-empty span {
            font-size: 13px;
        }

        /* ── Métricas rápidas ───────────────────────────────────── */
        .rda-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1px;
            background: var(--rda-border);
            border-bottom: 1px solid var(--rda-border);
        }

        .rda-metric {
            background: var(--rda-surface);
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .rda-metric-value {
            font-size: 26px;
            font-weight: 700;
            line-height: 1;
            letter-spacing: -0.5px;
        }

        .rda-metric-label {
            font-size: 11px;
            color: var(--rda-muted);
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .rda-metric-pct {
            font-size: 12px;
            font-weight: 600;
            margin-top: 2px;
        }

        .rda-metric-total .rda-metric-value {
            color: var(--rda-text);
        }

        .rda-metric-ok .rda-metric-value,
        .rda-metric-ok .rda-metric-pct {
            color: var(--rda-ok);
        }

        .rda-metric-out .rda-metric-value,
        .rda-metric-out .rda-metric-pct {
            color: var(--rda-warn);
        }

        .rda-metric-act .rda-metric-value {
            color: var(--rda-accent);
        }

        /* ── Body ───────────────────────────────────────────────── */
        .rda-body {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 0;
        }

        @media (max-width: 700px) {
            .rda-body {
                grid-template-columns: 1fr;
            }
        }

        /* ── Gráfico ────────────────────────────────────────────── */
        .rda-chart-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            border-right: 1px solid var(--rda-border);
        }

        .rda-chart-inner {
            position: relative;
            width: 180px;
            height: 180px;
        }

        .rda-chart-inner canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .rda-chart-center {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }

        .rda-chart-center-val {
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
        }

        .rda-chart-center-lbl {
            font-size: 11px;
            color: var(--rda-muted);
            margin-top: 2px;
        }

        /* ── Tabla ──────────────────────────────────────────────── */
        .rda-table-wrap {
            overflow-x: auto;
        }

        .rda-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .rda-table thead tr {
            border-bottom: 1px solid var(--rda-border);
        }

        .rda-table th {
            padding: 10px 14px;
            font-size: 11px;
            font-weight: 600;
            color: var(--rda-muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            text-align: left;
            white-space: nowrap;
        }

        .rda-th-num {
            text-align: right;
        }

        .rda-th-bar {
            width: 100px;
        }

        .rda-row {
            border-bottom: 1px solid var(--rda-border);
            transition: background .12s;
        }

        .rda-row:last-child {
            border-bottom: none;
        }

        .rda-row:hover {
            background: rgba(255, 255, 255, 0.025);
        }

        .rda-row td {
            padding: 10px 14px;
            vertical-align: middle;
        }

        .rda-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid;
            letter-spacing: .3px;
            white-space: nowrap;
        }

        .rda-desc {
            color: var(--rda-text);
        }

        .rda-tag-noac {
            display: inline-block;
            margin-left: 6px;
            font-size: 10px;
            color: var(--rda-muted);
            background: rgba(255, 255, 255, 0.06);
            border-radius: 4px;
            padding: 1px 5px;
            vertical-align: middle;
        }

        .rda-tipo {
            font-size: 11px;
            color: var(--rda-muted);
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .rda-num {
            text-align: right;
        }

        .rda-num-bold {
            font-weight: 700;
            font-size: 14px;
        }

        .rda-pct-cell {
            color: var(--rda-muted);
            font-size: 12px;
        }

        .rda-bar-cell {
            padding-right: 18px !important;
        }

        .rda-bar-track {
            height: 5px;
            border-radius: 99px;
            background: rgba(255, 255, 255, 0.07);
            overflow: hidden;
        }

        .rda-bar-fill {
            height: 100%;
            border-radius: 99px;
            transition: width .6s cubic-bezier(.4, 0, .2, 1);
            min-width: 2px;
        }
    </style>
</div>



{{-- ══ SCRIPT Alpine + Chart.js ════════════════════════════════════════ --}}
<script>
    (function () {
        if (typeof Chart === 'undefined') {
            const s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js';
            document.head.appendChild(s);
        }
    })();

    document.addEventListener('alpine:init', () => {
        Alpine.data('asistenciasChart', (totalesInit, totalInit) => ({
            totales: totalesInit,
            total: totalInit,
            chart: null,

            init() {
                this.$nextTick(() => this.buildChart());
            },

            buildChart() {
                const canvas = this.$refs.canvas;
                if (!canvas) return;
                if (!window.Chart) {
                    setTimeout(() => this.buildChart(), 300);
                    return;
                }
                if (this.chart) { this.chart.destroy(); }

                const labels = this.totales.map(t => t.descripcion);
                const data = this.totales.map(t => t.total);
                const colors = this.totales.map(t => t.color);
                const borders = this.totales.map(t => t.color);

                this.chart = new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data,
                            backgroundColor: colors.map(c => c + 'CC'),
                            borderColor: borders,
                            borderWidth: 1.5,
                            hoverOffset: 6,
                        }]
                    },
                    options: {
                        cutout: '70%',
                        responsive: false,
                        animation: { duration: 600, easing: 'easeInOutQuart' },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1e2233',
                                borderColor: 'rgba(255,255,255,0.1)',
                                borderWidth: 1,
                                titleFont: { size: 13, weight: '600' },
                                bodyFont: { size: 12 },
                                padding: 10,
                                callbacks: {
                                    label: ctx => {
                                        const pct = this.total > 0
                                            ? ((ctx.parsed / this.total) * 100).toFixed(1)
                                            : 0;
                                        return `  ${ctx.parsed} empleados (${pct}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            },

            refrescar(nuevosTotales) {
                this.totales = nuevosTotales;
                this.$nextTick(() => this.buildChart());
            }
        }));
    });
</script>