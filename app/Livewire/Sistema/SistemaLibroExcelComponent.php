<?php

namespace App\Livewire\Sistema;

use App\Services\Sistema\LibroExcel\SistemaLibroExcel;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelAnalisis;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelDefinicion;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelProceso;
use App\Services\Sistema\LibroExcel\SistemaLibroExcelRegistro;
use App\Traits\HandlesAlerts;
use Illuminate\Support\Facades\Gate;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Libro Excel del sistema (recursos, insumos, empleados): descargar y subir con vista previa (nuevo / cambia / igual /
 * error por hoja) antes de guardar.
 * - En Sistema → Configuración: barra con todas las hojas del libro.
 * - En la página de un catálogo (labores, campos…): botón flotante "Recursos" con solo las hojas de esa página.
 * - En el escritorio de archivos del inicio: sin botón propio; se abre con el evento abrirLibroExcel(libro, ruta)
 *   cuando se suelta un archivo sobre su ícono.
 * Lo usa quien tenga el permiso de la página (:permiso) o el del libro.
 */
class SistemaLibroExcelComponent extends Component
{
    use WithFileUploads, LivewireAlert, HandlesAlerts;

    #[Locked]
    public string $libro = 'recursos';
    /** @var string[] claves de hojas del libro (y su matriz); vacío = todas */
    #[Locked]
    public array $hojas = [];
    #[Locked]
    public bool $flotante = false;
    /** Sin botón propio: lo abre el escritorio de archivos del inicio */
    #[Locked]
    public bool $escritorio = false;
    #[Locked]
    public ?string $permiso = null;
    #[Locked]
    public string $volverA = '';

    public bool $mostrarImportar = false;
    public $archivo = null;
    public ?string $ruta = null;
    public ?array $analisis = null;
    public ?string $pestania = null;

    public function mount(string $libro = 'recursos', array $hojas = [], bool $flotante = false, bool $escritorio = false, ?string $permiso = null): void
    {
        $this->libro = $libro;
        $reglas = $this->def()->reglas();
        // Actualización en cadena: con una hoja vienen también las que referencia (se descargan y se suben juntas)
        $this->hojas = $hojas ? $reglas::conDependencias(array_values(array_intersect($hojas, $reglas::claves()))) : [];
        $this->flotante = $flotante;
        $this->escritorio = $escritorio;
        $this->permiso = $permiso;
        $this->volverA = url()->current();
    }

    private function def(): SistemaLibroExcelDefinicion
    {
        return SistemaLibroExcelRegistro::definicion($this->libro);
    }

    private function puede(): bool
    {
        return Gate::allows($this->def()->permiso()) || ($this->permiso && Gate::allows($this->permiso));
    }

    /** @return string[]|null null = todas las hojas */
    private function soloHojas(): ?array
    {
        return $this->hojas ?: null;
    }

    public function exportar()
    {
        try {
            abort_unless($this->puede(), 403);
            [$libro, $nombre] = app(\App\Services\Sistema\LibroExcel\SistemaLibroExcelDescarga::class)->preparar(auth()->user(), $this->libro, $this->soloHojas());
            return response()->streamDownload(fn() => (new Xlsx($libro))->save('php://output'), $nombre);
        } catch (\Throwable $e) {
            $this->errorAlert($e);
            return null;
        }
    }

    public function abrirImportar(): void
    {
        $this->reset(['archivo', 'ruta', 'analisis', 'pestania']);
        $this->resetErrorBag();
        $this->mostrarImportar = true;
    }

    /** Desde el escritorio de archivos: el archivo ya se subió (ruta en storage/app) y se analiza aquí. */
    #[On('abrirLibroExcel')]
    public function abrirDesdeEscritorio(string $libro, string $ruta): void
    {
        if (!$this->escritorio || $libro !== $this->libro || !str_starts_with($ruta, 'importaciones/libros/')) {
            return;
        }
        $this->abrirImportar();
        $this->ruta = $ruta;
        $this->analizar();
    }

    public function updatedArchivo(): void
    {
        try {
            abort_unless($this->puede(), 403);
            $this->validate(['archivo' => 'required|file|extensions:xlsx,xlsm|max:20480'], ['archivo.extensions' => 'Sube el archivo Excel (.xlsx o .xlsm).']);
            $this->ruta = $this->archivo->store('importaciones/libros', 'local');
            $this->archivo = null;
            $this->analizar();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }
    }

    private function analizar(): void
    {
        try {
            abort_unless($this->puede(), 403);
            $def = $this->def();
            $this->analisis = app(SistemaLibroExcelAnalisis::class)
                ->analizar($def, app(SistemaLibroExcel::class)->leer($def, storage_path('app/' . $this->ruta), $this->soloHojas()), $this->soloHojas());
            // Primera pestaña: la primera hoja con errores o cambios
            $this->pestania = collect($this->analisis['hojas'])->search(fn($h) => !empty($h['resumen']['error']))
                ?: collect($this->analisis['hojas'])->search(fn($h) => !empty($h['resumen']['nuevo']) || !empty($h['resumen']['cambia']))
                ?: array_key_first($this->analisis['hojas']) ?? 'matriz';
        } catch (\Throwable $e) {
            $this->analisis = null;
            $this->errorAlert($e);
        }
    }

    public function importar()
    {
        try {
            abort_unless($this->ruta && $this->puede(), 403);
            $r = app(SistemaLibroExcelProceso::class)->importar($this->def(), storage_path('app/' . $this->ruta), $this->soloHojas());
            $mensaje = $r ? 'Guardado. ' . collect($r)->map(fn($n, $hoja) => "{$hoja}: {$n}")->implode(', ') . '.' : 'No había nada nuevo ni con cambios.';
            if ($this->flotante || $this->escritorio) {
                // La página se recarga para mostrar lo importado
                return $this->flash('success', $mensaje, ['timer' => 7000], $this->volverA);
            }
            $this->mostrarImportar = false;
            $this->alert('success', $mensaje, ['timer' => 9000]);
        } catch (\Throwable $e) {
            $this->errorAlert($e);
        }
        return null;
    }

    public function render()
    {
        $reglas = $this->def()->reglas();
        return view('livewire.sistema.sistema-libro-excel-component', [
            'visible' => $this->puede(),
            'tituloLibro' => $reglas::TITULO,
            'titulos' => $this->hojas ? $reglas::titulos($this->hojas) : [],
            // Con la macro disponible la descarga sale como .xlsm (botones Actualizar / Subir)
            'nombreArchivo' => SistemaLibroExcel::macroDisponible()
                ? preg_replace('/\.xlsx$/', '.xlsm', $reglas::nombreArchivo($this->soloHojas()))
                : $reglas::nombreArchivo($this->soloHojas()),
        ]);
    }
}
