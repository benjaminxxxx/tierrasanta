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
        .subtitle { font-size: 9px; color: #7f8c8d; }

        /* Filtros activos */
        .filtros { background: #f8f9fa; border: 1px solid #e0e6ed; padding: 5px 8px; margin-bottom: 8px; font-size: 8px; color: #555; }
        .filtros span { margin-right: 12px; }
        .filtros strong { color: #2c3e50; }

        /* Tabla */
        .data-table { width: 100%; border-collapse: collapse; font-size: 8px; }
        .data-table th {
            background: #34495e; color: white;
            padding: 4px 5px; text-align: left; font-size: 7.5px;
        }
        .data-table td { padding: 3px 5px; border-bottom: 1px solid #ecf0f1; vertical-align: top; }
        .data-table tr:nth-child(even) td { background: #fafafa; }
        .data-table tr:last-child td { border-bottom: none; }

        /* Badges acción */
        .badge { padding: 1px 5px; border-radius: 3px; font-size: 7.5px; font-weight: bold; }
        .badge-crear    { background: #d5f5e3; color: #1e8449; }
        .badge-editar   { background: #fdebd0; color: #b7770d; }
        .badge-eliminar { background: #fadbd8; color: #922b21; }
        .badge-otro     { background: #eaeded; color: #555; }

        .mono { font-family: monospace; }
        .muted { color: #95a5a6; }
        .tachado { text-decoration: line-through; color: #e74c3c; }
        .nuevo { color: #27ae60; }
        .campo-label { color: #7f8c8d; }

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
                    <h1>Reporte de Auditoría</h1>
                    <div class="subtitle">Registro de acciones del sistema</div>
                </td>
                <td style="text-align:right; font-size:8px; color:#7f8c8d;">
                    <strong>Generado el</strong><br>{{ $generadoEn }}
                </td>
            </tr>
        </table>
    </div>

    <!-- FILTROS ACTIVOS -->
    <div class="filtros">
        <span><strong>Desde:</strong> {{ $filtros['desde'] ?: '—' }}</span>
        <span><strong>Hasta:</strong> {{ $filtros['hasta'] ?: '—' }}</span>
        <span><strong>Modelo:</strong> {{ $filtros['modelo'] }}</span>
        <span><strong>Acción:</strong> {{ $filtros['accion'] }}</span>
        <span><strong>Usuario:</strong> {{ $filtros['usuario'] }}</span>
        <span><strong>Total registros:</strong> {{ $registros->count() }}</span>
    </div>

    <!-- TABLA -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:65px">Fecha</th>
                <th style="width:55px">Usuario</th>
                <th style="width:45px">Acción</th>
                <th style="width:70px">Modelo</th>
                <th style="width:25px">ID</th>
                <th>Cambios</th>
                <th style="width:80px">Observación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($registros as $reg)
                @php
                    $cambios = is_array($reg->cambios)
                        ? $reg->cambios
                        : json_decode($reg->cambios, true);
                @endphp
                <tr>
                    <td class="mono">
                        {{ \Carbon\Carbon::parse($reg->fecha_accion)->format('d/m/Y') }}<br>
                        <span class="muted">{{ \Carbon\Carbon::parse($reg->fecha_accion)->format('H:i:s') }}</span>
                    </td>
                    <td>
                        {{ $reg->usuario_nombre }}<br>
                        <span class="muted">#{{ $reg->usuario_id }}</span>
                    </td>
                    <td>
                        @php
                            $badgeClass = match($reg->accion) {
                                'crear'    => 'badge-crear',
                                'editar'   => 'badge-editar',
                                'eliminar' => 'badge-eliminar',
                                default    => 'badge-otro',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ strtoupper($reg->accion) }}</span>
                    </td>
                    <td class="mono">{{ class_basename($reg->modelo) }}</td>
                    <td class="mono muted">{{ $reg->modelo_id }}</td>

                    <td>
                        @if($cambios)
                            @if($reg->accion === 'editar' && isset($cambios['antes'], $cambios['despues']))
                                @foreach($cambios['despues'] as $campo => $nuevo)
                                    <span class="campo-label">{{ $campo }}:</span>
                                    <span class="tachado">{{ Str::limit((string)($cambios['antes'][$campo] ?? '—'), 25) }}</span>
                                    → <span class="nuevo">{{ Str::limit((string)$nuevo, 25) }}</span><br>
                                @endforeach

                            @elseif($reg->accion === 'crear' && isset($cambios['creado']))
                                @foreach(array_slice($cambios['creado'], 0, 4) as $k => $v)
                                    <span class="campo-label">{{ $k }}:</span>
                                    <span class="nuevo">{{ Str::limit((string)$v, 30) }}</span><br>
                                @endforeach
                                @if(count($cambios['creado']) > 4)
                                    <span class="muted">+{{ count($cambios['creado']) - 4 }} más</span>
                                @endif

                            @elseif($reg->accion === 'eliminar' && isset($cambios['eliminado']))
                                @foreach(array_slice($cambios['eliminado'], 0, 4) as $k => $v)
                                    <span class="campo-label">{{ $k }}:</span>
                                    <span class="tachado">{{ Str::limit((string)$v, 30) }}</span><br>
                                @endforeach

                            @else
                                <span class="muted mono">{{ Str::limit(json_encode($cambios, JSON_UNESCAPED_UNICODE), 80) }}</span>
                            @endif
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>

                    <td class="muted">{{ $reg->observacion ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center; padding:20px; color:#95a5a6;">
                        Sin registros para los filtros aplicados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>Sistema de Auditoría • {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</td>
                <td style="text-align:right">{{ $registros->count() }} registros exportados</td>
            </tr>
        </table>
    </div>

</body>
</html>