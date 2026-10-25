<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Preferencias de un usuario: accesos directos y cómo abre los grupos del menú. */
class UsuarioPreferencia extends Model
{
    protected $table = 'usuario_preferencias';

    protected $fillable = ['user_id', 'accesos_directos', 'menu_abrir_activo', 'menu_recordar_abiertos'];

    protected $casts = ['accesos_directos' => 'array', 'menu_abrir_activo' => 'boolean', 'menu_recordar_abiertos' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
