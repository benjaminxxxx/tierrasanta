<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Asistencias</title>

    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 50px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #2c3e50;
            margin: 0;
            padding: 0;
        }

        h1 {
            font-size: 16px;
            margin: 0;
            color: #1a1a2e;
        }

        h2 {
            font-size: 10px;
            margin: 0 0 4px 0;
            text-transform: uppercase;
        }

        /* HEADER */
        .header {
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }

        .header-table {
            width: 100%;
        }

        .header-left {
            width: 65%;
        }

        .header-right {
            width: 35%;
            text-align: right;
            font-size: 10px;
        }

        .subtitle {
            font-size: 10px;
            color: #7f8c8d;
        }

        /* KPI */
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .kpi-cell {
            width: 20%;
            border: 1px solid #e0e6ed;
            background: #f8f9fa;
            text-align: center;
            padding: 6px 4px;
            border-left: 3px solid #2c3e50;
        }

        .green {
            border-left-color: #27ae60;
        }

        .red {
            border-left-color: #e74c3c;
        }

        .blue {
            border-left-color: #3498db;
        }

        .kpi-label {
            font-size: 8px;
            font-weight: bold;
            color: #7f8c8d;
        }

        .kpi-value {
            font-size: 18px;
            font-weight: bold;
        }

        /* LAYOUT PRINCIPAL CON TABLA */
        .main-table {
            width: 100%;
            border-collapse: collapse;
        }

        .col-chart {
            width: 25%;
            background: #f8f9fa;
            border: 1px solid #e0e6ed;
            text-align: center;
            padding: 6px;
            vertical-align: top;
        }

        .col-table {
            width: 75%;
            background: #f8f9fa;
            border: 1px solid #e0e6ed;
            padding: 6px;
            vertical-align: top;
        }

        .chart img {
            width: 100px;
            height: 100px;
        }

        .chart-title {
            font-size: 8px;
            margin-top: 4px;
            font-weight: bold;
        }

        /* DATA TABLE */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .data-table th {
            background: #34495e;
            color: white;
            padding: 4px;
            font-size: 8px;
            text-align: left;
        }

        .data-table td {
            padding: 4px;
            border-bottom: 1px solid #ecf0f1;
        }

        .text-right {
            text-align: right;
        }

        .badge {
            background: #34495e;
            color: white;
            padding: 2px 5px;
            font-size: 8px;
            font-weight: bold;
        }

        .progress-bg {
            width: 40px;
            height: 4px;
            background: #ecf0f1;
            display: inline-block;
        }

        .progress-fill {
            height: 4px;
            background: #34495e;
        }

        /* FOOTER */
        .footer {
            margin-top: 6px;
            border-top: 1px solid #e0e6ed;
            font-size: 8px;
            padding-top: 4px;
        }

        .footer-table {
            width: 100%;
        }

        .footer-left {
            text-align: left;
        }

        .footer-right {
            text-align: right;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <h1>Asistencias del mes</h1>
                    <div class="subtitle">{{ $periodoFormateado }}</div>
                </td>
                <td class="header-right">
                    <strong>Reporte de asistencias</strong><br>
                    {{ \Carbon\Carbon::now()->isoFormat('D [de] MMMM [de] YYYY') }}
                </td>
            </tr>
        </table>
    </div>

    <!-- KPI -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-cell">
                <div class="kpi-label">Total Planilla</div>
                <div class="kpi-value">{{ $totalPlanilla }}</div>
            </td>
            <td class="kpi-cell green">
                <div class="kpi-label">Asistencia</div>
                <div class="kpi-value">{{ $asistidos }}</div>
            </td>
            <td class="kpi-cell red">
                <div class="kpi-label">Faltas</div>
                <div class="kpi-value">{{ $ausentes }}</div>
            </td>
            <td class="kpi-cell blue">
                <div class="kpi-label">Permisos</div>
                <div class="kpi-value">{{ $permisos ?? '-' }}</div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-label">Actividades</div>
                <div class="kpi-value">{{ $actividades ?? '-' }}</div>
            </td>
        </tr>
    </table>

    <!-- CONTENIDO PRINCIPAL -->
    <table class="main-table">
        <tr>

            <!-- GRÁFICO -->
            <td class="col-chart">
                <div style="padding:20px">
                    @if($chartBase64)
                        <img src="{{ $chartBase64 }}">
                    @else
                        <div style="height:100px; line-height:100px;">[Gráfico]</div>
                    @endif
                    <div class="chart-title">Distribución Mensual</div>
                </div>
            </td>

            <!-- TABLA -->
            <td class="col-table">
                <h2>Resumen por Código de Asistencia</h2>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Tipo</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">%</th>
                            <th style="text-align:center;">Gráfico</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($totales as $item)
                            <tr>
                                <td><span class="badge">{{ $item['codigo'] }}</span></td>
                                <td>{{ $item['descripcion'] }}</td>
                                <td>{{ $item['tipo'] }}</td>
                                <td class="text-right">{{ $item['total'] }}</td>
                                <td class="text-right">{{ $item['porcentaje'] }}%</td>
                                <td style="text-align:center;">
                                    <span class="progress-bg">
                                        <span class="progress-fill"
                                            style="width: {{ $item['porcentaje'] }}%; display:block;"></span>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>

        </tr>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-left">
                    Generado automáticamente • {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
                </td>
                <td class="footer-right">
                    Página 1
                </td>
            </tr>
        </table>
    </div>

</body>

</html>