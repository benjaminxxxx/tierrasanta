<?php

namespace App\Livewire\GestionRiego;

use App\Models\AcumulacionUso;
use App\Models\ConsolidadoRiego;
use App\Models\ParametroTemporal;
use App\Services\Campo\Riego\RiegoServicio;
use App\Services\Riego\ConsolidadorServicio;
use App\Services\Riego\ConsolidarJornadaRiegoProceso;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;
use App\Models\LaboresRiego;
use App\Models\Campo;
use App\Models\ReporteDiarioRiego;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\DB;

class ReporteDiarioRiegoHorasAcumuladasComponent extends Component
{
    use LivewireAlert;
    public $mostrarHorasAcumuladasForm = false;
    public function mount()
    {
    }
    public function render()
    {
        return view('livewire.gestion-riego.reporte-diario-riego-detalle-component');
    }
}
