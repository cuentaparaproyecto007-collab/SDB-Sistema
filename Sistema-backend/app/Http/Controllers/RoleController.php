<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        // Traemos roles con sus permisos cargados
        $roles = Role::with('permissions')->get();
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id'          => 'nullable|integer', // Importante para el updateOrCreate
            'nombre'      => 'required|string',
            'descripcion' => 'required|string', // Cambiado a required como pediste
            'permisos'    => 'array' 
        ]);

        $role = Role::updateOrCreate(
            ['id' => $request->id],
            [
                'nombre'      => $validated['nombre'],
                'descripcion' => $validated['descripcion']
            ]
        );

        // Sincronizamos: si no vienen permisos, mandamos array vacío para limpiar
        $role->permissions()->sync($request->permisos ?? []);

        return response()->json([
            'message' => 'Rol guardado exitosamente',
            'role' => $role->load('permissions')
        ]);
    }

    public function destroy($id)
    {
        // 🛡️ ESCUDO DE SEGURIDAD S.D.B.
        if ($id == 1) {
            return response()->json(['message' => 'Prohibido eliminar el rol Administrador del sistema'], 403);
        }

        Role::destroy($id);
        return response()->json(['message' => 'Rol eliminado correctamente']);
    }
}