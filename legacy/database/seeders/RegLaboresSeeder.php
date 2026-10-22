<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegLaboresSeeder extends Seeder
{
    public function run(): void
    {
        // No hay columnas de auditoría (creado_por, etc.) en reg_labores,
        // así que no debería haber conflicto de FK aquí — lo dejo desactivado
        // solo como salvaguarda, igual que en LaboresSeeder.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::unprepared("
            INSERT INTO `reg_labores` (`id`, `nombre_labor`, `es_riego`, `es_apoyo_riego`, `consumo_m3_hora`, `created_at`, `updated_at`) VALUES
            (1, 'Instalación Arco de Riego', 0, 0, NULL, '2024-10-16 01:22:44', '2024-10-16 01:22:44'),
            (2, 'Reparación de Tuberia', 0, 0, NULL, '2024-10-16 01:22:58', '2024-10-16 01:22:58'),
            (4, 'Mantenimiento de Válvula', 0, 0, NULL, '2024-10-16 01:25:27', '2024-10-16 01:25:27'),
            (5, 'Lavado de Filtro', 0, 0, NULL, '2024-10-16 01:28:09', '2024-10-16 01:28:09'),
            (9, 'Riego', 1, 0, 100.00, '2024-10-21 18:02:09', '2026-09-04 20:55:33'),
            (10, 'Observacion', 0, 0, NULL, '2024-10-21 19:32:43', '2024-10-21 19:32:43'),
            (12, 'Instalación de Tuberia', 0, 0, NULL, '2024-10-22 17:22:02', '2024-10-22 17:22:02'),
            (13, 'Colocación de Cinta', 0, 0, NULL, '2024-10-22 17:24:04', '2024-10-22 17:24:04'),
            (15, 'Mantenimiento de Estanque', 0, 0, NULL, '2024-11-15 18:57:15', '2024-11-15 18:57:15'),
            (16, 'Instalación Sistema de Aspersión', 0, 0, NULL, '2024-11-15 18:58:22', '2024-11-15 18:58:22'),
            (17, 'Apoyo Riego', 0, 1, NULL, '2024-11-15 19:14:37', '2026-09-04 20:54:15'),
            (18, 'instalación salidas para cinta de riego', 0, 0, NULL, '2024-12-19 18:36:40', '2024-12-19 18:36:40'),
            (20, 'Instalación salidas para sistema de aspersión', 0, 0, NULL, '2024-12-19 18:38:00', '2024-12-19 18:38:00'),
            (22, 'Desmonte sistema de aspersión', 0, 0, NULL, '2024-12-19 18:38:40', '2024-12-19 18:38:40'),
            (23, 'Parchado de cinta', 0, 0, NULL, '2024-12-19 18:38:56', '2024-12-19 18:38:56'),
            (24, 'Deshierbo', 0, 0, NULL, '2024-12-19 18:39:16', '2024-12-19 18:39:16'),
            (25, 'Cerco Perimetrico', 0, 0, NULL, '2025-08-05 15:01:17', '2025-08-05 15:01:17'),
            (26, 'Limpieza Acequias', 0, 0, NULL, '2026-02-28 13:37:51', '2026-02-28 13:37:51'),
            (27, 'Limpieza Estanque ', 0, 0, NULL, '2026-03-03 19:03:49', '2026-03-03 19:03:49'),
            (29, 'Traslado sistema de aspersión', 0, 0, NULL, '2026-03-04 20:41:47', '2026-03-04 20:41:47'),
            (30, 'Riego por sistema de aspersión', 1, 0, 500.00, '2026-03-05 15:10:51', '2026-09-04 21:00:34'),
            (32, 'Mantenimiento de cabezal', 0, 0, NULL, '2026-06-30 14:15:36', '2026-06-30 14:15:36'),
            (33, 'LIMPIEZA DESARENADOR', 0, 0, NULL, '2026-06-30 19:42:58', '2026-06-30 19:42:58'),
            (34, 'MANTENIMIENTO DE CABEZAL', 0, 0, NULL, '2026-07-02 12:49:35', '2026-07-02 12:49:35'),
            (35, 'Retiro de Cinta', 0, 0, NULL, '2026-08-08 15:15:25', '2026-08-08 15:15:25'),
            (36, 'apoyo', 1, 0, 234.00, '2026-09-04 20:45:33', '2026-09-04 20:54:07');
        ");

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}