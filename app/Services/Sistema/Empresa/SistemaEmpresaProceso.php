<?php

namespace App\Services\Sistema\Empresa;

use App\Exceptions\ErrorControlado;
use App\Models\Empresa;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Support\Facades\DB;

/**
 * Datos de la empresa de este sistema (una sola fila en `empresa`): razón social, RUC y establecimiento. Los usan los
 * encabezados de reportes y Excel. Se editan en Sistema → Configuración o en la hoja "Empresa" del Excel de recursos.
 */
class SistemaEmpresaProceso
{
    /**
     * Logos de la empresa: se suben en Configuración y se guardan en storage/app/empresa (fuera de git: cada servidor
     * tiene los suyos). Sin logo subido se usa el de public/images. tipo => [nombre, archivo por defecto]
     */
    public const LOGOS = [
        'icono' => ['Ícono (menú lateral y pestaña del navegador)', 'images/logo/logotipo.svg'],
        'horizontal' => ['Logo horizontal a color (fondo claro)', 'images/logo/logo-horizontal-color.svg'],
        'horizontal_claro' => ['Logo horizontal claro (fondo oscuro)', 'images/logo/logo-horizontal.svg'],
    ];
    private const EXTENSIONES = ['svg', 'png', 'jpg', 'jpeg', 'webp'];
    private const TAMANO_MAXIMO_KB = 1024;

    private static ?array $cache = null;

    /** @return array{ruc: ?string, razon_social: ?string, establecimiento: ?string} */
    public static function datos(): array
    {
        if (self::$cache === null) {
            try {
                $e = Empresa::first();
            } catch (\Throwable) {
                $e = null; // instalación sin tablas todavía
            }
            self::$cache = ['ruc' => $e?->ruc, 'razon_social' => $e?->razon_social, 'establecimiento' => $e?->establecimiento];
        }
        return self::$cache;
    }

    /** Nombre de la empresa para títulos y encabezados: la razón social de Configuración (o APP_NAME si no hay). */
    public static function nombre(): string
    {
        return trim((string) self::datos()['razon_social']) ?: (string) config('app.name');
    }

    /** Siglas para el menú lateral: iniciales de la razón social sin el tipo de sociedad (TIERRA NUEVA HOLDING S.A.C. → TNH). */
    public static function siglas(): string
    {
        $palabras = preg_split('/\s+/', mb_strtoupper(self::nombre()));
        $palabras = array_filter($palabras, fn($p) => $p !== '' && !preg_match('/^(S\.?A\.?C?\.?|S\.?R\.?L\.?|E\.?I\.?R\.?L\.?|S\.?A\.?A\.?|DE|LA|EL|Y)$/', $p));
        return implode('', array_map(fn($p) => mb_substr($p, 0, 1), array_slice(array_values($palabras), 0, 4))) ?: 'TS';
    }

    // ------------------------------------------------------------------ logos

    /** URL del logo (el subido o el de siempre). La versión evita que el navegador muestre uno anterior. */
    public static function logoUrl(string $tipo): string
    {
        $subido = self::logoSubido($tipo);
        return $subido ? route('empresa.logo', ['tipo' => $tipo, 'v' => substr(md5($subido), 0, 8)]) : asset(self::LOGOS[$tipo][1]);
    }

    /** Ruta relativa (en storage/app) del logo subido, o null. */
    public static function logoSubido(string $tipo): ?string
    {
        if (!isset(self::LOGOS[$tipo])) {
            return null;
        }
        try {
            $ruta = DB::table('configuracion')->where('codigo', "empresa_logo_{$tipo}")->value('valor');
        } catch (\Throwable) {
            return null;
        }
        return $ruta && \Illuminate\Support\Facades\Storage::disk('local')->exists($ruta) ? $ruta : null;
    }

    /** Respuesta HTTP con el logo (ruta pública: también se ve en el login). */
    public static function respuestaLogo(string $tipo)
    {
        abort_unless(isset(self::LOGOS[$tipo]), 404);
        $ruta = self::logoSubido($tipo);
        $archivo = $ruta ? \Illuminate\Support\Facades\Storage::disk('local')->path($ruta) : public_path(self::LOGOS[$tipo][1]);
        $tipoMime = str_ends_with($archivo, '.svg') ? 'image/svg+xml' : (mime_content_type($archivo) ?: 'application/octet-stream');
        return response()->file($archivo, [
            'Content-Type' => $tipoMime,
            'Cache-Control' => 'public, max-age=604800',
            // Un SVG abierto directamente no puede ejecutar nada
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; img-src data:",
        ]);
    }

    public function guardarLogo(string $tipo, \Illuminate\Http\UploadedFile $archivo): void
    {
        if (!isset(self::LOGOS[$tipo])) {
            throw new ErrorControlado('Tipo de logo no válido.');
        }
        $ext = mb_strtolower($archivo->getClientOriginalExtension());
        if (!in_array($ext, self::EXTENSIONES, true)) {
            throw new ErrorControlado('El logo debe ser SVG, PNG, JPG o WEBP.');
        }
        if ($archivo->getSize() > self::TAMANO_MAXIMO_KB * 1024) {
            throw new ErrorControlado('El logo pesa más de ' . self::TAMANO_MAXIMO_KB . ' KB.');
        }
        if ($ext === 'svg') {
            $svg = (string) file_get_contents($archivo->getRealPath());
            if (!str_contains($svg, '<svg') || preg_match('/<script|on[a-z]+\s*=|javascript:|<foreignObject|<!ENTITY/i', $svg)) {
                throw new ErrorControlado('El SVG no es válido o contiene código (scripts o eventos). Expórtalo de nuevo como imagen simple.');
            }
        } elseif (!@getimagesize($archivo->getRealPath())) {
            throw new ErrorControlado('El archivo no es una imagen válida.');
        }
        $anterior = self::logoSubido($tipo);
        $ruta = $archivo->storeAs('empresa', "logo-{$tipo}-" . now()->format('YmdHis') . ".{$ext}", 'local');
        DB::table('configuracion')->updateOrInsert(['codigo' => "empresa_logo_{$tipo}"],
            ['valor' => $ruta, 'descripcion' => 'Logo de la empresa: ' . self::LOGOS[$tipo][0], 'updated_at' => now(), 'created_at' => now()]);
        if ($anterior && $anterior !== $ruta) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($anterior);
        }
        AuditoriaServicio::registrar(Empresa::class, "logo_{$tipo}", 'editar', ['logo' => $anterior], ['logo' => $ruta], 'Logo de la empresa: ' . self::LOGOS[$tipo][0]);
    }

    /** Vuelve al logo de siempre. */
    public function quitarLogo(string $tipo): void
    {
        $anterior = self::logoSubido($tipo);
        DB::table('configuracion')->where('codigo', "empresa_logo_{$tipo}")->delete();
        if ($anterior) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($anterior);
            AuditoriaServicio::registrar(Empresa::class, "logo_{$tipo}", 'eliminar', ['logo' => $anterior], null, 'Logo de la empresa: vuelve al de siempre');
        }
    }

    public function guardar(string $ruc, string $razonSocial, ?string $establecimiento): void
    {
        $ruc = preg_replace('/\D/', '', $ruc);
        if (!preg_match('/^\d{11}$/', $ruc)) {
            throw new ErrorControlado('El RUC debe tener 11 dígitos.');
        }
        if (trim($razonSocial) === '') {
            throw new ErrorControlado('La razón social es obligatoria.');
        }
        DB::transaction(function () use ($ruc, $razonSocial, $establecimiento) {
            $antes = self::datos();
            // Una sola empresa por sistema
            Empresa::where('ruc', '<>', $ruc)->delete();
            Empresa::updateOrCreate(['ruc' => $ruc], ['razon_social' => trim($razonSocial), 'establecimiento' => trim((string) $establecimiento) ?: '']);
            self::$cache = null;
            AuditoriaServicio::registrar(Empresa::class, $ruc, 'editar', $antes, self::datos(), 'Datos de la empresa');
        });
    }
}
