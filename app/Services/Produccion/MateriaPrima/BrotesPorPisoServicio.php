<?php

namespace App\Services\Produccion\MateriaPrima;

use App\Exports\Produccion\MateriaPrima\PoblacionPlantaExport;
use App\Models\EvalPoblacionPlanta;
use App\Services\Produccion\Planificacion\CampaniaServicio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class BrotesPorPisoServicio
{
    protected CampaniaServicio $campaniaServicio;

    public function __construct(CampaniaServicio $campaniaServicio)
    {
        $this->campaniaServicio = $campaniaServicio;
    }
}