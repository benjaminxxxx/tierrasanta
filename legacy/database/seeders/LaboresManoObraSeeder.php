<?php

namespace Database\Seeders;

use App\Models\Labores;
use App\Models\ManoObra;
use App\Support\FormatoHelper;
use Illuminate\Database\Seeder;

/**
 * Asigna la mano de obra (grupo del reporte "Costos de producción") a cada labor, por NOMBRE.
 *
 * Todo se busca por nombre (labores.nombre_labor y mano_obras.codigo/descripcion), no por id: los ids pueden ser
 * distintos en producción. La comparación ignora mayúsculas, tildes, espacios dobles y puntos finales.
 *
 * - 'actividades': actividad del reporte de costos => nombre de la labor sugerida (null = no hay labor equivalente,
 *   se queda así).
 * - 'adicionales': labores que no están en el reporte pero por su nombre pertenecen al grupo (variantes tubos,
 *   mallita, "Infestación 2", "Sin bono", Poda Mama, etc.).
 *
 * Sobrescribe la mano de obra de las labores encontradas (había asignaciones erróneas). Las labores que no están
 * en el mapeo no se tocan. Uso: php artisan db:seed --class=LaboresManoObraSeeder
 */
class LaboresManoObraSeeder extends Seeder
{
    public const MAPEO = [
        // 1.1.1
        'preparacion_terreno' => [
            'descripcion' => 'Preparación de terreno',
            'actividades' => [
                'Cambio de accesorios' => null,
                'Arreglo de conectores' => 'Arreglo conectores',
                'Cambio de aspersores' => null,
                'Cambio de gomas' => null,
                'Componiendo tuberia' => null,
                'Colocación cinta de riego' => 'Colocación de cinta de riego',
                'Desmonte sistema de aspersión' => 'Desmonte sistema de aspersión',
                'Escarbo de conectores' => null,
                'Escarbo para tuberias' => null,
                'Escarbo para resiembra' => 'Escarbo para Resiembre',
                'Instalación sistema de aspersión' => 'Instalación sistema de aspersión',
                'Instalación de conectores iniciales' => null,
                'Instalación de salidas de riego' => null,
                'Jalado de cintas de riego' => null,
                'Limpieza de culata' => null,
                'Marcado para instalar aspersores' => null,
                'Mantenimiento de válvulas' => null,
                'Parchado cinta de riego' => 'Parchado cinta de riego',
                'Recojo de basura' => 'Recojo de basura',
                'Retiro de cinta de riego del campo' => 'Retiro de cinta de riego del campo',
                'Tractorista' => 'Tractorista',
                'Traslado sistema de aspersión' => 'Traslado sistema de aspersión',
            ],
            'adicionales' => [
                'Reparto de cinta', 'Lavado de cinta', 'Amarre terminales cinta de riego', 'Levantada de Cinta',
                'Acomodar Cinta', 'Instalación tubería', 'Instalación arco de riego',
                'Rastrojeo (junta y quema de rastrojo)', 'Trituración de Penca',
                'Operador Trituración de Penca (motocultor)', 'Trituración de penca (Tractorista)',
                'Aporque de pencas Triturada', 'Aporque de Penca', 'Colocación Implementos - Tractor',
                'Desalitrada', 'Limpieza de Campo',
            ],
        ],
        // 1.1.2
        'siembra' => [
            'descripcion' => 'Siembra',
            'actividades' => [
                'Descarga de pencas.' => 'Descarga de Pencas',
                'Ensacado de pencas' => 'Ensacado de pencas',
                'Plantación' => 'Plantación',
                'Reparto de pencas.' => 'Reparto de Pencas',
                'Traslado de pencas' => 'Traslado de pencas',
                'Replante' => 'Replante',
                'Tapado de pencas siembra' => 'Tapado de pencas siembra',
            ],
            'adicionales' => [
                'Desterrado de pencas-siembra', 'Siembra 2', 'Lavado de pencas',
                'Aporque Después de Siembra Tractorista', 'Nivelacion de camas Después de Siembra',
                'Parchado de Cinta de riego - siembra',
            ],
        ],
        // 1.1.3
        'sanidad' => [
            'descripcion' => 'Sanidad',
            'actividades' => [
                'Aplicación sanitaria (mochila)' => 'Aplicación sanitaria (mochila)',
                'Aplicación herbicida (mochila)' => 'Aplicación herbicida (mochila)',
                'Aplicación cebo tóxico' => 'Aplicación cebo tóxico',
                'Preparación de cebo tóxico' => 'Preparación de cebo tóxico',
                'Poda sanitaria' => 'Poda sanitaria',
            ],
            'adicionales' => [
                'Control manual queresa', 'Eliminación manual de arañas',
                'Aplicacion Sanitaria( Fumigadora Estacionaria)', 'Operador ( Estacionaria + Tractor)',
                'Operador ( Estacionaria)', 'Mantenimiento de trampas de luz',
            ],
        ],
        // 1.1.4
        'infestacion' => [
            'descripcion' => 'Infestación',
            'actividades' => [
                'Traslado infestadores cartón en infestación' => 'Traslado infestadores cartón en infestación',
                'Llenada infestadores cartones en infestación' => 'Llenada infestadores cartones en infestación',
                'Colocación infestadores cartones en infestación' => 'Colocación infestadores cartones en infestación',
                'Recojo infestadores cartón en infestación' => 'Recojo infestadores cartón en infestación',
                'Vaciado Infestadores cartón en infestación' => 'Vaciado Infestadores cartón en infestación',
            ],
            'adicionales' => [
                'Llenada infestadores tubos en infestación', 'Llenada infestadores mallita en infestación',
                'Traslado infestadores tubos en infestación', 'Traslado infestadores mallita en infestación',
                'Colocación infestadores tubos en infestación', 'Colocación infestadores mallita en infestación',
                'Traslado de mallas raschel en infestación', 'Distribución de malla raschel en infestación',
                'Colocación malla raschel en infestación', 'Destapa de malla raschel en infestación',
                'Retiro malla raschel en infestación', 'Recojo infestadores tubo en infestación',
                'Recojo infestadores mallita en infestación', 'Vaciado Infestadores tubo en infestación',
                'Vaciado Infestadores mallita en infestación', 'Traslado de cajas y/o bolsas vacías para infestadores',
                'Traslado de cochinilla madre pesada', 'Traslado cochinilla Madre pesada en Infestación',
                'Retorno a almacén de infestadores vacíos (cartón, mallita, tubo)',
                'Traslado de infestadores vacíos campo a campo.', 'Confección infestadores malla',
                'Cortado de malla para infestadores', 'Doblado de mallas para infestadores',
                'Cambio de mallitas en infestación', 'Retorno a almacén de infestadores mallita con cochinilla',
                'Reparación de infestadores', 'Acomodar Malla Rachel - Infestación',
                // Infestación 2
                'Llenada de infestadores Cartón en Infestación 2', 'llenada Infestadores Tubos en Infestación 2',
                'Llenada Infestadores Mallita en infestación 2', 'Traslado Infestadores carton en Infestacion 2',
                'Traslado Infestadores Tubos en Infestación 2', 'Traslado Infestadores mallita en Infestación 2',
                'Colocación Infestadores Cartones en Infestacion 2', 'Colocación Infestadores tubo en Infestacion 2',
                'Colocación Infestadores Mallita en infestación 2', 'Traslado de Malla Rachel en Infestación 2',
                'Distribución Malla Rachel en Infestación 2', 'Colocación Malla Rachel en Infestación 2',
                'Destape de Malla Rachel en Infestación 2', 'Retiro de Malla Rachel en Infestación 2',
                'Recojo Infestadores Cartón en Infestación 2', 'Recojo Infestadores Tubo en Infestación 2',
                'Recojo Infestadores Mallita en Infestación 2', 'Vaciado De Infestadores Cartón en Infestación 2',
                'Vaciado de Infestadores Tubo en infestación 2', 'Vaciado Infestadores Mallita en Infestación 2',
                // Sin bono
                'Llenada Infestadores mallita en Infestación Sin Bono',
                'Colocación Infestadores Mallita en Infestación Sin Bono',
                'Llenada Infestadores Mallita en Infestación 2 Sin Bono',
                'Colocacion Infestadores Mallita en Infestación 2 Sin bono',
            ],
        ],
        // 1.1.5 (el reporte incluye aquí la cosecha mamá / pre-cosecha)
        'reinfestacion' => [
            'descripcion' => 'Re-infestación',
            'actividades' => [
                'Distribucion De Malla en Re-Infestacion' => 'Distribución de malla raschel en re-Infestación',
                'Colocación infestadores en re-infestación cartones' => 'Colocación infestadores en re-infestación cartones',
                'Colocación malla re-infestación' => 'Colocación malla raschel reinfestación',
                'Cosecha mama o pre-cosecha' => 'Cosecha mama o pre-cosecha',
                'Llenada infestadores cartones en re-infestación.' => 'Llenada infestadores cartones en re-infestación.',
                'Retiro y colocación malla en cosecha mama o pre-cosecha' => 'Retiro y colocación malla raschel en cosecha mama o pre-cosecha',
            ],
            'adicionales' => [
                'Traslado de malla raschel en reinfestación', 'Llenada infestadores tubos en re-infestación.',
                'Llenada infestadores mallita en re-infestación.', 'Traslado infestadores cartón en re-infestación',
                'Traslado infestadores tubo en re-infestación', 'Traslado infestadores mallita en re-infestación',
                'Colocación infestadores en re-infestación tubos', 'Colocación infestadores en re-infestación mallita',
                'Recojo infestadores re-infestación cartón', 'Recojo infestadores re-infestación tubo',
                'Recojo infestadores re-infestación mallita', 'Destapa de malla raschel en re-infestación',
                'Retiro definitivo de malla raschel re-infestación', 'Vaciado Infestadores cartón en re-infestación',
                'Vaciado Infestadores tubo en re-infestación', 'Vaciado Infestadores mallita en re-infestación',
                'Traslado cochinilla cosecha madres o pre-cosecha', 'Cambio de mallitas en RE infestación',
                'Acomodar Malla Rachel - Reinfestación',
                'Retorno a almacén de Infestadores Vacios (cartón,tubo ) en reinfestación',
                'Llenada infestadores Mallita en Reinfestación Sin Bono',
                'Colocación Infestadores en Reinfestación Mallita sin bono',
            ],
        ],
        // 1.1.6
        'labores_culturales' => [
            'descripcion' => 'Labores culturales',
            'actividades' => [
                'Deshierbo' => 'Deshierbo',
                'Eliminación de frutos' => 'Eliminación de frutos',
                'Desbrote' => 'Desbrote',
                'Raleo de Brotes' => 'Raleo de Brotes',
                'Cosecha de pencas' => 'Cosecha de pencas',
                'Cortado de penca brazos' => null,
            ],
            'adicionales' => [
                'Aplicación de estiércol', 'Aplicación manual de estiércol', 'Tapado de estiércol manual',
                'Trituración de Estiércol', 'Aplicación de Compost', 'Entresacado de pencas malogradas',
                'ELIMINACION DE PENCAS COSECHADAS',
            ],
        ],
        // 1.1.7
        'riego_fertilizacion' => [
            'descripcion' => 'Riego y fertilización',
            'actividades' => [
                'Riego y fertilización' => 'Riego y fertilización',
                'Ayuda en riego' => null,
            ],
            'adicionales' => [
                'Riego aspersión', 'Desatoro de goteros', 'Pesado fertilizantes', 'Reparto de fertilizantes',
                'Carga y descarga de fertilizantes', 'Carga y descarga de Biol', 'Envasada de biol', 'Reparto de biol',
            ],
        ],
        // 1.1.8 (todas las variantes de poda son cosecha)
        'cosecha' => [
            'descripcion' => 'Cosecha',
            'actividades' => [
                'Poda' => 'Poda',
                'Traslado cochinilla de cosecha' => 'Traslado cochinilla de cosecha',
            ],
            'adicionales' => [
                'Poda Mama', 'Poda Barrido (Ranqueo)', 'Traslado Mama - Almacen', 'Poda Sin Bono',
                'Poda Mama Sin bono', 'Poda Barrido ( Ranqueo) Sin Bono',
            ],
        ],
        // 1.1.9
        'postcosecha' => [
            'descripcion' => 'Post-cosecha',
            'actividades' => [
                'Filtrado' => null,
                'Post-Cosecha (venteado,extendido,filtrado)' => 'Post-cosecha (Venteado, extendido y filtrado)',
            ],
            'adicionales' => [
                'Mantenimiento de secadero de cochinilla', 'DESCARGA DE COCHINILLA (ACUMULADO)',
            ],
        ],
        // Fuera del reporte, pero el grupo existe y el nombre es inequívoco
        'naranja' => [
            'descripcion' => 'Naranja',
            'actividades' => [],
            'adicionales' => ['Poda-Naranja', 'Cosecha - Naranja', 'Eliminación fruta malograda', 'Aplicaciones sanitarias naranja'],
        ],
        'labores_mantenimiento' => [
            'descripcion' => 'Labores de mantenimiento',
            'actividades' => [],
            'adicionales' => [
                'Limpieza acequias', 'Limpieza desarenador', 'Parchada geomembrana', 'Limpieza estanque (Jornales)',
                'Mantenimiento de estanque', 'Arreglo almacenes', 'Mantenimiento de maquinarias', 'Cerco perimétrico',
                'Jardinería',
            ],
        ],
    ];

    public function run(): void
    {
        $manoObras = ManoObra::all();
        // groupBy (no keyBy): puede haber labores duplicadas con el mismo nombre normalizado y se asignan todas
        $labores = Labores::all()->groupBy(fn($l) => self::normalizar($l->nombre_labor));

        $asignadas = 0;
        $sinCambio = 0;
        $noEncontradas = [];
        $sinLaborSugerida = [];

        foreach (self::MAPEO as $codigo => $grupo) {
            $manoObra = $manoObras->first(fn($m) => $m->codigo === $codigo)
                ?? $manoObras->first(fn($m) => self::normalizar($m->descripcion) === self::normalizar($grupo['descripcion']));
            if (!$manoObra) {
                $this->command?->warn("Mano de obra no encontrada: {$codigo} / {$grupo['descripcion']} (se omite el grupo)");
                continue;
            }

            foreach ($grupo['actividades'] as $actividad => $laborSugerida) {
                if ($laborSugerida === null) {
                    $sinLaborSugerida[] = "{$grupo['descripcion']}: {$actividad}";
                }
            }

            $nombres = array_merge(array_filter(array_values($grupo['actividades'])), $grupo['adicionales']);
            foreach (array_unique($nombres) as $nombre) {
                $coincidencias = $labores->get(self::normalizar($nombre));
                if (!$coincidencias) {
                    $noEncontradas[] = "{$grupo['descripcion']}: {$nombre}";
                    continue;
                }
                foreach ($coincidencias as $labor) {
                    if ($labor->codigo_mano_obra === $manoObra->codigo) {
                        $sinCambio++;
                        continue;
                    }
                    $labor->update(['codigo_mano_obra' => $manoObra->codigo]);
                    $asignadas++;
                }
            }
        }

        $restantes = Labores::where(fn($q) => $q->whereNull('codigo_mano_obra')->orWhere('codigo_mano_obra', ''))
            ->orderBy('codigo')->get(['codigo', 'nombre_labor']);

        $this->command?->info("Labores asignadas o corregidas: {$asignadas} · ya estaban bien: {$sinCambio}");
        $this->listar('Labores del mapeo que no existen en esta base (revisar el nombre)', $noEncontradas);
        $this->listar('Actividades del reporte sin labor equivalente (se quedan así)', $sinLaborSugerida);
        $this->listar('Labores que siguen sin mano de obra', $restantes->map(fn($l) => "{$l->codigo} · {$l->nombre_labor}")->all());
    }

    /** Misma comparación de nombres que usa el reporte de costos (FormatoHelper::normalizarNombre). */
    public static function normalizar(?string $texto): string
    {
        return FormatoHelper::normalizarNombre($texto);
    }

    private function listar(string $titulo, array $lineas): void
    {
        if (!$lineas || !$this->command) {
            return;
        }
        $this->command->line('');
        $this->command->comment($titulo . ' (' . count($lineas) . '):');
        foreach ($lineas as $linea) {
            $this->command->line("  - {$linea}");
        }
    }
}
