<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ===== Crear roles =====
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $contabilidad = Role::firstOrCreate(['name' => 'Contabilidad', 'guard_name' => 'web']);
        $capataz = Role::firstOrCreate(['name' => 'Capataz', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);

        // ===== Crear usuarios =====
        $users = [
            [
                'name' => 'Benjamin',
                'email' => 'benjamin_unitek@hotmail.com',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
                'role' => $superAdmin,
            ],
            [
                'name' => 'Lucio',
                'email' => 'luciocz@tsh.com.pe',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
                'role' => $admin,
            ],
            [
                'name' => 'Flavia',
                'email' => 'flaviajg@tsh.com.pe',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
                'role' => $admin,
            ],
            [
                'name' => 'Elber',
                'email' => 'elber.2025@ths.com',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
                'role' => $admin,
            ],
        ];

        // ===== Insertar usuarios y asignar roles =====
        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $data['password'],
                    'email_verified_at' => $data['email_verified_at'],
                ]
            );

            $user->syncRoles([$data['role']]);
        }
    }
}
