<?php

namespace App\Livewire\Sistema;

use App\Constants\Permisos;
use App\Http\Controllers\Sistema\SistemaArchivoController;
use App\Services\Sistema\LibroExcel\SistemaLibroExcel;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelRegistro;
use App\Traits\HandlesAlerts;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Escritorio de archivos del inicio: íconos de Excel estilo escritorio. Clic = descarga con los datos frescos (ruta
 * archivos.*). Soltar un archivo sobre el ícono (o "Subir") = se envía al servidor y se abre la vista previa del libro
 * (SistemaLibroExcelComponent en modo escritorio). Cada ícono dice cuándo lo bajó el usuario y si los datos cambiaron.
 */
class SistemaEscritorioArchivosComponent extends Component
{
    use WithFileUploads, LivewireAlert, HandlesAlerts;

    public $archivo = null;
    public string $destino = '';

    /** Nombres cortos de los libros en el escritorio */
    private const NOMBRES = ['recursos' => 'Recursos', 'insumos' => 'Insumos', 'empleados' => 'Empleados'];

    public function updatedArchivo(): void
    {
        try {
            $def = SistemaLibroExcelRegistro::definicion($this->destino);
            abort_unless(Gate::allows($def->permiso()), 403);
            $this->validate(['archivo' => 'required|file|extensions:xlsx,xlsm|max:20480'], ['archivo.extensions' => 'Suelta un archivo Excel (.xlsx o .xlsm).']);
            $ruta = $this->archivo->store('importaciones/libros', 'local');
            $this->archivo = null;
            $this->dispatch('abrirLibroExcel', libro: $this->destino, ruta: $ruta);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->alert('error', $e->validator->errors()->first(), ['position' => 'center', 'toast' => false, 'timer' => null]);
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
    }

    public function render()
    {
        $archivos = [];
        foreach (SistemaLibroExcelRegistro::disponibles() as $clave => $def) {
            if (!Gate::allows($def->permiso())) {
                continue;
            }
            $archivos[] = [
                'clave' => $clave, 'nombre' => self::NOMBRES[$clave] ?? $def->reglas()::TITULO, 'archivo' => SistemaLibroExcel::macroDisponible() ? preg_replace('/\.xlsx$/', '.xlsm', $def->reglas()::nombreArchivo()) : $def->reglas()::nombreArchivo(),
                'url' => route('archivos.libro', $clave), 'subible' => true, 'estado' => $this->estado($clave, $def),
            ];
        }
        $anio = now()->year;
        if (Gate::allows(Permisos::COCHINILLA_INGRESO)) {
            $archivos[] = ['clave' => 'cochinilla', 'nombre' => "Cochinilla {$anio}", 'archivo' => "COCHINILLA {$anio}.xlsx",
                'url' => route('archivos.cochinilla', $anio), 'subible' => false, 'estado' => null,
                'nota' => 'Se sube en Cochinilla → Ingreso (Excel oficial).'];
        }
        if (Gate::allows(Permisos::INSUMO_COMPRA)) {
            $mes = now()->month;
            $archivos[] = ['clave' => 'compras', 'nombre' => 'Compras ' . str_pad($mes, 2, '0', STR_PAD_LEFT) . "/{$anio}", 'archivo' => sprintf('COMPRAS %04d-%02d.xlsx', $anio, $mes),
                'url' => route('archivos.compras', [$anio, $mes]), 'subible' => false, 'estado' => null,
                'nota' => 'Se sube en Almacén → Entradas (compras).'];
        }
        return view('livewire.sistema.sistema-escritorio-archivos-component', compact('archivos'));
    }

    /** Última descarga del usuario y si los datos siguen iguales desde entonces. */
    private function estado(string $clave, $def): ?array
    {
        $descarga = Cache::get(SistemaArchivoController::claveDescarga($clave));
        if (!$descarga) {
            return null;
        }
        $alDia = app(SistemaLibroExcel::class)->firma($def) === $descarga['firma'];
        return ['cuando' => Carbon::parse($descarga['cuando'])->diffForHumans(), 'al_dia' => $alDia];
    }
}
