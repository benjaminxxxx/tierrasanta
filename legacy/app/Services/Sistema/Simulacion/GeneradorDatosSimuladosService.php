<?php

namespace App\Services\Sistema\Simulacion;

use App\Models\Campo;
use App\Models\CampoCampania;
use App\Models\Labores;
use App\Models\Persona;
use App\Models\PlanContrato;
use App\Models\PlanEmpleado;
use App\Models\PlanResumenDiario;
use App\Models\PlanSueldo;
use App\Services\Planilla\Modulos\GestionPlanillaReporteDiario;
use App\Services\Campania\CrudCampaniaServicio;
use App\Services\Planilla\Empleado\ActividadServicio;
use App\Services\Planilla\Empleado\ContratoServicio;
use App\Services\Planilla\PlanillaEmpleadoServicio;
use App\Services\Planilla\PlanillaMensualDetalleServicio;
use App\Services\Planilla\RegistroDiario\PlanillaRegistroDiarioServicio;
use Faker\Factory as Faker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\Planilla\PlanillaMensualServicio;

class GeneradorDatosSimuladosService
{
	protected PlanillaEmpleadoServicio $planillaEmpleadoServicio;
	protected ContratoServicio $contratoServicio;
	protected PlanillaMensualServicio $planillaMensualServicio;
	protected GestionPlanillaReporteDiario $gestionPlanillaReporteDiario;

	public function __construct(
		PlanillaEmpleadoServicio $planillaEmpleadoServicio,
		ContratoServicio $contratoServicio,
		PlanillaMensualServicio $planillaMensualServicio,
		GestionPlanillaReporteDiario $gestionPlanillaReporteDiario
	) {
		$this->planillaEmpleadoServicio = $planillaEmpleadoServicio;
		$this->contratoServicio = $contratoServicio;
		$this->planillaMensualServicio = $planillaMensualServicio;
		$this->gestionPlanillaReporteDiario = $gestionPlanillaReporteDiario;
	}

	/**
	 * Elimina todos los empleados/personas existentes y genera
	 * trabajadores con Contrato y Sueldo vigentes desde el 01/01/2026.
	 */
	public function crearTrabajadoresContratadosEnPlanilla(int $cantidad = 100): void
	{
		$faker = Faker::create('es_PE');

		// 1. Limpiamos datos fuera de la transacción principal
		$this->limpiarDatosPrevios();

		// Lista de sueldos aleatorios
		$sueldosPosibles = [2000, 2200, 2400, 2600, 2750];
		// Lista de Sistemas de Pensiones válidos
		$sistemasPensiones = [
			'HAB F',
			'HAB M',
			'INT F',
			'INT M',
			'PRI F',
			'PRI M',
			'PRO F',
			'PRO M',
			'SNP',
		];

		// 2. Generación de empleados
		for ($i = 0; $i < $cantidad; $i++) {
			DB::transaction(function () use ($faker, $sueldosPosibles, $sistemasPensiones) {
				$genero = $faker->randomElement(['M', 'F']);
				$genderFaker = $genero === 'M' ? 'male' : 'female';

				// A) Datos del empleado
				$datosEmpleado = [
					'nombres' => mb_strtoupper($faker->firstName($genderFaker)),
					'apellido_paterno' => mb_strtoupper($faker->lastName()),
					'apellido_materno' => mb_strtoupper($faker->lastName()),
					'documento' => $faker->unique()->numerify('########'),
					'email' => $faker->unique()->safeEmail(),
					'direccion' => mb_strtoupper($faker->streetAddress()),
					'genero' => $genero,
					'fecha_nacimiento' => $faker->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
					'fecha_ingreso' => '2026-01-01',
				];

				// Guardar Persona / Empleado mediante el Servicio
				$empleado = $this->planillaEmpleadoServicio->guardar($datosEmpleado, null);
				$planEmpleadoId = is_array($empleado) ? $empleado['id'] : $empleado->id;

				// B) Datos del Contrato (Cumpliendo estrictamente las reglas requeridas)
				$datosContrato = [
					'plan_empleado_id' => $planEmpleadoId,
					'fecha_inicio' => '2026-01-01',
					'tipo_planilla' => 'AGRARIA',
					'tipo_contrato' => $faker->randomElement(['plazo fijo', 'indefinido', 'temporal']),
					'modalidad_pago' => 'MENSUAL',
					'plan_sp_codigo' => $faker->randomElement($sistemasPensiones), // <-- AQUÍ SE CORRIGE
					'grupo_codigo' => null,
				];

				// Guardar el Contrato usando ContratoServicio
				$this->contratoServicio->guardarContrato($datosContrato);

				// C) Crear el Sueldo (Día 1 de enero de 2026)
				$sueldoAleatorio = $faker->randomElement($sueldosPosibles);

				PlanSueldo::create([
					'plan_empleado_id' => $planEmpleadoId,
					'fecha_inicio' => '2026-01-01',
					'fecha_fin' => null,
					'sueldo' => $sueldoAleatorio,
					'creado_por' => auth()->id() ?? 1,
				]);
				
			});
		}
	}

	/**
	 * Crea 40 campañas asociadas a los primeros 40 campos disponibles, 
	 * iniciando una campaña por día a partir del 01/01/2026.
	 */
	public function crearCampaniasIniciales(): void
	{
		$campos = Campo::limit(40)->pluck('nombre')->toArray();
		$fechaBase = Carbon::create(2026, 1, 1);

		foreach ($campos as $index => $nombreCampo) {
			$fechaInicio = $fechaBase->copy()->addDays($index)->format('Y-m-d');
			$nombreCampania = 'CAMPAÑA ' . ($index + 1) . ' - ' . $nombreCampo;

			$existe = CampoCampania::where('campo', $nombreCampo)
				->where('nombre_campania', $nombreCampania)
				->exists();

			if (!$existe) {
				app(CrudCampaniaServicio::class)->guardar([
					'campo' => $nombreCampo,
					'infestacion_fecha' => null,
					'infestacion_fecha_recojo_vaciado_infestadores' => null,
					'infestacion_fecha_colocacion_malla' => null,
					'infestacion_fecha_retiro_malla' => null,
					'nombre_campania' => mb_strtoupper($nombreCampania),
					'area' => rand(5, 25) + (rand(0, 99) / 100),
					'fecha_inicio' => $fechaInicio,
				]);
			}
		}
	}

	/**
	 * Paso 2: Asigna orden de planilla mensual y genera la labor diaria (asistencia, 
	 * tramos, horas y labores) para cada día del mes y año especificados.
	 *
	 * @param int $anio
	 * @param int $mes
	 * @return void
	 */
	/**
	 * @param array $escenarios Casos de mano de obra indirecta (todos opcionales):
	 *   - feriados: string[] fechas Y-m-d con FR (8 h si asistió el día hábil anterior, 0 h si faltó: criterio manual)
	 *   - prob_dm: float probabilidad diaria de descanso médico (4 u 8 h, se escriben a mano)
	 *   - prob_am: float probabilidad diaria de atención médica (4 h)
	 *   - vacaciones: int cantidad de trabajadores con 5 días hábiles seguidos de vacaciones (V, 0 h)
	 */
	public function asignarLaboresAleatoriasEnMes(int $anio, int $mes, bool $completo = false, array $escenarios = []): void
	{
		$feriados = $escenarios['feriados'] ?? [];
		$probDm = (float) ($escenarios['prob_dm'] ?? 0);
		$probAm = (float) ($escenarios['prob_am'] ?? 0);
		$ultimaAsistencia = []; // plan_men_detalle_id => asistencia del último día hábil

		// 0. Asegurar la existencia de las campañas previas
		$this->crearCampaniasIniciales();

		// 1. Obtener los empleados contratados disponibles para el mes
		$empleados = $this->gestionPlanillaReporteDiario
			->obtenerPlanillaAgraria($mes, $anio)
			->toArray();

		if (count($empleados) === 0) {
			return;
		}

		// 2. Calcular la fecha del mes anterior
		$fechaAnterior = Carbon::createFromDate($anio, $mes, 1)->subMonth();

		// 3. Obtener el mapa de órdenes del mes anterior [empleado_id => orden]
		$ordenesAnteriores = PlanillaMensualDetalleServicio::obtenerOrden(
			$fechaAnterior->month,
			$fechaAnterior->year
		);

		// 4. Filtrar solo los empleados que existen y mantenían orden previo
		$ordenesValidos = [];
		foreach ($empleados as $emp) {
			$id = $emp['id'];
			if (isset($ordenesAnteriores[$id])) {
				$ordenesValidos[$id] = $ordenesAnteriores[$id];
			}
		}

		// 5. Ordenar manteniendo el índice del empleado
		asort($ordenesValidos);

		// 6. Reindexar los órdenes válidos correlativamente desde 1
		$ordenReindexado = [];
		$n = 1;
		foreach ($ordenesValidos as $idEmp => $oldOrder) {
			$ordenReindexado[$idEmp] = $n;
			$n++;
		}

		// 7. Asignar orden final e igualar a 'ordenOriginal'
		foreach ($empleados as &$emp) {
			$id = $emp['id'];

			if (isset($ordenReindexado[$id])) {
				$emp['orden'] = $ordenReindexado[$id];
			} else {
				$emp['orden'] = $n;
				$n++;
			}

			$emp['ordenOriginal'] = $emp['orden'];
		}
		unset($emp);

		// 8. Guardar la lista de la planilla mensual con sus órdenes
		app(PlanillaMensualServicio::class)->guardarOrdenMensualEmpleados($mes, $anio, $empleados);

		// 9. Cargar campos con campañas activas y lista de códigos de labores
		$camposArray = CampoCampania::pluck('campo')->unique()->values()->toArray();
		$laboresArray = Labores::whereNotNull('codigo')->pluck('codigo')->toArray();

		if (empty($camposArray) || empty($laboresArray)) {
			return;
		}

		// Obtener detalles guardados para relacionar 'plan_men_detalle_id'
		$planMensualDetalles = DB::table('plan_mensual_detalles')
			->join('plan_mensuales', 'plan_mensuales.id', '=', 'plan_mensual_detalles.plan_mensual_id')
			->where('plan_mensuales.mes', $mes)
			->where('plan_mensuales.anio', $anio)
			->select('plan_mensual_detalles.id as plan_men_detalle_id', 'plan_mensual_detalles.documento', 'plan_mensual_detalles.nombres')
			->get();

		$diasEnMes = Carbon::createFromDate($anio, $mes, 1)->daysInMonth;
		$faker = Faker::create('es_PE');

		// Vacaciones: N trabajadores con 5 días hábiles seguidos (a partir de un día al azar)
		$vacaciones = []; // plan_men_detalle_id => [fecha => true]
		foreach ($planMensualDetalles->shuffle()->take((int) ($escenarios['vacaciones'] ?? 0)) as $det) {
			$d = Carbon::createFromDate($anio, $mes, rand(1, max(1, $diasEnMes - 10)));
			for ($n = 0; $n < 5; $d->addDay()) {
				if (!$d->isSunday() && $d->month === $mes) {
					$vacaciones[$det->plan_men_detalle_id][$d->toDateString()] = true;
					$n++;
				}
				if ($d->month !== $mes) {
					break;
				}
			}
		}

		// 10. Recorrer día por día del mes
		for ($dia = 1; $dia <= $diasEnMes; $dia++) {
			$fechaCarbon = Carbon::createFromDate($anio, $mes, $dia);
			$fechaDia = $fechaCarbon->format('Y-m-d');

			// Regla: Los domingos no se trabaja
			if ($fechaCarbon->isSunday()) {
				continue;
			}

			$datosDiarios = [];
			$maxTramosEnElDia = 0;

			// Solo campos con campaña vigente ese día (el registro diario lo exige)
			$camposDelDia = CampoCampania::where('fecha_inicio', '<=', $fechaDia)
				->where(fn($q) => $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $fechaDia))
				->pluck('campo')->unique()->values()->toArray() ?: $camposArray;

			foreach ($planMensualDetalles as $det) {
				// Asistencia aleatoria (90% "A", 10% "F")

				$asistencia = $faker->boolean(90) ? 'A' : 'F';
				if ($completo) {
					$asistencia = 'A';
				}
				$horasSinCampo = 0; // horas pagadas sin trabajo en campo (mano de obra indirecta)

				if (isset($vacaciones[$det->plan_men_detalle_id][$fechaDia])) {
					$asistencia = 'V'; // se paga aparte (vacaciones pagadas), 0 h en el registro
				} elseif (in_array($fechaDia, $feriados, true)) {
					// Criterio manual: se paga el feriado si asistió el día hábil anterior
					$asistencia = 'FR';
					$horasSinCampo = ($ultimaAsistencia[$det->plan_men_detalle_id] ?? 'A') === 'A' ? 8 : 0;
				} elseif ($asistencia === 'A' && $probDm > 0 && $faker->boolean((int) round($probDm * 100))) {
					$asistencia = 'DM';
					$horasSinCampo = $faker->randomElement([4, 8]); // consulta en el pueblo / viaje a Arequipa
				} elseif ($asistencia === 'A' && $probAm > 0 && $faker->boolean((int) round($probAm * 100))) {
					$asistencia = 'AM';
					$horasSinCampo = 4;
				}

				if (!in_array($asistencia, ['FR'], true)) {
					$ultimaAsistencia[$det->plan_men_detalle_id] = $asistencia;
				}

				$row = [
					'plan_men_detalle_id' => $det->plan_men_detalle_id,
					'documento' => $det->documento,
					'nombres' => $det->nombres,
					'asistencia' => $asistencia,
					'total_horas' => $horasSinCampo,
					'total_bono' => '',
				];

				if ($asistencia === 'A') {
					if ($fechaCarbon->isSaturday()) {
						// Sábados: Jornada corta (6:00 a 12:00 -> 6 Horas) dividida en 1 a 2 tramos
						$numTramos = rand(1, 2);
						$tramosGenerados = $this->generarTramosHorario('06.00', '14.00', $numTramos);
					} else {
						// Lunes a Viernes: Jornada (8 a 9 horas) dividida en 3 a 5 tramos
						$numTramos = rand(2, 4);
						$horaFin = $faker->randomElement(['12.00', '16.00']); // 8h o 9h
						if ($completo) {
							$horaFin = '16.00';
						}
						$tramosGenerados = $this->generarTramosHorario('08.00', $horaFin, $numTramos);
					}

					$totalHoras = 0;
					foreach ($tramosGenerados as $idx => $tramo) {
						$num = $idx + 1;
						$row["campo_{$num}"] = $faker->randomElement($camposDelDia);
						$row["labor_{$num}"] = (string) $faker->randomElement($laboresArray);
						$row["entrada_{$num}"] = $tramo['entrada'];
						$row["salida_{$num}"] = $tramo['salida'];

						$totalHoras += $tramo['horas'];
					}

					$row['total_horas'] = round($totalHoras, 2);

					if (count($tramosGenerados) > $maxTramosEnElDia) {
						$maxTramosEnElDia = count($tramosGenerados);
					}
				}

				$datosDiarios[] = $row;
			}

			if (!empty($datosDiarios)) {
				// Actualizar el Resumen Diario con la cantidad EXACTA calculada para este día
				PlanResumenDiario::updateOrCreate(
					['fecha' => $fechaCarbon->format('Y-m-d')],
					['total_actividades' => $maxTramosEnElDia > 0 ? $maxTramosEnElDia : 1]
				);

				// Guardar los registros diarios de la jornada con el $maxTramosEnElDia dinámico
				app(PlanillaRegistroDiarioServicio::class)
					->guardarRegistrosDiarios($fechaDia, $datosDiarios, $maxTramosEnElDia);

				// Detectar y sincronizar actividades de la jornada
				ActividadServicio::detectarYCrearActividades($fechaDia);
			}
		}
	}

	/**
	 * Genera tramos con horarios secuenciales calculando la diferencia exacta en horas decimales.
	 */
	protected function generarTramosHorario(string $horaInicio, string $horaFin, int $cantidadTramos): array
	{
		$inicio = Carbon::createFromFormat('H.i', $horaInicio);
		$fin = Carbon::createFromFormat('H.i', $horaFin);
		$minutosTotales = $inicio->diffInMinutes($fin);

		$minutosPorTramo = floor($minutosTotales / $cantidadTramos);
		$tramos = [];
		$cursor = $inicio->copy();

		for ($i = 0; $i < $cantidadTramos; $i++) {
			$proximo = ($i === $cantidadTramos - 1)
				? $fin->copy()
				: $cursor->copy()->addMinutes($minutosPorTramo);

			$horasDiff = round($cursor->diffInMinutes($proximo) / 60, 2);

			$tramos[] = [
				'entrada' => $cursor->format('H.i'),
				'salida' => $proximo->format('H.i'),
				'horas' => $horasDiff,
			];

			$cursor = $proximo->copy();
		}

		return $tramos;
	}

	/**
	 * Limpia completamente las tablas involucradas.
	 */
	protected function limpiarDatosPrevios(): void
	{
		Schema::disableForeignKeyConstraints();

		PlanSueldo::truncate();
		PlanContrato::truncate();
		PlanEmpleado::truncate();
		Persona::query()->forceDelete();

		Schema::enableForeignKeyConstraints();
	}
}