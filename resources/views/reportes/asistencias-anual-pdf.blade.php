<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; }
        @page { margin: 40px; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #2c3e50;
            margin: 0; padding: 0;
        }
        h1 { font-size: 15px; margin: 0; }
        /* HEADER */
        .header {
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .header-table { width: 100%; }
        .subtitle { font-size: 10px; color: #7f8c8d; }
        /* KPI */
        .kpi-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .kpi-cell {
            width: 25%;
            border: 1px solid #e0e6ed;
            background: #f8f9fa;
            text-align: center;
            padding: 6px 4px;
            border-left: 3px solid #2c3e50;
        }
        .kpi-cell.green { border-left-color: #27ae60; }
        .kpi-cell.red   { border-left-color: #e74c3c; }
        .kpi-cell.blue  { border-left-color: #3498db; }
        .kpi-label { font-size: 8px; font-weight: bold; color: #7f8c8d; text-transform: uppercase; }
        .kpi-value { font-size: 16px; font-weight: bold; }
        .kpi-pct   { font-size: 9px; color: #7f8c8d; }
        /* LAYOUT */
        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .col-chart {
            width: 22%;
            background: #f8f9fa;
            border: 1px solid #e0e6ed;
            text-align: center;
            padding: 6px;
            vertical-align: middle;
        }
        .col-codes {
            width: 78%;
            background: #f8f9fa;
            border: 1px solid #e0e6ed;
            padding: 6px;
            vertical-align: top;
        }
        .col-chart img { width: 110px; height: 110px; }
        /* TABLA GENÉRICA */
        .data-table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .data-table th {
            background: #34495e; color: white;
            padding: 4px 6px; font-size: 8px; text-align: left;
        }
        .data-table th.right { text-align: right; }
        .data-table td { padding: 4px 6px; border-bottom: 1px solid #ecf0f1; }
        .data-table td.right { text-align: right; font-variant-numeric: tabular-nums; }
        .badge {
            background: #34495e; color: white;
            padding: 1px 5px; font-size: 8px; font-weight: bold;
        }
        .progress-bg  { width: 40px; height: 4px; background: #ecf0f1; display: inline-block; }
        .progress-fill { height: 4px; background: #27ae60; display: block; }
        .no-data { color: #bdc3c7; font-style: italic; font-size: 8px; }
        /* colores % mes */
        .pct-green { color: #27ae60; font-weight: bold; }
        .pct-amber { color: #e67e22; font-weight: bold; }
        .pct-red   { color: #e74c3c; font-weight: bold; }
        /* SECCIÓN */
        .section-title {
            font-size: 9px; font-weight: bold;
            text-transform: uppercase; color: #7f8c8d;
            margin-bottom: 4px;
        }
        /* FOOTER */
        .footer {
            border-top: 1px solid #e0e6ed;
            font-size: 8px; color: #bdc3c7;
            padding-top: 4px; margin-top: 6px;
        }
        .footer-table { width: 100%; }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <h1>Reporte Anual de Asistencias &mdash; {{ $anio }}</h1>
                    <div class="subtitle">Consolidado de todos los meses del año</div>
                </td>
                <td style="text-align:right; font-size:9px; color:#7f8c8d;">
                    <strong>Generado el</strong><br>
                    {{ \Carbon\Carbon::now()->isoFormat('D [de] MMMM [de] YYYY, HH:mm') }}
                </td>
            </tr>
        </table>
    </div>

    <!-- KPI GLOBALES -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-cell">
                <div class="kpi-label">Base anual</div>
                <div class="kpi-value">{{ number_format($totalBaseAnio) }}</div>
                <div class="kpi-pct">{{ $diasHabilesAnio }} días hábiles</div>
            </td>
            <td class="kpi-cell green">
                <div class="kpi-label">Total asistencias</div>
                <div class="kpi-value">{{ number_format($asistidosAnio) }}</div>
                <div class="kpi-pct">{{ $pctAsistAnio }}%</div>
            </td>
            <td class="kpi-cell red">
                <div class="kpi-label">Sin acumular</div>
                <div class="kpi-value">{{ number_format($ausentesAnio) }}</div>
                <div class="kpi-pct">
                    {{ $totalBaseAnio > 0 ? round(($ausentesAnio / $totalBaseAnio) * 100, 1) : 0 }}%
                </div>
            </td>
            <td class="kpi-cell blue">
                <div class="kpi-label">Meses con datos</div>
                <div class="kpi-value">
                    {{ collect($meses)->where('sinDatos', false)->count() }}
                </div>
                <div class="kpi-pct">de 12 meses</div>
            </td>
        </tr>
    </table>

    <!-- GRÁFICO + TABLA DE CÓDIGOS -->
    <table class="main-table">
        <tr>
            <td class="col-chart">
                @if($chartBase64)
                    <img src="{{ $chartBase64 }}">
                @endif
                <div style="font-size:8px; margin-top:4px; font-weight:bold;">Distribución anual</div>
            </td>
            <td class="col-codes">
                <div class="section-title">Resumen por código de asistencia (año completo)</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Tipo</th>
                            <th class="right">Total año</th>
                            <th class="right">%</th>
                            <th style="text-align:center">Gráfico</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($totalesAnio as $item)
                        <tr>
                            <td><span class="badge">{{ $item['codigo'] }}</span></td>
                            <td>{{ $item['descripcion'] }}</td>
                            <td>{{ $item['tipo'] }}</td>
                            <td class="right">{{ number_format($item['total']) }}</td>
                            <td class="right">{{ $item['porcentaje'] }}%</td>
                            <td style="text-align:center">
                                <span class="progress-bg">
                                    <span class="progress-fill"
                                          style="width:{{ min($item['porcentaje'], 100) }}%; display:block;"></span>
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- DETALLE POR MES -->
    <div class="section-title">Detalle mensual</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Mes</th>
                <th class="right">Días háb.</th>
                <th class="right">Empleados</th>
                <th class="right">Base</th>
                <th class="right">Asistencias</th>
                <th class="right">Sin acum.</th>
                <th class="right">% Asist.</th>
                <th style="text-align:center">Gráfico</th>
            </tr>
        </thead>
        <tbody>
            @foreach($meses as $m)
            <tr>
                <td>{{ $m['nombre'] }}</td>
                @if($m['sinDatos'])
                    <td colspan="7" class="no-data">Sin datos</td>
                @else
                    <td class="right">{{ $m['diasHabiles'] }}</td>
                    <td class="right">{{ $m['empleados'] }}</td>
                    <td class="right">{{ number_format($m['totalBase']) }}</td>
                    <td class="right">{{ number_format($m['asistidos']) }}</td>
                    <td class="right">{{ number_format($m['ausentes']) }}</td>
                    <td class="right @if($m['pctAsist'] >= 85) pct-green @elseif($m['pctAsist'] >= 70) pct-amber @else pct-red @endif">
                        {{ $m['pctAsist'] }}%
                    </td>
                    <td style="text-align:center">
                        <span class="progress-bg">
                            <span class="progress-fill"
                                  style="width:{{ min($m['pctAsist'], 100) }}%; display:block;
                                         background:{{ $m['pctAsist'] >= 85 ? '#27ae60' : ($m['pctAsist'] >= 70 ? '#e67e22' : '#e74c3c') }}">
                            </span>
                        </span>
                    </td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-left">Generado automáticamente desde resúmenes diarios • {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</td>
                <td style="text-align:right">Página 1</td>
            </tr>
        </table>
    </div>

</body>
</html>