<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsSubcategoria extends Model
{
    use SoftDeletes;

    protected $table = 'ins_subcategorias';

    protected $fillable = [
        'nombre',
        'categoria_codigo',
        'descripcion',
        'creado_por',
        'editado_por',
        'eliminado_por',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    // ─── Relaciones ───────────────────────────────────────────

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(InsCategoria::class, 'categoria_codigo', 'codigo');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'subcategoria_id');
    }

    // Auditoría
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function editadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editado_por');
    }

    public function eliminadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'eliminado_por');
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeDeCategoria($query, string $categoria): mixed
    {
        return $query->where('categoria_codigo', $categoria);
    }

    public function scopeFitosanitarios($query): mixed
    {
        return $query->where('categoria_codigo', 'fitosanitario');
    }
}
