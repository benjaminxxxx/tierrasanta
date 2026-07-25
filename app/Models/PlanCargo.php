<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PlanCargo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'plan_cargos';

    protected $fillable = ['nombre', 'slug', 'cupo_maximo', 'activo', 'eliminado_por'];

    protected $casts = [
        'activo' => 'boolean',
        'cupo_maximo' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (PlanCargo $cargo) {
            $cargo->slug = static::normalizar($cargo->nombre);
        });
    }

    public static function normalizar(string $nombre): string
    {
        return (string) Str::of($nombre)->trim()->lower()->ascii()->slug('-');
    }

    public static function buscarOCrear(string $nombreExcel): self
    {
        $slug = static::normalizar($nombreExcel);

        return static::firstOrCreate(
            ['slug' => $slug],
            ['nombre' => trim($nombreExcel)]
        );
    }

    public function empleadoCargos()
    {
        return $this->hasMany(PlanEmpleadoCargo::class, 'plan_cargo_id');
    }

    public function empleadoCargosVigentes()
    {
        return $this->hasMany(PlanEmpleadoCargo::class, 'plan_cargo_id')->whereNull('fecha_fin');
    }
}