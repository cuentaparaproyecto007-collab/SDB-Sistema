<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use App\Models\AuditoriaAcceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SensorController extends Controller
{
    /**
     * Listar todos los sensores registrados
     */
    public function index()
    {
        $sensores = Sensor::with('vehiculo')->get();
        return response()->json($sensores);
    }

    /**
     * Listar únicamente los sensores libres para el selector de Vue.js
     */
    public function getDisponibles()
    {
        $disponibles = Sensor::where('estado', 'Disponible')->get();
        return response()->json($disponibles);
    }

    /**
     * Registrar un nuevo sensor de hardware
     */
    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|max:30|unique:sensores,codigo',
            'modelo' => 'required|string|max:50',
            'estado' => 'required|in:Disponible,Instalado,Mantenimiento'
        ]);

        $sensor = Sensor::create($request->all());

        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Registró un nuevo dispositivo IoT: Código {$sensor->codigo} (Modelo: {$sensor->modelo})",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => 'Sensor registrado exitosamente en el inventario',
            'data' => $sensor
        ], 201);
    }

    /**
     * Ver detalles específicos de un sensor
     */
    public function show($id)
    {
        $sensor = Sensor::with('vehiculo')->findOrFail($id);
        return response()->json($sensor);
    }

    /**
     * Modificar datos del sensor
     */
    public function update(Request $request, $id)
    {
        $sensor = Sensor::findOrFail($id);

        $request->validate([
            'codigo' => ['required', 'string', 'max:30', Rule::unique('sensores', 'codigo')->ignore($id)],
            'modelo' => 'required|string|max:50',
            'estado' => 'required|in:Disponible,Instalado,Mantenimiento'
        ]);

        $sensor->update($request->all());

        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Actualizó el dispositivo hardware ID {$id} a Código: {$request->codigo}",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => 'Datos del dispositivo actualizados',
            'data' => $sensor
        ]);
    }

    /**
     * Eliminar físicamente un sensor si no está acoplado
     */
    public function destroy(Request $request, $id)
    {
        $sensor = Sensor::findOrFail($id);

        if ($sensor->estado === 'Instalado') {
            return response()->json([
                'res' => false,
                'message' => 'No se puede eliminar un hardware que está actualmente montado en un vehículo.'
            ], 400);
        }

        $codigoEliminado = $sensor->codigo;
        $sensor->delete();

        AuditoriaAcceso::create([
            'user_id'          => Auth::id(),
            'accion'           => "Retiró del inventario el sensor Código: {$codigoEliminado} (ID: {$id})",
            'ip_origen'        => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        return response()->json([
            'res' => true,
            'message' => 'Dispositivo retirado del inventario de hardware correctamente.'
        ]);
    }
}
