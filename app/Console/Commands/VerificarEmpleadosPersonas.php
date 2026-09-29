<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Compara los datos personales duplicados en plan_empleados contra su persona.
 * Mientras ambas copias existan deben coincidir; cuando este comando reporte 0 diferencias
 * de forma sostenida, las columnas de plan_empleados se pueden eliminar.
 */
class VerificarEmpleadosPersonas extends Command
{
    protected $signature = 'planilla:verificar-personas {--detalle : Lista cada diferencia}';

    protected $description = 'Reporta empleados sin persona y diferencias entre plan_empleados y personas';

    private const CAMPOS = [
        'nombres' => 'nombres',
        'apellido_paterno' => 'apellido_paterno',
        'apellido_materno' => 'apellido_materno',
        'documento' => 'numero_documento',
        'email' => 'email',
        'numero' => 'telefono_movil',
        'fecha_nacimiento' => 'fecha_nacimiento',
        'direccion' => 'direccion',
    ];

    public function handle(): int
    {
        $filas = DB::table('plan_empleados as e')
            ->leftJoin('personas as p', 'p.id', '=', 'e.persona_id')
            ->select('e.*', 'p.id as p_id', 'p.nombres as p_nombres', 'p.apellido_paterno as p_apellido_paterno',
                'p.apellido_materno as p_apellido_materno', 'p.numero_documento as p_numero_documento', 'p.email as p_email',
                'p.telefono_movil as p_telefono_movil', 'p.fecha_nacimiento as p_fecha_nacimiento', 'p.direccion as p_direccion',
                'p.genero as p_genero')
            ->get();

        $normalizar = fn($v) => mb_strtoupper(trim((string) $v));
        $sinPersona = [];
        $diferencias = [];

        foreach ($filas as $f) {
            if (!$f->p_id) {
                $sinPersona[] = "#{$f->id} {$f->documento}";
                continue;
            }
            foreach (self::CAMPOS as $campoEmpleado => $campoPersona) {
                $a = $f->{$campoEmpleado};
                $b = $f->{'p_' . $campoPersona};
                if ($normalizar($a) !== $normalizar($b)) {
                    $diferencias[$campoEmpleado][] = "#{$f->id}: '{$a}' vs '{$b}'";
                }
            }
            $genero = ['M' => 'MASCULINO', 'F' => 'FEMENINO'][$f->genero] ?? '';
            if ($genero !== $normalizar($f->p_genero)) {
                $diferencias['genero'][] = "#{$f->id}: '{$f->genero}' vs '{$f->p_genero}'";
            }
        }

        $this->info("Empleados: {$filas->count()} | sin persona: " . count($sinPersona));
        foreach ($sinPersona as $s) {
            $this->line("  sin persona: {$s}");
        }

        if (!$diferencias) {
            $this->info('Sin diferencias entre plan_empleados y personas.');
            return self::SUCCESS;
        }

        foreach ($diferencias as $campo => $lista) {
            $this->warn("{$campo}: " . count($lista) . ' diferencia(s)');
            if ($this->option('detalle')) {
                foreach ($lista as $d) {
                    $this->line("  {$d}");
                }
            }
        }

        return self::FAILURE;
    }
}
