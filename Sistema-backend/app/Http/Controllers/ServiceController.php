<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Http\Requests\ServiceRequest;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    // GET /services - Listado de activos (Laravel ignora los eliminados automáticamente)
    public function index()
    {
        $services = Service::where('user_id', auth()->id())->get();
        
        return response()->json([
            'status' => 'Success',
            'data' => $services
        ], 200);
    }

    // POST /services - Registro
    public function store(ServiceRequest $request)
    {
        $service = Service::create([
            'user_id' => auth()->id(),
            'foto_persona' => $request->foto_persona,
        ]);

        return response()->json([
            'status' => 'Created',
            'message' => 'Servicio registrado correctamente',
            'data' => $service
        ], 201);
    }

    // PUT /services/{id} - Actualización
    public function update(ServiceRequest $request, $id)
    {
        $service = Service::where('user_id', auth()->id())->findOrFail($id);
        
        $service->update([
            'foto_persona' => $request->foto_persona
        ]);

        return response()->json([
            'status' => 'Updated',
            'message' => 'Servicio actualizado',
            'data' => $service
        ], 200);
    }

    // DELETE /services/{id} - Soft Delete
    public function destroy($id)
    {
        $service = Service::where('user_id', auth()->id())->findOrFail($id);
        
        $service->delete(); // Gracias al trait SoftDeletes en el Modelo, esto solo llena 'deleted_at'

        return response()->json([
            'status' => 'Deleted',
            'message' => 'Servicio enviado a la papelera (Eliminación lógica)'
        ], 200);
    }
}