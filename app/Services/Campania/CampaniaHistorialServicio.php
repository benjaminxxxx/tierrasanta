<?php

namespace App\Services\Campania;

use App\Models\CampoCampania;
use App\Services\Riego\RiegoCampaniaServicio;
use Exception;

class CampaniaHistorialServicio
{
    public $campoCampaniaId;
    public $campoCampania;
    public function __construct($campoCampaniaId = null)
    {
        $this->campoCampaniaId = $campoCampaniaId;
        if ($this->campoCampaniaId) {
            $this->campoCampania = CampoCampania::find($this->campoCampaniaId);
            if (!$this->campoCampania) {
                throw new Exception("La campaña no existe.");
            }
        }
    }

    public function registrarHistorialRiegos()
    {
        if (!$this->campoCampania) {
            return;
        }

        RiegoCampaniaServicio::procesarRiegosParaCampania($this->campoCampania);
    }
}
