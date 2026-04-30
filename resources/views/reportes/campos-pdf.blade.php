<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; }
        @page { margin: 35px; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 9px; color: #2c3e50; margin:0; padding:0; }
        h1 { font-size: 14px; margin: 0; }

        .header { border-bottom: 2px solid #2c3e50; padding-bottom: 6px; margin-bottom: 8px; }
        .header-table { width: 100%; }
        .subtitle { font-size: 9px; color: #7f8c8d; margin-top: 2px; }

        .filtro-bar {
            background: #f8f9fa; border: 1px solid #e0e6ed;
            padding: 4px 8px; margin-bottom: 8px;
            font-size: 8px; color: #555;
        }
        .filtro-bar strong { color: #2c3e50; }

        /* Tabla */
        .data-table { width: 100%; border-collapse: collapse; font-size: 8px; }
        .data-table th {
            background: #34495e; color: white;
            padding: 4px 5px; text-align: left; font-size: 7.5px;
        }
        .data-table th.right { text-align: right; }
        .data-table th.center { text-align: center; }
        .data-table td { padding: 4px 5px; border-bottom: 1px solid #ecf0f1; vertical-align: top; }
        .data-table td.right { text-align: right; font-variant-numeric: tabular-nums; }
        .data-table td.center { text-align: center; }
        .data-table tr:nth-child(even) td { background: #fafafa; }
        .data-table tr:last-child td { border-bottom: none; }

        /* Badges estado */
        .badge { padding: 1px 5px; border-radius: 10px; font-size: 7px; font-weight: bold; }
        .badge-activo   { background: #d5f5e3; color: #1e8449; }
        .badge-alerta   { background: #fdebd0; color: #b7770d; }
        .badge-inactivo { background: #eaeded; color: #717d7e; }

        .mono { font-family: monospace; font-weight: bold; }
        .muted { color: #95a5a6; font-size: 7.5px; }
        .alerta-txt { color: #e67e22; font-size: 7px; }
        .verde { color: #27ae60; }
        .gris  { color: #95a5a6; }

        .footer { border-top: 1px solid #e0e6ed; font-size: 7.5px; color: #bdc3c7; padding-top: 4px; margin-top: 6px; }
        .footer-table { width: 100%; }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <h1>Reporte de Campos</h1>
                    <div class="subtitle">{{ $etiquetaFiltro }}{{ $filtroCampo ? ' — Campo: ' . $filtroCampo : '' }}</div>
                </td>
                <td style="text-align:right; font-size:8px; color:#7f8c8d;">
                    <strong>Generado el</strong><br>{{ $generadoEn }}
                </td>
            </tr>
        </table>
    </div>

    <!-- RESUMEN -->
    <div class="filtro-bar">
        <strong>Total campos:</strong> {{ $campos->count() }} &nbsp;|&nbsp;
        <strong>Activos:</strong> {{ $campos->where('es_activo', true)->count() }} &nbsp;|&nbsp;
        <strong>Con alerta:</strong> {{ $campos->where('tiene_alerta', true)->count() }} &nbsp;|&nbsp;
        <strong>Inactivos:</strong> {{ $campos->where('es_activo', false)->count() }}
    </div>

    <!-- TABLA -->
    <table class="data-table">
        <thead>
            <tr>
                <th>Campo</th>
                <th>Alias</th>
                <th>Padre</th>
                <th class="right">Área (ha)</th>
                <th class="center">Estado</th>
                <th>Campaña activa</th>
                <th class="right">Campañas</th>
                <th>Última siembra</th>
                <th class="right">Siembras</th>
            </tr>
        </thead>
        <tbody>
            @forelse($campos as $campo)
                <tr>
                    <td class="mono">{{ $campo->nombre }}</td>

                    <td class="muted">
                        @if($campo->alias)
                            {{ collect(explode(',', $campo->alias))->map(fn($a) => trim($a))->implode(' · ') }}
                        @else
                            —
                        @endif
                    </td>

                    <td class="mono">{{ $campo->campo_parent_nombre ?? '—' }}</td>

                    <td class="right mono">{{ number_format($campo->area, 3) }}</td>

                    <td class="center">
                        @if($campo->tiene_alerta)
                            <span class="badge badge-alerta">&#9651; Alerta</span>
                        @elseif($campo->es_activo)
                            <span class="badge badge-activo">&#9679; Activo</span>
                        @else
                            <span class="badge badge-inactivo">&#9675; Inactivo</span>
                        @endif
                    </td>

                    <td>
                        @if($campo->campana_activa)
                            <span class="verde" style="font-weight:bold">
                                {{ $campo->campana_activa->nombre_campania }}
                            </span><br>
                            <span class="muted">
                                desde {{ \Carbon\Carbon::parse($campo->campana_activa->fecha_inicio)->format('d/m/Y') }}
                            </span>
                            @if($campo->tiene_alerta)
                                <br><span class="alerta-txt">&#9651; +2 años sin cerrar</span>
                            @endif
                        @elseif($campo->ultima_campana)
                            <span class="muted">{{ $campo->ultima_campana->nombre_campania }}</span><br>
                            <span class="muted">
                                cerrada {{ \Carbon\Carbon::parse($campo->ultima_campana->fecha_fin)->format('d/m/Y') }}
                            </span>
                        @else
                            <span class="muted">Sin campaña</span>
                        @endif
                    </td>

                    <td class="right">
                        <span class="{{ $campo->total_campanas > 0 ? '' : 'muted' }}">
                            {{ $campo->total_campanas }}
                        </span>
                    </td>

                    <td>
                        @if($campo->ultima_siembra)
                            {{ \Carbon\Carbon::parse($campo->ultima_siembra->fecha_siembra)->format('d/m/Y') }}<br>
                            <span class="muted">
                                hace {{ \Carbon\Carbon::parse($campo->ultima_siembra->fecha_siembra)->diffForHumans(null, true) }}
                            </span>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>

                    <td class="right">
                        <span class="{{ $campo->total_siembras > 0 ? '' : 'muted' }}">
                            {{ $campo->total_siembras }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center; padding:20px; color:#95a5a6;">
                        Sin campos para los filtros aplicados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>Reporte de Campos • {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</td>
                <td style="text-align:right">{{ $campos->count() }} campos exportados</td>
            </tr>
        </table>
    </div>

</body>
</html>