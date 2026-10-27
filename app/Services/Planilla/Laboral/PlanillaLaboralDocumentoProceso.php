<?php

namespace App\Services\Planilla\Laboral;

use App\Exceptions\ErrorControlado;
use App\Models\PlanContrato;
use App\Models\PlanContratoDocumento;
use App\Services\Reporte\AuditoriaServicio;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Documentos de un contrato (sustento del cese). Se pueden adjuntar al finalizar o después. Los archivos van al disco
 * privado "local" (no son públicos: se descargan desde el panel del empleado).
 */
class PlanillaLaboralDocumentoProceso
{
    private const DISCO = 'local';

    public function adjuntar(PlanContrato $contrato, UploadedFile $archivo, string $tipo, ?string $fechaDocumento = null, ?string $observacion = null): PlanContratoDocumento
    {
        if (!array_key_exists($tipo, PlanillaLaboralReglas::TIPOS_DOCUMENTO)) {
            throw new ErrorControlado('Elige el tipo de documento.');
        }
        $extension = strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension());
        if (!in_array($extension, PlanillaLaboralReglas::DOCUMENTO_EXTENSIONES, true)) {
            throw new ErrorControlado('El documento debe ser PDF o imagen (' . implode(', ', PlanillaLaboralReglas::DOCUMENTO_EXTENSIONES) . ').');
        }
        if ($archivo->getSize() > PlanillaLaboralReglas::DOCUMENTO_MAX_KB * 1024) {
            throw new ErrorControlado('El documento pesa más de ' . (PlanillaLaboralReglas::DOCUMENTO_MAX_KB / 1024) . ' MB.');
        }

        $carpeta = "planilla/contratos/{$contrato->plan_empleado_id}/{$contrato->id}";
        $nombre = now()->format('Ymd_His') . '_' . $tipo . '_' . Str::random(6) . '.' . $extension;
        $ruta = $archivo->storeAs($carpeta, $nombre, self::DISCO);

        try {
            return DB::transaction(function () use ($contrato, $archivo, $tipo, $fechaDocumento, $observacion, $ruta) {
                $doc = PlanContratoDocumento::create([
                    'plan_contrato_id' => $contrato->id,
                    'tipo' => $tipo,
                    'fecha_documento' => $fechaDocumento ?: null,
                    'nombre_original' => mb_substr($archivo->getClientOriginalName(), 0, 250),
                    'ruta' => $ruta,
                    'mime' => $archivo->getMimeType(),
                    'tamano' => $archivo->getSize(),
                    'observacion' => $observacion ? mb_substr(trim($observacion), 0, 250) : null,
                    'subido_por' => Auth::id(),
                ]);
                AuditoriaServicio::registrar(PlanContrato::class, $contrato->id, 'editar', null,
                    ['documento' => $doc->only(['id', 'tipo', 'fecha_documento', 'nombre_original'])],
                    'Documento adjuntado: ' . PlanillaLaboralReglas::TIPOS_DOCUMENTO[$tipo]);
                return $doc;
            });
        } catch (\Throwable $e) {
            Storage::disk(self::DISCO)->delete($ruta); // no dejar archivos huérfanos
            throw $e;
        }
    }

    public function eliminar(PlanContratoDocumento $doc): void
    {
        DB::transaction(function () use ($doc) {
            AuditoriaServicio::registrar(PlanContrato::class, $doc->plan_contrato_id, 'editar',
                ['documento' => $doc->only(['id', 'tipo', 'fecha_documento', 'nombre_original'])], null,
                'Documento eliminado: ' . (PlanillaLaboralReglas::TIPOS_DOCUMENTO[$doc->tipo] ?? $doc->tipo));
            $doc->delete();
        });
        Storage::disk(self::DISCO)->delete($doc->ruta);
    }

    /** Respuesta de descarga del archivo (con su nombre original). */
    public function descargar(PlanContratoDocumento $doc)
    {
        if (!Storage::disk(self::DISCO)->exists($doc->ruta)) {
            throw new ErrorControlado('El archivo ya no está en el servidor.');
        }
        return Storage::disk(self::DISCO)->download($doc->ruta, $doc->nombre_original);
    }
}
