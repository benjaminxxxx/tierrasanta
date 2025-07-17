<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CuadrillaController extends Controller
{
    public function registro_diario(){
        return view("cuadrilla.gestion.reporte_diario");
    }
    public function reporte_semanal(){
        return view("cuadrilla.gestion.reporte_semanal");
    }
    public function bonificaciones(){
        return view("cuadrilla.gestion.bonificaciones");
    }
    public function gestion()
    {
        $modules = [
            [
                'title' => 'Reporte Semanal',
                'description' => 'Crear y gestionar períodos de pago por grupo',
                'icon' => 'fa-calendar-alt',
                'route' => 'gestion_cuadrilleros.reporte-semanal.index',
                'color' => 'bg-orange-500',
            ],
            [
                'title' => 'Detallar Registro Diario',
                'description' => 'Buscar cuadrilleros y asignar actividades diarias',
                'icon' => 'fa-clock',
                'route' => 'gestion_cuadrilleros.registro-diario.index',
                'color' => 'bg-blue-500',
            ],
            [
                'title' => 'Gestión de Actividades',
                'description' => 'Administrar actividades, tramos de bonificación y estándares',
                'icon' => 'fa-tasks',
                'route' => 'gestion_cuadrilleros.actividades.index',
                'color' => 'bg-green-500',
            ],
            [
                'title' => 'Grupos de Pago',
                'description' => 'Configurar grupos y tipos de pago',
                'icon' => 'fa-users',
                'route' => 'gestion_cuadrilleros.pagos.index',
                'color' => 'bg-purple-500',
            ],
            [
                'title' => 'Módulo de Pagos',
                'description' => 'Procesar pagos y generar reportes',
                'icon' => 'fa-dollar-sign',
                'route' => 'gestion_cuadrilleros.pagos.index',
                'color' => 'bg-red-500',
            ],
            [
                'title' => 'Bonificaciones',
                'description' => 'Registrar producción y calcular bonos',
                'icon' => 'fa-chart-bar',
                'route' => 'gestion_cuadrilleros.bonificaciones.index',
                'color' => 'bg-indigo-500',
            ],
        ];
        return view("cuadrilla.gestion.indice", ["modules"=> $modules]);
    }
}
