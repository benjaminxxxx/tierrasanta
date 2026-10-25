<?php

namespace App\View\Components\Layouts;

use App\Services\Sistema\Menu\SistemaMenuConsulta;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

/**
 * Menú lateral estilo explorador de archivos: Inicio, "Acceso directo" (de cada usuario) y los grupos en árbol.
 * La definición está en SistemaMenuReglas y la visibilidad en SistemaMenuConsulta.
 */
class Sidebar extends Component
{
    public array $menu = [];

    /** ruta => opción visible (para dibujar los accesos directos en el navegador) */
    public array $opciones = [];

    /** Rutas de los accesos directos del usuario */
    public array $accesos = [];

    /** Cómo abre los grupos: abrir_activo, recordar_abiertos (por defecto todos sin expandir) */
    public array $preferencias = [];

    public function __construct()
    {
        $user = auth()->user();
        $this->menu = SistemaMenuConsulta::menu($user, Route::currentRouteName());
        $this->opciones = SistemaMenuConsulta::opciones($user);
        $this->accesos = SistemaMenuConsulta::accesos($user, $this->opciones);
        $this->preferencias = SistemaMenuConsulta::preferencias($user);
    }

    public function render(): View|Closure|string
    {
        return view('components.layouts.sidebar');
    }
}
