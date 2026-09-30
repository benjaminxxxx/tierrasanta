<?php

namespace App\Services\Planilla\Plame;

/**
 * Conceptos del PLAME (códigos SUNAT, como en la boleta R08) y la columna de plan_mensual_personals que los guarda.
 */
class PlanillaPlameReglas
{
    /** código => [columna, concepto] */
    public const INGRESOS = [
        '0117' => ['plame_0117_comp_vacacional', 'COMPENSACIÓN VACACIONAL'],
        '0118' => ['plame_0118_rem_vacacional', 'REMUNERACIÓN VACACIONAL'],
        '0121' => ['plame_0121_rem_jornal_basico', 'REMUNERACIÓN O JORNAL BÁSICO'],
        '0201' => ['plame_0201_asignacion_familiar', 'ASIGNACIÓN FAMILIAR'],
        '0312' => ['plame_0312_bonif_ext_temp', 'BONIF. EXTRAORD. TEMPORAL LEY 29351 y 30334'],
        '0314' => ['plame_0314_beta_30', 'BONIF. ESPECIAL POR TRABAJO AGRARIO'],
        '0406' => ['plame_0406_gratif_fiestas_navidad', 'GRATIF. F.PATRIAS NAVIDAD LEY 29351 Y 30334'],
        '0904' => ['plame_0904_cts', 'COMPENSACIÓN TIEMPO DE SERVICIOS'],
    ];

    public const DESCUENTOS = [
        '0601' => ['plame_descuento_0601_comision_afp_pct', 'COMISIÓN AFP PORCENTUAL'],
        '0605' => ['plame_descuento_0605_renta_5ta_retenida', 'RENTA QUINTA CATEGORÍA RETENCIONES'],
        '0606' => ['plame_descuento_0606_prima_seguro_afp', 'PRIMA DE SEGURO AFP'],
        '0607' => ['plame_descuento_0607_snp', 'SISTEMA NACIONAL DE PENSIONES'],
        '0608' => ['plame_descuento_0608_spp_aporte_obligatorio', 'SPP - APORTACIÓN OBLIGATORIA'],
    ];

    public const APORTES_EMPLEADOR = [
        '0803' => ['plame_aporte_empleador_0803_poliza', 'PÓLIZA DE SEGURO - D. LEG. 688'],
        '0804' => ['plame_aporte_empleador_0804_essalud', 'ESSALUD (REGULAR CBSSP AGRAR/AC) TRAB'],
        '0805' => ['plame_aporte_empleador_0805_sctr', 'SCTR PENSIONES'],
        '0810' => ['plame_aporte_empleador_0810_eps', 'EPS - SEGURO COMPLEMENTARIO DE TRAB'],
    ];

    /**
     * Motivos de suspensión: código SUNAT => columna. Las descripciones salen de plan_tipos_suspension
     * (01–08 suspensión perfecta, 20–27 imperfecta; 07 = falta no justificada, 08 = por temporada).
     */
    public const SUSPENSIONES = [
        '01' => 'sp_01', '02' => 'sp_02', '03' => 'sp_03', '04' => 'sp_04',
        '05' => 'sp_05', '06' => 'sp_06', '07' => 'sp_07', '08' => 'sp_08',
        '20' => 'si_20', '21' => 'si_21', '22' => 'si_22', '23' => 'si_23',
        '24' => 'si_24', '25' => 'si_25', '26' => 'si_26', '27' => 'si_27',
    ];

    /** Suspensiones que SUNAT cuenta como días subsidiados (pagados por EsSalud). */
    public const CODIGOS_SUBSIDIADOS = ['21', '22'];
}
