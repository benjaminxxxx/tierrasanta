<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; }
        @page { margin: 40px; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #2c3e50; margin:0; padding:0; }
        h1 { font-size: 15px; margin: 0; }
        .header { border-bottom: 2px solid #2c3e50; padding-bottom: 6px; margin-bottom: 8px; }
        .header-table { width: 100%; }
        .subtitle { font-size: 10px; color: #7f8c8d; }

        .kpi-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .kpi-cell {
            width: 25%; border: 1px solid #e0e6ed; background: #f8f9fa;
            text-align: center; padding: 6px 4px; border-left: 3px solid #2c3e50;
        }
        .kpi-cell.sky    { border-left-color: #0ea5e9; }
        .kpi-cell.amber  { border-left-color: #f59e0b; }
        .kpi-cell.indigo { border-left-color: #6366f1; }
        .kpi-label { font-size: 8px; font-weight: bold; color: #7f8c8d; text-transform: uppercase; }
        .kpi-value { font-size: 16px; font-weight: bold; }

        .data-table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .data-table th {
            background: #34495e; color: white;
            padding: 4px 6px; font-size: 8px; text-align: left;
        }
        .data-table th.right { text-align: right; }
        .data-table td { padding: 4px 6px; border-bottom: 1px solid #ecf0f1; vertical-align: middle; }
        .data-table td.right { text-align: right; font-variant-numeric: tabular-nums; }
        .data-table tr:last-child td { border-bottom: none; }

        .badge { background: #34495e; color: white; padding: 1px 5px; font-size: 8px; font-weight: bold; }
        .campo { font-family: monospace; font-weight: bold; font-size: 10px; }

        .progress-bg   { width: 50px; height: 4px; background: #ecf0f1; display: inline-block; }
        .progress-fill { height: 4px; background: #6366f1; display: block; }

        .sky   { color: #0ea5e9; }
        .amber { color: #f59e0b; }
        .muted { color: #94a3b8; }

        .footer { border-top: 1px solid #e0e6ed; font-size: 8px; color: #bdc3c7; padding-top: 4px; margin-top: 8px; }
        .footer-table { width: 100%; }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <h1>Reporte Diario de Actividades</h1>
                    <div class="subtitle">{{ $fechaFormateada }}</div>
                </td>
                <td style="text-align:right; font-size:9px; color:#7f8c8d;">
                    <strong>Generado el</strong><br>
                    {{ \Carbon\Carbon::now()->isoFormat('D [de] MMMM [de] YYYY, HH:mm') }}
                </td>
            </tr>
        </table>
    </div>

    <!-- KPI -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-cell">
                <div class="kpi-label">Actividades</div>
                <div class="kpi-value">{{ $totalActividades }}</div>
            </td>
            <td class="kpi-cell sky">
                <div class="kpi-label">Planilla</div>
                <div class="kpi-value">{{ $totalPlanilla }}</div>
            </td>
            <td class="kpi-cell amber">
                <div class="kpi-label">Cuadrilla</div>
                <div class="kpi-value">{{ $totalCuadrilla }}</div>
            </td>
            <td class="kpi-cell indigo">
                <div class="kpi-label">Métodos bonif.</div>
                <div class="kpi-value">{{ $totalMetodos }}</div>
            </td>
        </tr>
    </table>

    <!-- TABLA -->
    @php
        $maxPersonas = collect($actividades)->max('total_personas') ?: 1;
    @endphp
    <table class="data-table">
        <thead>
            <tr>
                <th>Campo</th>
                <th>Código</th>
                <th>Labor</th>
                <th class="right">Unid.</th>
                <th class="right">Métodos</th>
                <th class="right">Planilla</th>
                <th class="right">Cuadrilla</th>
                <th class="right">Total</th>
                <th style="text-align:center">Distr.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($actividades as $item)
            <tr>
                <td><span class="campo">{{ $item['campo'] }}</span></td>
                <td><span class="badge">{{ $item['codigo_labor'] }}</span></td>
                <td>{{ $item['nombre_labor'] }}</td>
                <td class="right">{{ $item['unidades'] ?? '—' }}</td>
                <td class="right">{{ $item['total_metodos'] > 0 ? $item['total_metodos'] : '—' }}</td>
                <td class="right sky">
                    {{ $item['total_planilla'] }}
                    <span class="muted">({{ $item['pct_planilla'] }}%)</span>
                </td>
                <td class="right amber">
                    {{ $item['total_cuadrilla'] }}
                    <span class="muted">({{ $item['pct_cuadrilla'] }}%)</span>
                </td>
                <td class="right" style="font-weight:bold">{{ $item['total_personas'] }}</td>
                <td style="text-align:center">
                    <span class="progress-bg">
                        <span class="progress-fill"
                              style="width:{{ round(($item['total_personas'] / $maxPersonas) * 100) }}%; display:block;">
                        </span>
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>Generado automáticamente • {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</td>
                <td style="text-align:right">Página 1</td>
            </tr>
        </table>
    </div>

</body>
</html>