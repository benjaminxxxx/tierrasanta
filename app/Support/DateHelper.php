<?php

namespace App\Support;

use App\Models\VentaCochinilla;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class DateHelper
{
    public static function fechasCoinciden($fecha1, $fecha2)
    {
        try {
            $f1 = Carbon::parse(FormatoHelper::parseFecha($fecha1))->format('Y-m-d');
            $f2 = Carbon::parse(FormatoHelper::parseFecha($fecha2))->format('Y-m-d');
            return $f1 === $f2;
        } catch (\Exception $e) {
            return false;
        }
    }
}
