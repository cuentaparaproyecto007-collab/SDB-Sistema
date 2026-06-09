<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Listar todos los usuarios con sus roles (Solo los activos).
     */
    public function index()
    {
        // Laravel automáticamente filtra y excluye a los usuarios que tengan 'deleted_at' != null
        $users = User::with('role')->get();
        return response()->json($users);
    }

    /**
     * Obtener todos los roles disponibles.
     */
    public function getRoles()
    {
        return response()->json(Role::all());
    }

    /**
     * Crear un nuevo usuario.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombres'          => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'required|string|max:100',
            // 🔥 CONTROL DE INTEGRIDAD: El CI es único solo entre usuarios activos
            'ci'               => [
                'required', 
                'string', 
                Rule::unique('users', 'ci')->whereNull('deleted_at')
            ],
            'direccion'        => 'nullable|string|max:255',
            'celular'          => 'nullable|numeric|digits_between:7,10', 
            // 🔥 CONTROL DE INTEGRIDAD: El Email es único solo entre usuarios activos
            'email'            => [
                'required', 
                'string', 
                'email', 
                Rule::unique('users', 'email')->whereNull('deleted_at')
            ],
            'password'         => 'required|string|min:8',
            'role_id'          => 'required', 
        ]);

        $roleId = $this->resolveRoleId($validated['role_id']);

        if (!$roleId) {
            return response()->json(['message' => 'El rol especificado no es válido.'], 422);
        }

        $fullName = trim($validated['nombres'] . ' ' . $validated['apellido_paterno'] . ' ' . $validated['apellido_materno']);

        $user = User::create([
            'name'             => $fullName,
            'nombres'          => $validated['nombres'],
            'apellido_paterno' => $validated['apellido_paterno'],
            'apellido_materno' => $validated['apellido_materno'],
            'ci'               => $validated['ci'],
            'direccion'        => $validated['direccion'],
            'celular'          => $validated['celular'], 
            'email'            => $validated['email'],
            'password'         => Hash::make($validated['password']),
            'role_id'          => $roleId,
            'face_photo'       => null, 
        ]);

        return response()->json([
            'message' => 'Usuario creado exitosamente en S.D.B.',
            'user'    => $user->load('role')
        ], 201);
    }

    /**
     * Actualizar un usuario existente.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'nombres'          => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'required|string|max:100',
            // 🔥 Ignora al usuario actual pero busca duplicados solo en activos
            'ci'               => [
                'required', 
                'string', 
                Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')
            ],
            'direccion'        => 'nullable|string|max:255',
            'celular'          => 'nullable|numeric|digits_between:7,10', 
            'email'            => [
                'required', 
                'email', 
                Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')
            ],
            'role_id'          => 'required',
            'password'         => 'nullable|string|min:8',
        ]);

        $roleId = $this->resolveRoleId($validated['role_id']);

        $user->nombres = $validated['nombres'];
        $user->apellido_paterno = $validated['apellido_paterno'];
        $user->apellido_materno = $validated['apellido_materno'];
        $user->ci = $validated['ci'];
        $user->direccion = $validated['direccion'];
        $user->celular = $validated['celular']; 
        $user->email = $validated['email'];
        $user->role_id = $roleId;

        $user->name = trim($validated['nombres'] . ' ' . $validated['apellido_paterno'] . ' ' . $validated['apellido_materno']);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'user'    => $user->load('role')
        ]);
    }

    /**
     * Eliminar un usuario (Lógicamente).
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        if (auth()->id() == $user->id) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta.'], 403);
        }

        // 🔥 Al tener SoftDeletes en el modelo, esto ya no hace un DELETE físico.
        // Solo guarda la fecha actual en la columna 'deleted_at'.
        $user->delete();

        return response()->json(['message' => 'Usuario dado de baja lógicamente en el sistema S.D.B.']);
    }

    /**
     * 🔥 NUEVO: Obtener lista de usuarios dados de baja (Papelera de Reciclaje).
     */
    public function getTrashed()
    {
        // Trae única y exclusivamente los registros que tengan el campo 'deleted_at' lleno
        $trashedUsers = User::onlyTrashed()->with('role')->get();
        return response()->json($trashedUsers);
    }

    /**
     * 🔥 NUEVO: Reactivar un usuario eliminado lógicamente.
     */
    public function restore($id)
    {
        // Buscamos el registro dentro del subconjunto de eliminados lógicos
        $user = User::onlyTrashed()->findOrFail($id);
        
        // Limpia el campo 'deleted_at' restableciéndolo a NULL
        $user->restore(); 

        return response()->json([
            'message' => "La cuenta de {$user->nombres} ha sido reactivada con éxito en S.D.B."
        ]);
    }

    private function resolveRoleId($roleInput)
    {
        if (is_numeric($roleInput)) {
            return Role::where('id', $roleInput)->exists() ? $roleInput : null;
        }

        $role = Role::where('nombre', $roleInput)->first();
        return $role ? $role->id : null;
    }
}