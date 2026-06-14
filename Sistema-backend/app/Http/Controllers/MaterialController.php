<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\AuditoriaAcceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaterialController extends Controller
{
   public function index()
    {
        // 💡 Corregido: Envolvemos el resultado en la estructura oficial del proyecto
        return response()->json([
            'res'  => true,
            'data' => Material::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100|unique:materiales,nombre',
            'stock_actual' => 'required|numeric|min:0'
        ]);

        $material = Material::create([
            'nombre' => $request->nombre,
            'stock_actual' => $request->stock_actual
        ]);

        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Registró un nuevo material: '{$request->nombre}' con stock de {$request->stock_actual} m³",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json(['res' => true, 'data' => $material], 201);
    }

    /**
     * 🔥 MODIFICADO: Permite editar Nombre y Stock a la vez
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre'       => 'required|string|max:100|unique:materiales,nombre,' . $id,
            'stock_actual' => 'required|numeric|min:0'
        ]);

        $material = Material::findOrFail($id);
        $stockAnterior = $material->stock_actual;
        $nombreAnterior = $material->nombre;
        
        $material->update([
            'nombre'       => $request->nombre,
            'stock_actual' => $request->stock_actual
        ]);

        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Modificó material ID {$id}. Nombre: '{$nombreAnterior}' -> '{$request->nombre}', Stock: {$stockAnterior} m³ -> {$request->stock_actual} m³",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json(['res' => true, 'data' => $material]);
    }

    public function destroy($id)
    {
        $material = Material::findOrFail($id);
        $enUso = \DB::table('baches')->where('material_id', $id)->exists();

        if ($enUso) {
            return response()->json([
                'res' => false,
                'message' => "❌ No se puede eliminar '{$material->nombre}'. Este insumo ya cuenta con registros históricos en baches reparados por la alcaldía."
            ], 422);
        }

        $material->delete();

        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Eliminó del sistema el material sin uso: '{$material->nombre}'",
            'ip_origen'        => request()->ip(),
            'dispositivo_info' => request()->userAgent()
        ]);

        return response()->json(['res' => true, 'message' => 'Material removido con éxito']);
    }
}
