<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Excel files configuration
    |--------------------------------------------------------------------------
    */

    'files' => [
        'informacion_general' => env('SYSTEM_EXCEL_BACKUP_GENERAL', 'informacion_general.xlsx'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vigencia del sistema (Y-m-d)
    |--------------------------------------------------------------------------
    | Desde cuándo trabaja esta empresa en este dominio. Se bloquean registros anteriores en los casos que lo usan
    | (ver App\Services\Sistema\Vigencia\SistemaVigenciaReglas). Sin la variable no se bloquea nada.
    */

    'vigencia_desde' => env('SISTEMA_VIGENCIA_DESDE'),

];
