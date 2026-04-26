<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 50px;
        }

        body {
            margin: 0;
        }

        .content * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            background: #ffffff;
        }

        /* ── Header ─────────────────────────────────────── */
        .header {
            margin-bottom: 18px;
        }

        .header-label {
            font-size: 8px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 2px;
        }

        .header-date {
            font-size: 11px;
            color: #6b7280;
        }

        .divider {
            border: none;
            border-top: 0.5px solid #e5e7eb;
            margin: 14px 0;
        }

        /* ── Métricas ────────────────────────────────────── */
        .metrics-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .metrics-table td {
            width: 25%;
            background: #f9fafb;
            padding: 14px 16px;
            border-right: 0.5px solid #e5e7eb;
        }

        .metrics-table td:last-child {
            border-right: none;
        }

        .metric-value {
            font-size: 24px;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 4px;
        }

        .metric-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 3px;
        }

        .metric-pct {
            font-size: 10px;
            font-weight: bold;
        }

        .color-default {
            color: #111827;
        }

        .color-green {
            color: #10b981;
        }

        .color-red {
            color: #ef4444;
        }

        .color-indigo {
            color: #6366f1;
        }

        /* ── Body: gráfico + tabla ───────────────────────── */
        .body-table {
            width: 100%;
            border-collapse: collapse;
        }

        .body-table>tbody>tr>td {
            vertical-align: top;
            padding: 0;
        }

        .col-chart {
            width: 140px;
            padding-right: 16px;
        }

        .col-table {}

        .chart-img {
            width: 120px;
            height: 120px;
        }

        /* ── Tabla desglose ──────────────────────────────── */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .detail-table thead tr {
            border-bottom: 0.5px solid #e5e7eb;
            background: #f9fafb;
        }

        .detail-table th {
            font-size: 8px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 7px 10px;
            text-align: left;
        }

        .detail-table th.right {
            text-align: right;
        }

        .detail-table tbody tr {
            border-bottom: 0.5px solid #f3f4f6;
        }

        .detail-table tbody tr:last-child {
            border-bottom: none;
        }

        .detail-table td {
            padding: 8px 10px;
            vertical-align: middle;
        }

        /* Badge código */
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            border: 0.5px solid;
        }

        .tag-noac {
            font-size: 8px;
            color: #9ca3af;
            margin-left: 5px;
        }

        .tipo-text {
            font-size: 9px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .num-right {
            text-align: right;
            font-weight: bold;
        }

        .pct-right {
            text-align: right;
            color: #6b7280;
        }

        /* Barra de progreso */
        .bar-wrap {
            background: #f3f4f6;
            border-radius: 99px;
            height: 5px;
            width: 80px;
            overflow: hidden;
        }

        .bar-fill {
            height: 5px;
            border-radius: 99px;
        }

        /* ── Footer ──────────────────────────────────────── */
        .footer {
            margin-top: 24px;
            border-top: 0.5px solid #e5e7eb;
            padding-top: 10px;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>

<body class="content">

    {{-- Header --}}
    <div class="header">
        <div class="header-label">Reporte de Asistencias</div>
        <div class="header-title">Asistencias del día</div>
        <div class="header-date">{{ $fechaFormateada }}</div>
    </div>

    <hr class="divider">

    {{-- Métricas --}}
    <table class="metrics-table">
        <tr>
            <td>
                <div class="metric-value color-default">{{ $totalPlanilla }}</div>
                <div class="metric-label">Total planilla</div>
            </td>
            <td>
                <div class="metric-value color-green">{{ $asistidos }}</div>
                <div class="metric-label">Acumulan asistencia</div>
                <div class="metric-pct color-green">{{ $pctAsist }}%</div>
            </td>
            <td>
                <div class="metric-value color-red">{{ $ausentes }}</div>
                <div class="metric-label">Sin acumular</div>
                <div class="metric-pct color-red">{{ $pctAusent }}%</div>
            </td>
            <td>
                <div class="metric-value color-indigo">{{ $actividades }}</div>
                <div class="metric-label">Actividades</div>
            </td>
        </tr>
    </table>

    {{-- Body: gráfico + tabla --}}
    <table class="body-table">
        <tr>
            {{-- Gráfico --}}
            <td class="col-chart">
                @if($chartBase64)
                    <img class="chart-img" src="{{ $chartBase64 }}">
                @endif
            </td>

            {{-- Tabla desglose --}}
            <td class="col-table">
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Tipo</th>
                            <th class="right">Total</th>
                            <th class="right">%</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($totales as $item)
                            @php
                                $hex = ltrim($item['color'], '#');
                                $r = hexdec(substr($hex, 0, 2));
                                $g = hexdec(substr($hex, 2, 2));
                                $b = hexdec(substr($hex, 4, 2));
                                // Color de texto: oscurecer el color del badge
                                $textHex = $item['color'];
                                $bgAlpha = "rgba({$r},{$g},{$b},0.12)";
                                $border = "rgba({$r},{$g},{$b},0.4)";
                            @endphp
                            <tr>
                                <td>
                                    <span class="badge"
                                        style="color:{{ $textHex }}; background:{{ $bgAlpha }}; border-color:{{ $border }}">
                                        {{ $item['codigo'] }}
                                    </span>
                                </td>
                                <td>
                                    {{ $item['descripcion'] }}
                                    @if(!$item['acumula'])
                                        <span class="tag-noac">no acumula</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="tipo-text">{{ $item['tipo'] }}</span>
                                </td>
                                <td class="num-right">{{ $item['total'] }}</td>
                                <td class="pct-right">{{ $item['porcentaje'] }}%</td>
                                <td>
                                    <div class="bar-wrap">
                                        <div class="bar-fill"
                                            style="width:{{ $item['porcentaje'] }}%; background:{{ $item['color'] }}">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        Reporte generado automáticamente &nbsp;•&nbsp; {{ now()->format('d/m/Y H:i') }}
    </div>

</body>

</html>