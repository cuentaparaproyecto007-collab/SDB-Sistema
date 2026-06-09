<?php

namespace App\Http\Controllers;

use App\Models\PersonalTaller;
use Illuminate\Http\Request;

class PersonalTallerController extends Controller
{
    /**
     * 1. 👁️ VER / LISTAR: Trae a todo el personal activo (no eliminados)
     */
    public function index()
    {
        try {
            // Trae los registros del taller ordenados desde el más reciente
            $personal = PersonalTaller::orderBy('id', 'desc')->get();
            return response()->json($personal, 200);
        } catch (\Exception $e) {
            return response()->json(['res' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 📥 2. GUARDAR: Recibe el formulario de Vue y crea el registro
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombres'          => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:75',
            'apellido_materno' => 'nullable|string|max:75',
            'ci'               => 'required|string|max:25|unique:personal_taller,ci',
            'genero'           => 'required|in:Masculino,Femenino,Otro',
            'celular'          => 'required|string|max:20',
            'direccion'        => 'required|string|max:255',
            'rubro'            => 'required|in:Mecánica,Electrónica',
        ]);

        try {
            PersonalTaller::create([
                'nombres'          => $request->nombres,
                'apellido_paterno' => $request->apellido_paterno,
                'apellido_materno' => $request->apellido_materno,
                'ci'               => $request->ci,
                'genero'           => $request->genero,
                'celular'          => $request->celular,
                'direccion'        => $request->direccion,
                'rubro'            => $request->rubro,
                'estado'           => 'Disponible',
            ]);

            return response()->json(['res' => true, 'message' => 'Técnico registrado exitosamente.'], 201);
        } catch (\Exception $e) {
            return response()->json(['res' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 📝 3. EDITAR / ACTUALIZAR: Modifica los datos de un técnico existente
     */
    public function update(Request $request, $id)
    {
        // Validamos, pero ignoramos el CI del propio usuario para que permita guardar sin error de duplicado
        $request->validate([
            'nombres'          => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:75',
            'apellido_materno' => 'nullable|string|max:75',
            'ci'               => 'required|string|max:25|unique:personal_taller,ci,' . $id,
            'genero'           => 'required|in:Masculino,Femenino,Otro',
            'celular'          => 'required|string|max:20',
            'direccion'        => 'required|string|max:255',
            'rubro'            => 'required|in:Mecánica,Electrónica',
            'estado'           => 'required|string|max:30',
        ]);

        try {
            $tecnico = PersonalTaller::findOrFail($id);
            $tecnico->update($request->all());

            return response()->json(['res' => true, 'message' => 'Datos actualizados correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['res' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 🗑️ 4. ELIMINAR (Borrado Lógico): Lo envía a la papelera de reciclaje
     */
    public function destroy($id)
    {
        try {
            $tecnico = PersonalTaller::findOrFail($id);
            // Laravel automáticamente llenará 'deleted_at' gracias al SoftDeletes del modelo
            $tecnico->delete(); 

            return response()->json(['res' => true, 'message' => 'Técnico enviado a la papelera.'], 200);
        } catch (\Exception $e) {
            return response()->json(['res' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
