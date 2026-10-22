<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Usuario Super Admin para la administración inicial (ve todo; luego se sube el Excel de recursos con los usuarios
 * reales). Correo y clave desde el .env (SUPERADMIN_EMAIL, SUPERADMIN_PASSWORD); sin clave se genera una y se muestra.
 * Si el usuario ya existe no se cambia su clave.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $correo = env('SUPERADMIN_EMAIL', 'admin@tierrasanta.local');
        $usuario = User::where('email', $correo)->first();
        if (!$usuario) {
            $clave = env('SUPERADMIN_PASSWORD') ?: Str::password(14, symbols: false);
            $usuario = new User(['email' => $correo]);
            $usuario->name = 'Super Admin';
            $usuario->password = Hash::make($clave);
            $usuario->email_verified_at = now();
            $usuario->save();
            $this->command?->warn("Super Admin: {$correo} / " . (env('SUPERADMIN_PASSWORD') ? '(clave del .env)' : $clave));
        }
        $usuario->assignRole('Super Admin');
    }
}
