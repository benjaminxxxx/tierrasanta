{{-- Totales de un grupo operativo (o del total): como en su hoja, cant. unid. y costo son el SALDO --}}
<dl class="grid grid-cols-2 gap-x-4 gap-y-2 p-4 text-sm text-foreground">
    <div>
        <dt class="text-xs text-muted-foreground">Cant. unid. (saldo)</dt>
        <dd class="font-mono">{{ formatear_numero($t['saldo_unidades']) }}</dd>
    </div>
    <div>
        <dt class="text-xs text-muted-foreground">{{ $etiquetaCosto }} (saldo importe)</dt>
        <dd class="font-mono text-base font-semibold">{{ formatear_numero($t['saldo_importe']) }}</dd>
    </div>
    <div>
        <dt class="text-xs text-muted-foreground">Consumo (salidas)</dt>
        <dd class="font-mono">{{ formatear_numero($t['salidas_importe']) }}</dd>
    </div>
    <div>
        <dt class="text-xs text-muted-foreground">Entradas</dt>
        <dd class="font-mono">{{ formatear_numero($t['entradas_importe']) }}</dd>
    </div>
</dl>
