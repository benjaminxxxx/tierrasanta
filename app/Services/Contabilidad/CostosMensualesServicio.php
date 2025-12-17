<?php

namespace App\Services\Contabilidad;

use App\Models\CostoMensual;
use DB;
use DomainException;

class CostosMensualesServicio
{
    public function guardar(array $form): CostoMensual
    {
        $this->validar($form);

        return DB::transaction(function () use ($form) {

            return CostoMensual::updateOrCreate(
                [
                    'anio' => (int) $form['anio'],
                    'mes' => (int) $form['mes'],
                ],
                $this->mapearDatos($form)
            );
        });
    }

    /**
     * Validaciones de dominio
     */
    protected function validar(array $form): void
    {
        if (empty($form['anio']) || empty($form['mes'])) {
            throw new DomainException('Debe seleccionar año y mes.');
        }

        if ($form['mes'] < 1 || $form['mes'] > 12) {
            throw new DomainException('El mes seleccionado no es válido.');
        }
    }

    /**
     * Normaliza y prepara los datos para persistencia
     */
    protected function mapearDatos(array $form): array
    {
        return [
            // FIJOS
            'fijo_administrativo_blanco' => $this->num($form['fijo_administrativo_blanco'] ?? null),
            'fijo_administrativo_negro' => $this->num($form['fijo_administrativo_negro'] ?? null),

            'fijo_financiero_blanco' => $this->num($form['fijo_financiero_blanco'] ?? null),
            'fijo_financiero_negro' => $this->num($form['fijo_financiero_negro'] ?? null),

            'fijo_gastos_oficina_blanco' => $this->num($form['fijo_gastos_oficina_blanco'] ?? null),
            'fijo_gastos_oficina_negro' => $this->num($form['fijo_gastos_oficina_negro'] ?? null),

            'fijo_depreciaciones_blanco' => $this->num($form['fijo_depreciaciones_blanco'] ?? null),
            'fijo_depreciaciones_negro' => $this->num($form['fijo_depreciaciones_negro'] ?? null),

            'fijo_costo_terreno_blanco' => $this->num($form['fijo_costo_terreno_blanco'] ?? null),
            'fijo_costo_terreno_negro' => $this->num($form['fijo_costo_terreno_negro'] ?? null),

            // OPERATIVOS
            'operativo_servicios_fundo_blanco' => $this->num($form['operativo_servicios_fundo_blanco'] ?? null),
            'operativo_servicios_fundo_negro' => $this->num($form['operativo_servicios_fundo_negro'] ?? null),

            'operativo_mano_obra_indirecta_blanco' => $this->num($form['operativo_mano_obra_indirecta_blanco'] ?? null),
            'operativo_mano_obra_indirecta_negro' => $this->num($form['operativo_mano_obra_indirecta_negro'] ?? null),
        ];
    }

    /**
     * Convierte null / '' a 0.00 y asegura float
     */
    protected function num($valor): float
    {
        if ($valor === '' || $valor === null) {
            return 0.0;
        }

        return (float) $valor;
    }
}
