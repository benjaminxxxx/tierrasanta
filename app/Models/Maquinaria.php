<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Maquinaria extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'alias_blanco',
        'combustible_producto_id', // combustible que usa (gasolina / petróleo)
        'usa_distribucion',        // reparte su trabajo por campo; si no, su combustible va directo a FDM
        'consumo_modo',            // 'km' (km por unidad) | 'hora' (unidades por hora de encendido)
        'consumo_estimado',
        'placa',
        'foto',
    ];

    protected $casts = [
        'usa_distribucion' => 'boolean',
        'consumo_estimado' => 'float',
    ];

    public const MODOS_CONSUMO = [
        'km' => 'Por kilómetro',
        'hora' => 'Por hora de encendido',
    ];

    public function combustible()
    {
        return $this->belongsTo(Producto::class, 'combustible_producto_id');
    }

    /** Unidad del combustible (ej. GAL), según la tabla 6 del producto. */
    public function getUnidadCombustibleAttribute(): string
    {
        $tabla6 = $this->combustible?->tabla6;
        return $tabla6?->alias ?: ($tabla6?->descripcion ?: 'unidad');
    }

    /** Texto del consumo estimado, ej. "35 km por GAL" o "0.5 GAL por hora". */
    public function getConsumoTextoAttribute(): ?string
    {
        if (!$this->consumo_modo || $this->consumo_estimado === null) {
            return null;
        }
        $valor = rtrim(rtrim(number_format($this->consumo_estimado, 3, '.', ''), '0'), '.');
        return $this->consumo_modo === 'km'
            ? "{$valor} km por {$this->unidad_combustible}"
            : "{$valor} {$this->unidad_combustible} por hora";
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->foto) : null;
    }

    /**
     * Relación con el modelo DetalleMaquinariaConsumo.
     *
     * Una maquinaria puede tener múltiples consumos de detalle.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function detallesConsumo()
    {
        return $this->hasMany(DetalleMaquinariaConsumo::class);
    }

    /**
     * Relación con el modelo AlmacenProductoSalida.
     *
     * Una maquinaria puede estar asociada a múltiples salidas de productos del almacén.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function salidasAlmacen()
    {
        return $this->hasMany(AlmacenProductoSalida::class);
    }
}
