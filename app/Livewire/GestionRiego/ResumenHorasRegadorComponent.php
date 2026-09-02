<?php

namespace App\Livewire\GestionRiego;

use App\Models\ConsolidadoRiego;
use Carbon\Carbon;
use Livewire\Component;

class ResumenHorasRegadorComponent extends Component
{
    public $trabajadorType;
    public $trabajadorId;
    public $fecha;

    public $minutosSemana = 0;
    public $minutosMes = 0;

    public $mostrarDesgloseDias = false;    // clic en badge "Semana" -> ver días de ESA semana
    public $mostrarDesgloseSemanas = false; // clic en badge "Mes" -> ver semanas de ESE mes

    public $desgloseDias = [];
    public $totalSemanaFormateado = '';

    public $desgloseSemanas = [];

    protected $listeners = ['registroConsolidado' => 'calcularTotales','registroRegadoresActualizado' => 'calcularTotales'];
    public function calcularTotales(): void
    {
        $fecha = Carbon::parse($this->fecha);

        $inicioSemana = $fecha->copy()->startOfWeek(Carbon::MONDAY);
        $finSemana = $fecha->copy()->endOfWeek(Carbon::SUNDAY);

        $this->minutosSemana = (int) $this->baseQuery()
            ->whereBetween('fecha', [$inicioSemana->toDateString(), $finSemana->toDateString()])
            ->sum('minutos_jornal');

        $this->minutosMes = (int) $this->baseQuery()
            ->whereYear('fecha', $fecha->year)
            ->whereMonth('fecha', $fecha->month)
            ->sum('minutos_jornal');
    }

    public function mount($trabajadorType, $trabajadorId, $fecha)
    {
        $this->trabajadorType = $trabajadorType;
        $this->trabajadorId = $trabajadorId;
        $this->fecha = $fecha;

        $this->calcularTotales();
    }

    protected function baseQuery()
    {
        return ConsolidadoRiego::where('trabajador_type', $this->trabajadorType)
            ->where('trabajador_id', $this->trabajadorId);
    }



    // Badge "Horas Semana" -> desglose día por día de la semana activa (lunes-domingo)
    public function verDesgloseDias(): void
    {
        $fecha = Carbon::parse($this->fecha);
        $inicioSemana = $fecha->copy()->startOfWeek(Carbon::MONDAY);
        $finSemana = $fecha->copy()->endOfWeek(Carbon::SUNDAY);

        $registrosPorFecha = $this->baseQuery()
            ->whereBetween('fecha', [$inicioSemana->toDateString(), $finSemana->toDateString()])
            ->get(['fecha', 'minutos_jornal'])
            ->keyBy(fn($r) => Carbon::parse($r->fecha)->toDateString());

        $dias = [];
        $totalMinutos = 0;
        $hoy = $fecha->toDateString();

        for ($d = $inicioSemana->copy(); $d->lte($finSemana); $d->addDay()) {
            $clave = $d->toDateString();
            $minutos = (int) ($registrosPorFecha[$clave]->minutos_jornal ?? 0);
            $totalMinutos += $minutos;

            $dias[] = [
                'dia_nombre' => ucfirst($d->translatedFormat('l')),
                'fecha_formateada' => $d->format('d/m'),
                'formateado' => formatear_minutos_horas($minutos),
                'es_activo' => $clave === $hoy,
            ];
        }

        $this->desgloseDias = $dias;
        $this->totalSemanaFormateado = formatear_minutos_horas($totalMinutos);
        $this->mostrarDesgloseDias = true;
    }

    // Badge "Horas Mes" -> desglose semana por semana, usando semanas COMPLETAS
    // (lunes-domingo), aunque se extiendan hacia el mes anterior o siguiente.
    public function verDesgloseSemanas(): void
    {
        $fecha = Carbon::parse($this->fecha);
        $inicioMes = $fecha->copy()->startOfMonth();
        $finMes = $fecha->copy()->endOfMonth();

        $inicioPrimeraSemana = $inicioMes->copy()->startOfWeek(Carbon::MONDAY);
        $finUltimaSemana = $finMes->copy()->endOfWeek(Carbon::SUNDAY);

        // Armar el "esqueleto" de semanas del rango, para no perder semanas sin registros
        $semanas = [];
        for (
            $inicioSemana = $inicioPrimeraSemana->copy();
            $inicioSemana->lte($finUltimaSemana);
            $inicioSemana->addWeek()
        ) {
            $finSemana = $inicioSemana->copy()->endOfWeek(Carbon::SUNDAY);
            $clave = $inicioSemana->toDateString();

            $semanas[$clave] = [
                'inicio' => $inicioSemana->format('d/m'),
                'fin' => $finSemana->format('d/m'),
                'minutos' => 0,
                'incluye_otro_mes' => $inicioSemana->month !== $fecha->month || $finSemana->month !== $fecha->month,
            ];
        }

        $registros = $this->baseQuery()
            ->whereBetween('fecha', [$inicioPrimeraSemana->toDateString(), $finUltimaSemana->toDateString()])
            ->get(['fecha', 'minutos_jornal']);

        foreach ($registros as $registro) {
            $f = Carbon::parse($registro->fecha);
            $clave = $f->copy()->startOfWeek(Carbon::MONDAY)->toDateString();

            if (isset($semanas[$clave])) {
                $semanas[$clave]['minutos'] += $registro->minutos_jornal;
            }
        }

        $this->desgloseSemanas = collect($semanas)->map(fn($s) => [
            'rango' => "{$s['inicio']} - {$s['fin']}",
            'formateado' => formatear_minutos_horas($s['minutos']),
            'incluye_otro_mes' => $s['incluye_otro_mes'],
        ])->values()->toArray();

        $this->mostrarDesgloseSemanas = true;
    }

    public function render()
    {
        return view('livewire.gestion-riego.resumen-horas-regador-component');
    }
}