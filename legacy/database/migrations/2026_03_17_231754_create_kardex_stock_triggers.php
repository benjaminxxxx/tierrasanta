<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Procedure: delta incremental ─────────────────────────────
        DB::unprepared('DROP PROCEDURE IF EXISTS sync_kardex_delta');
        DB::unprepared('
CREATE PROCEDURE sync_kardex_delta(
    IN p_producto_id BIGINT,
    IN p_anio        INT,
    IN p_tipo        VARCHAR(10),
    IN p_delta       DECIMAL(15,4)
)
proc: BEGIN
    IF p_tipo IS NULL OR p_tipo = "" THEN
        LEAVE proc;
    END IF;

    UPDATE ins_kardexes
SET stock_actual = stock_actual + p_delta  -- sin GREATEST
WHERE producto_id = p_producto_id
  AND anio        = p_anio
  AND CONVERT(tipo USING utf8mb4) COLLATE utf8mb4_unicode_ci
      = CONVERT(p_tipo USING utf8mb4) COLLATE utf8mb4_unicode_ci;
END
        ');

        // ── Triggers: ins_kardexes (BEFORE) ──────────────────────────
        DB::unprepared('DROP TRIGGER IF EXISTS trg_kardex_bi');
        DB::unprepared('
CREATE TRIGGER trg_kardex_bi
BEFORE INSERT ON ins_kardexes
FOR EACH ROW
BEGIN
    SET NEW.stock_actual =
        NEW.stock_inicial
        + IFNULL((
            SELECT SUM(stock)
            FROM compra_productos
            WHERE producto_id = NEW.producto_id
              AND CONVERT(tipo_kardex USING utf8mb4) COLLATE utf8mb4_unicode_ci
                  = CONVERT(NEW.tipo USING utf8mb4) COLLATE utf8mb4_unicode_ci
              AND YEAR(fecha_compra) = NEW.anio
        ), 0)
        - IFNULL((
            SELECT SUM(cantidad)
            FROM almacen_producto_salidas
            WHERE producto_id = NEW.producto_id
              AND CONVERT(tipo_kardex USING utf8mb4) COLLATE utf8mb4_unicode_ci
                  = CONVERT(NEW.tipo USING utf8mb4) COLLATE utf8mb4_unicode_ci
              AND YEAR(fecha_reporte) = NEW.anio
        ), 0);
END
        ');

        DB::unprepared('DROP TRIGGER IF EXISTS trg_kardex_bu');
        DB::unprepared('
CREATE TRIGGER trg_kardex_bu
BEFORE UPDATE ON ins_kardexes
FOR EACH ROW
BEGIN
    IF NEW.stock_inicial <> OLD.stock_inicial
       OR NEW.tipo        <> OLD.tipo
       OR NEW.anio        <> OLD.anio
       OR NEW.producto_id <> OLD.producto_id
    THEN
        SET NEW.stock_actual =
            NEW.stock_inicial
            + IFNULL((
                SELECT SUM(stock)
                FROM compra_productos
                WHERE producto_id = NEW.producto_id
                  AND CONVERT(tipo_kardex USING utf8mb4) COLLATE utf8mb4_unicode_ci
                      = CONVERT(NEW.tipo USING utf8mb4) COLLATE utf8mb4_unicode_ci
                  AND YEAR(fecha_compra) = NEW.anio
            ), 0)
            - IFNULL((
                SELECT SUM(cantidad)
                FROM almacen_producto_salidas
                WHERE producto_id = NEW.producto_id
                  AND CONVERT(tipo_kardex USING utf8mb4) COLLATE utf8mb4_unicode_ci
                      = CONVERT(NEW.tipo USING utf8mb4) COLLATE utf8mb4_unicode_ci
                  AND YEAR(fecha_reporte) = NEW.anio
            ), 0);
    END IF;
END
        ');

        // ── Triggers: compra_productos ────────────────────────────────
        DB::unprepared('DROP TRIGGER IF EXISTS trg_compra_ai');
        DB::unprepared('
CREATE TRIGGER trg_compra_ai
AFTER INSERT ON compra_productos
FOR EACH ROW
BEGIN
    IF NEW.tipo_kardex IS NOT NULL AND NEW.tipo_kardex <> "" THEN
        CALL sync_kardex_delta(
            NEW.producto_id,
            YEAR(NEW.fecha_compra),
            NEW.tipo_kardex,
            NEW.stock
        );
    END IF;
END
        ');

        DB::unprepared('DROP TRIGGER IF EXISTS trg_compra_au');
        DB::unprepared('
CREATE TRIGGER trg_compra_au
AFTER UPDATE ON compra_productos
FOR EACH ROW
BEGIN
    IF OLD.tipo_kardex IS NOT NULL AND OLD.tipo_kardex <> "" THEN
        CALL sync_kardex_delta(
            OLD.producto_id,
            YEAR(OLD.fecha_compra),
            OLD.tipo_kardex,
            -OLD.stock
        );
    END IF;

    IF NEW.tipo_kardex IS NOT NULL AND NEW.tipo_kardex <> "" THEN
        CALL sync_kardex_delta(
            NEW.producto_id,
            YEAR(NEW.fecha_compra),
            NEW.tipo_kardex,
            NEW.stock
        );
    END IF;
END
        ');

        DB::unprepared('DROP TRIGGER IF EXISTS trg_compra_ad');
        DB::unprepared('
CREATE TRIGGER trg_compra_ad
AFTER DELETE ON compra_productos
FOR EACH ROW
BEGIN
    IF OLD.tipo_kardex IS NOT NULL AND OLD.tipo_kardex <> "" THEN
        CALL sync_kardex_delta(
            OLD.producto_id,
            YEAR(OLD.fecha_compra),
            OLD.tipo_kardex,
            -OLD.stock
        );
    END IF;
END
        ');

        // ── Triggers: almacen_producto_salidas ────────────────────────
        DB::unprepared('DROP TRIGGER IF EXISTS trg_salida_ai');
        DB::unprepared('
CREATE TRIGGER trg_salida_ai
AFTER INSERT ON almacen_producto_salidas
FOR EACH ROW
BEGIN
    IF NEW.tipo_kardex IS NOT NULL AND NEW.tipo_kardex <> "" THEN
        CALL sync_kardex_delta(
            NEW.producto_id,
            YEAR(NEW.fecha_reporte),
            NEW.tipo_kardex,
            -NEW.cantidad
        );
    END IF;
END
        ');

        DB::unprepared('DROP TRIGGER IF EXISTS trg_salida_au');
        DB::unprepared('
CREATE TRIGGER trg_salida_au
AFTER UPDATE ON almacen_producto_salidas
FOR EACH ROW
BEGIN
    IF OLD.tipo_kardex IS NOT NULL AND OLD.tipo_kardex <> "" THEN
        CALL sync_kardex_delta(
            OLD.producto_id,
            YEAR(OLD.fecha_reporte),
            OLD.tipo_kardex,
            OLD.cantidad
        );
    END IF;

    IF NEW.tipo_kardex IS NOT NULL AND NEW.tipo_kardex <> "" THEN
        CALL sync_kardex_delta(
            NEW.producto_id,
            YEAR(NEW.fecha_reporte),
            NEW.tipo_kardex,
            -NEW.cantidad
        );
    END IF;
END
        ');

        DB::unprepared('DROP TRIGGER IF EXISTS trg_salida_ad');
        DB::unprepared('
CREATE TRIGGER trg_salida_ad
AFTER DELETE ON almacen_producto_salidas
FOR EACH ROW
BEGIN
    IF OLD.tipo_kardex IS NOT NULL AND OLD.tipo_kardex <> "" THEN
        CALL sync_kardex_delta(
            OLD.producto_id,
            YEAR(OLD.fecha_reporte),
            OLD.tipo_kardex,
            OLD.cantidad
        );
    END IF;
END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_kardex_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_kardex_bu');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_compra_ai');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_compra_au');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_compra_ad');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_salida_ai');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_salida_au');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_salida_ad');
        DB::unprepared('DROP PROCEDURE IF EXISTS sync_kardex_delta');
    }
};