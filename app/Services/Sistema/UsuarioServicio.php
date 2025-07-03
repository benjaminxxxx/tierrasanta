<?php

namespace App\Services\Sistema;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UsuarioServicio
{
    /**
     * Valida los datos de creación o actualización de usuario
     */
    public static function validarDatos(array $data, $userId = null): array
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId)
            ],
            'password' => $userId ? [] : ['required', 'string', 'min:2', 'max:255'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.unique' => 'El correo ya existe para otro usuario.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo debe ser válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 2 caracteres.',
            'password.max' => 'La contraseña no puede tener más de 255 caracteres.',
        ])->validate();
    }

    /**
     * Crea o actualiza el usuario con los datos dados.
     */
    public static function guardarUsuario(?int $userId, array $data, array $rolesSeleccionados): User
    {
        if (!$userId) {
            // Crear nuevo usuario
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
        } else {
            // Actualizar usuario
            $user = User::findOrFail($userId);
            $user->name = $data['name'];
            $user->email = $data['email'];
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            $user->save();
        }
        // Sincronizar roles
        $user->syncRoles($rolesSeleccionados);

        return $user;
    }

    /**
     * Obtiene un usuario con sus datos para el formulario
     */
    public static function obtenerUsuarioPorId(int $userId): ?User
    {
        return User::find($userId);
    }

    /**
     * Elimina un usuario por ID, desvinculando sus roles y permisos.
     */
    public static function eliminarUsuarioPorId(int $id): void
    {
        $usuario = User::findOrFail($id);

        // Desvincular roles y permisos
        $usuario->roles()->detach();
        $usuario->permissions()->detach();

        // Eliminar usuario
        $usuario->delete();
    }
    public static function obtenerUsuariosConRoles()
    {
        return User::with('roles')->get();
    }
}
