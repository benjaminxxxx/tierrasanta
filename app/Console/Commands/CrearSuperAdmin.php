<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class CrearSuperAdmin extends Command
{
    protected $signature = 'crear:superadmin {email}';
    protected $description = 'Crea el rol Super Admin (si no existe) y lo asigna a un usuario por email';

    public function handle()
    {
        $email = $this->argument('email');

        // Buscar o crear el rol
        $role = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $this->info("Rol 'Super Admin' verificado o creado.");

        // Buscar el usuario
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("No se encontró un usuario con email: {$email}");
            return;
        }

        // Asignar el rol
        $user->assignRole($role);

        $this->info("El rol 'Super Admin' fue asignado a {$user->email}.");
    }
}
