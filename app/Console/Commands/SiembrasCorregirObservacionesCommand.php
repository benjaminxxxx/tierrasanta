<?php

namespace App\Console\Commands;

use App\Exceptions\ErrorControlado;
use App\Models\CampaniaSector;
use App\Models\CampoCampania;
use App\Models\Siembra;
use App\Services\Campania\Sector\CampaniaSectorConsulta;
use App\Services\Campania\Sector\CampaniaSectorProceso;
use App\Services\Campania\Sector\CampaniaSectorReglas;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Deja las siembras del campo 2 como en la base del Ing. Lucio (OBSERVACIONES BENJAMIN.xlsx, 24/11/2026):
 *  - la plantación del 24/08/2016 no existe (se borra);
 *  - la del 05/04/2025 estaba dos veces: queda una, y es la 1ª siembra de T.2024;
 *  - T.2024 tiene una 2ª siembra (relleno) el 18/09/2025;
 *  - las demás campañas del campo toman su plantación con la regla de Siembra::deCampania.
 * Termina siempre en el mismo estado: se puede correr de nuevo sin cambiar nada. Todo queda en auditoría.
 */
class SiembrasCorregirObservacionesCommand extends Command
{
    protected $signature = 'siembras:corregir-observaciones {--simular : muestra lo que haría y no guarda nada}';

    protected $description = 'Corrige las siembras del campo 2 según las observaciones del ingeniero (24/11/2026)';

    private const CAMPO = '2';
    private const MOTIVO = 'Observaciones del Ing. Lucio (24/11/2026)';
    private const NO_EXISTE = '2016-08-24';
    private const PRIMERA_T2024 = '2025-04-05';
    private const SEGUNDA_T2024 = '2025-09-18';

    public function handle(CampaniaSectorProceso $proceso, CampaniaSectorConsulta $consulta): int
    {
        DB::beginTransaction();
        try {
            $this->unirDuplicadas(self::PRIMERA_T2024);
            $this->borrarPlantacion(self::NO_EXISTE);

            // La campaña de esas dos siembras: la que corría el 18/09/2025 (servidor antiguo) o, en el sistema nuevo (que
            // abrió las campañas el 01/09/2026), la primera que empezó después
            $t2024 = CampoCampania::where('campo', self::CAMPO)->whereDate('fecha_inicio', '<=', self::SEGUNDA_T2024)
                ->where(fn($q) => $q->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', self::SEGUNDA_T2024))->first()
                ?? CampoCampania::where('campo', self::CAMPO)->whereDate('fecha_inicio', '>', self::SEGUNDA_T2024)->orderBy('fecha_inicio')->first();
            if (!$t2024) {
                throw new ErrorControlado('El campo 2 no tiene una campaña que corra el ' . self::SEGUNDA_T2024 . ' ni una que empiece después.');
            }
            $this->line("  Campaña de las siembras " . self::PRIMERA_T2024 . ' y ' . self::SEGUNDA_T2024 . ": {$t2024->nombre_campania} (desde "
                . $t2024->fecha_inicio->toDateString() . ')');

            // Las demás campañas del campo: su plantación con la regla nueva (conservan sus siembras y evaluaciones)
            foreach (CampoCampania::where('campo', self::CAMPO)->where('id', '<>', $t2024->id)->whereNotNull('fecha_inicio')->orderBy('fecha_inicio')->get() as $c) {
                $antes = $c->sectores()->pluck('fecha_siembra')->map(fn($f) => $f?->toDateString())->implode(', ');
                $proceso->detectar($c, true);
                $despues = $c->sectores()->pluck('fecha_siembra')->map(fn($f) => $f?->toDateString())->implode(', ');
                $this->line("  {$c->nombre_campania}: siembra {$antes} → {$despues}" . ($antes === $despues ? ' (sin cambio)' : ''));
            }

            $this->siembrasT2024($t2024, $proceso, $consulta);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        if ($this->option('simular')) {
            DB::rollBack();
            $this->comment('Simulación: no se guardó nada. Ejecuta sin --simular para aplicar.');
            return self::SUCCESS;
        }
        DB::commit();
        $this->info('Listo.');
        return self::SUCCESS;
    }

    private function unirDuplicadas(string $fecha): void
    {
        $filas = Siembra::plantaciones()->where('campo_nombre', self::CAMPO)->whereDate('fecha_siembra', $fecha)->orderBy('id')->get();
        $queda = $filas->shift();
        foreach ($filas as $duplicada) {
            CampaniaSector::where('siembra_id', $duplicada->id)->update(['siembra_id' => $queda->id]);
            AuditoriaServicio::registrar(Siembra::class, $duplicada->id, 'eliminar', $duplicada->toArray(), null,
                self::MOTIVO . ": registro duplicado de la plantación {$fecha} del campo 2, unido al {$queda->id}");
            $duplicada->delete();
            $this->line("  Plantación {$fecha}: registro duplicado {$duplicada->id} unido al {$queda->id}");
        }
    }

    private function borrarPlantacion(string $fecha): void
    {
        foreach (Siembra::where('campo_nombre', self::CAMPO)->whereDate('fecha_siembra', $fecha)->get() as $s) {
            AuditoriaServicio::registrar(Siembra::class, $s->id, 'eliminar', $s->toArray(), null,
                self::MOTIVO . ": en la base del ingeniero no hubo siembra el " . Carbon::parse($fecha)->format('d/m/Y'));
            $s->delete(); // las siembras del mapa que la usaban quedan sin enlace y se vuelven a detectar
            $this->line("  Plantación {$fecha} (registro {$s->id}) eliminada: no existe en la base del ingeniero");
        }
    }

    /** T.2024: 1ª siembra el 05/04/2025 (conserva su id y sus evaluaciones) y 2ª siembra el 18/09/2025. */
    private function siembrasT2024(CampoCampania $c, CampaniaSectorProceso $proceso, CampaniaSectorConsulta $consulta): void
    {
        $sectores = $consulta->sectores($c);
        if (!$sectores) {
            $proceso->detectar($c, true);
            $sectores = $consulta->sectores($c);
        }
        $fechas = array_column($sectores, 'fecha_siembra');
        if (min(array_filter($fechas) ?: ['9999']) === self::PRIMERA_T2024 && in_array(self::SEGUNDA_T2024, $fechas, true)) {
            $this->line("  {$c->nombre_campania}: ya tiene sus 2 siembras (" . self::PRIMERA_T2024 . ' y ' . self::SEGUNDA_T2024 . ')');
            return;
        }
        $sectores = collect($sectores)->sortBy('hilera_desde')->values()->all();
        // 1ª siembra: la que ya tenga el 05/04/2025 (si se agregó a mano en el servidor) o la primera que no sea la 2ª
        $base = collect($sectores)->search(fn($s) => $s['fecha_siembra'] === self::PRIMERA_T2024);
        if ($base === false) {
            $base = collect($sectores)->search(fn($s) => $s['fecha_siembra'] !== self::SEGUNDA_T2024);
        }
        $sectores[$base]['fecha_siembra'] = self::PRIMERA_T2024;
        // Cualquier otra siembra (ej. la de 2021) se une a la 1ª: sus etapas y sus evaluaciones pasan a ella
        $decisiones = [];
        $quedan = [];
        foreach ($sectores as $i => $s) {
            if ($i === $base || $s['fecha_siembra'] === self::SEGUNDA_T2024) {
                $quedan[$i] = $s;
                continue;
            }
            $sectores[$base]['etapas'] = array_merge($sectores[$base]['etapas'], $s['etapas']);
            foreach (app(\App\Services\Evaluacion\Siembra\EvaluacionSiembraConsulta::class)->evaluaciones($c, [(int) $s['id']]) as $e) {
                $decisiones[$e['clave']] = ['accion' => 'mover', 'destino' => 's' . $sectores[$base]['id']];
            }
            $this->line("  {$c->nombre_campania}: {$s['nombre']} ({$s['fecha_siembra']}) se une a la 1ª siembra" . ($decisiones ? ' con sus evaluaciones' : ''));
        }
        $quedan[$base] = $sectores[$base];
        $sectores = array_values(collect($quedan)->sortKeys()->all());
        // La etapa "siembra" solo si la siembra fue dentro de la campaña (en el sistema nuevo las dos son anteriores a su inicio)
        $inicio = $c->fecha_inicio->toDateString();
        $etapaSiembra = fn(string $fecha) => $fecha >= $inicio ? [['etapa' => 'siembra', 'fecha_desde' => $fecha]] : [];
        $b = collect($sectores)->search(fn($s) => $s['fecha_siembra'] === self::PRIMERA_T2024);
        $sectores[$b]['etapas'] = collect($sectores[$b]['etapas'])->unique(fn($e) => "{$e['etapa']}|{$e['fecha_desde']}")
            ->reject(fn($e) => $e['etapa'] === 'siembra')->concat($etapaSiembra(self::PRIMERA_T2024))->values()->all();
        $agregada = false;
        if (!in_array(self::SEGUNDA_T2024, array_column($sectores, 'fecha_siembra'), true)) {
            $sectores[] = ['id' => null, 'nombre' => '', 'fecha_siembra' => self::SEGUNDA_T2024, 'hilera_desde' => 1, 'hilera_hasta' => 1,
                'etapas' => $etapaSiembra(self::SEGUNDA_T2024)];
            $agregada = true;
        }
        foreach ($sectores as $k => $s) {
            $sectores[$k]['nombre'] = CampaniaSectorReglas::nombreSiembra($s['fecha_siembra'] === self::PRIMERA_T2024 ? 1 : 2);
        }
        $sectores = collect($sectores)->sortBy('fecha_siembra')->values()->all();
        $hileras = CampaniaSectorConsulta::hilerasDelCampo(self::CAMPO);
        if ($agregada || count($quedan) < count($consulta->sectores($c)) || collect($sectores)->max('hilera_hasta') < $hileras) {
            foreach (CampaniaSectorReglas::repartirHileras($hileras, count($sectores)) as $k => [$desde, $hasta]) {
                $sectores[$k]['hilera_desde'] = $desde;
                $sectores[$k]['hilera_hasta'] = $hasta;
            }
        }
        $proceso->guardar($c, $hileras, $sectores, self::MOTIVO, 'correccion', $decisiones, true);
        $this->line("  {$c->nombre_campania}: 1ª siembra " . self::PRIMERA_T2024 . ' y 2ª siembra ' . self::SEGUNDA_T2024
            . ' (hileras repartidas en partes iguales; se corrigen en el mapa de etapas)');
    }
}
