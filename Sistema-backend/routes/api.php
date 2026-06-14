<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BacheController;
use App\Http\Controllers\RoleController; 
use App\Http\Controllers\ObreroController; 
use App\Http\Controllers\CuadrillaController; 
use App\Http\Controllers\VehiculoController; 
use App\Http\Controllers\SensorController; 
use App\Http\Controllers\MaterialController; 
use App\Models\Permission; 
use App\Http\Controllers\PersonalTallerController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\CuadrillaMaterialController;

/*
|--------------------------------------------------------------------------
| API Routes - Sistema S.D.B.
|--------------------------------------------------------------------------
*/

// --- NIVEL 1: RUTAS PÚBLICAS (Sin token) ---
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/verify-face', [AuthController::class, 'verifyFace']);

// Ruta pública para la recepción de telemetría de los sensores IoT viales
Route::post('/telemetria/detectar', [App\Http\Controllers\TelemetriaController::class, 'procesarImpacto']);


// --- NIVEL 2: RUTAS PARA USUARIOS AUTENTICADOS (Sanctum) ---
Route::middleware('auth:sanctum')->group(function () {

    // Sesión y Perfil
    Route::get('/user', function (Request $request) {
        return $request->user()->load('role.permissions');
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- Gestión de Baches (Operativa) ---
    Route::get('/baches', [BacheController::class, 'index']);
    
    // El estado del bache puede cambiar por Administrador, Técnico o el Jefe en la calle
    Route::put('/baches/{id}/estado', [BacheController::class, 'actualizarEstado'])
         ->middleware('rol:Administrador,Técnico,Jefe de Cuadrilla');
    
    // 🔥 CONTROL DE OPERACIONES: Solo el Administrador y el Técnico asignan cuadrillas a los baches
    Route::put('/baches/{id}/asignar-cuadrilla', [BacheController::class, 'asignarCuadrilla'])
         ->middleware('rol:Administrador,Técnico'); 
    
    Route::get('/stats-baches', [BacheController::class, 'getEstadisticas']);
    Route::post('/baches', [BacheController::class, 'store']);

    // --- Módulo de Reportes de Inteligencia Vial ---
    Route::get('/reportes-datos', [\App\Http\Controllers\ReportController::class, 'obtenerDatosReporte']);

    // 🌐 ¡PEGA ESTA LÍNEA AQUÍ! (Permite el acceso al Administrador y al Técnico):
    Route::get('/dashboard-stats', [\App\Http\Controllers\DashboardController::class, 'getStats'])
         ->middleware('rol:Administrador,Técnico,Encargado de almacén,Analista Vial');


    // --- 👥 GESTIÓN DE OBREROS (Control de Personal de Campo) ---
    // 🔥 REFACTORIZADO: El Técnico ahora gestiona el CRUD completo y la restauración de bajas lógicas
    Route::get('/obreros/trashed', [ObreroController::class, 'getTrashed'])->middleware('rol:Administrador,Técnico');
    Route::post('/obreros/{id}/restore', [ObreroController::class, 'restore'])->middleware('rol:Administrador,Técnico');

    Route::get('/obreros', [ObreroController::class, 'index'])->middleware('rol:Administrador,Técnico,Jefe de Cuadrilla');
    Route::post('/obreros', [ObreroController::class, 'store'])->middleware('rol:Administrador,Técnico');
    Route::get('/obreros/{id}', [ObreroController::class, 'show'])->middleware('rol:Administrador,Técnico'); 
    Route::put('/obreros/{id}', [ObreroController::class, 'update'])->middleware('rol:Administrador,Técnico'); 
    Route::delete('/obreros/{id}', [ObreroController::class, 'destroy'])->middleware('rol:Administrador,Técnico'); 
    Route::get('/cuadrillas/sugerencia', [ObreroController::class, 'sugerirCuadrilla'])->middleware('rol:Administrador,Técnico');


    // --- 👥 GESTIÓN DE CUADRILLAS OPERATIVAS ---
    // 🔥 REFACTORIZADO: El Técnico tiene plenos poderes logísticos sobre la organización de equipos
    Route::get('/cuadrillas/trashed', [CuadrillaController::class, 'getTrashed'])->middleware('rol:Administrador,Técnico');
    Route::post('/cuadrillas/{id}/restore', [CuadrillaController::class, 'restore'])->middleware('rol:Administrador,Técnico');

    // Nota: Dejamos leer al Encargado de almacén para que asigne material en su dropdown diario
    Route::get('/cuadrillas', [CuadrillaController::class, 'index'])->middleware('rol:Administrador,Técnico,Encargado de almacén');
    Route::post('/cuadrillas', [CuadrillaController::class, 'store'])->middleware('rol:Administrador,Técnico');
    Route::get('/cuadrillas/{id}', [CuadrillaController::class, 'show'])->middleware('rol:Administrador,Técnico');
    Route::put('/cuadrillas/{id}', [CuadrillaController::class, 'update'])->middleware('rol:Administrador,Técnico');
    Route::get('/cuadrillas/{id}/exportar-pdf', [CuadrillaController::class, 'exportarPersonalPdf'])->middleware('rol:Administrador,Técnico');
    
    // El Técnico de Flota e IoT también se incluye aquí para poder ver personal y usuarios si su vista lo requiere
    Route::get('/users', [UserController::class, 'index'])->middleware('rol:Administrador,Técnico,Técnico de Flota e IoT');


    // --- 📡 GESTIÓN DE SENSORES (Inventario IoT) ---
    // 🔥 ACTUALIZADO: Control completo de hardware autorizado para el Técnico de Flota e IoT
    Route::get('/sensores', [SensorController::class, 'index'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::post('/sensores', [SensorController::class, 'store'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::get('/sensores/{id}', [SensorController::class, 'show'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::get('/sensores-disponibles', [SensorController::class, 'getDisponibles'])->middleware('rol:Administrador,Técnico de Flota e IoT,Encargado de almacén');
    Route::put('/sensores/{id}', [SensorController::class, 'update'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::delete('/sensores/{id}', [SensorController::class, 'destroy'])->middleware('rol:Administrador,Técnico de Flota e IoT');


    // --- 🚗 GESTIÓN DE VEHÍCULOS MUNICIPALES ---
    // 🔥 ACTUALIZADO: Acceso total de control automotriz para el Técnico de Flota e IoT
    Route::get('/vehiculos', [VehiculoController::class, 'index'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::post('/vehiculos', [VehiculoController::class, 'store'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::get('/vehiculos/{id}', [VehiculoController::class, 'show'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::put('/vehiculos/{id}', [VehiculoController::class, 'update'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::delete('/vehiculos/{id}', [VehiculoController::class, 'destroy'])->middleware('rol:Administrador,Técnico de Flota e IoT');

    Route::get('/vehiculos/{id}/baches', [VehiculoController::class, 'getBachesAsociados'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::get('/vehiculos/{id}/exportar-pdf', [VehiculoController::class, 'exportarBachesPdf'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::get('/vehiculos-exportar-general-pdf', [VehiculoController::class, 'exportarTodosPdf'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::get('/vehiculos-exportar-general-excel', [VehiculoController::class, 'exportarTodosExcel'])->middleware('rol:Administrador,Técnico de Flota e IoT');


     Route::get('/materiales', [MaterialController::class, 'index'])
          ->middleware('rol:Administrador,Encargado de almacén,Técnico');

     // Acciones de escritura exclusivas para el Administrador y el Encargado de almacén
     Route::put('/materiales/{id}', [MaterialController::class, 'update'])
          ->middleware('rol:Administrador,Encargado de almacén');

     Route::post('/materiales', [MaterialController::class, 'store'])
          ->middleware('rol:Administrador,Encargado de almacén');

     Route::delete('/materiales/{id}', [MaterialController::class, 'destroy'])
          ->middleware('rol:Administrador,Encargado de almacén');


    // --- 📈 ANALÍTICA AVANZADA DE TELEMETRÍA VIAL Y SALUD MECÁNICA ---
    // 🔥 ACTUALIZADO: Permite el monitoreo telemático al Técnico de Flota e IoT
    Route::get('/vehiculos-salud-flota', [\App\Http\Controllers\VehiculoController::class, 'getSaludFlota'])
         ->middleware('rol:Administrador,Técnico de Flota e IoT');


    // --- 🔥 HISTORIAL Y AUDITORÍA VIAL DE REPARACIONES ---
    Route::get('/baches-historial', [\App\Http\Controllers\BacheController::class, 'getHistorialReparaciones'])
         ->middleware('rol:Administrador,Técnico,Analista Vial,Jefe de Cuadrilla');
    Route::get('/baches-historial-pdf', [\App\Http\Controllers\BacheController::class, 'exportarHistorialPdf'])
         ->middleware('rol:Administrador,Técnico,Analista Vial,Jefe de Cuadrilla');
    Route::get('/baches-historial-excel', [\App\Http\Controllers\BacheController::class, 'exportarHistorialExcel'])
         ->middleware('rol:Administrador,Técnico,Analista Vial,Jefe de Cuadrilla');


    // --- Módulo de Reportes de Inteligencia Vial ---
    Route::get('/reportes-datos', [\App\Http\Controllers\ReportController::class, 'obtenerDatosReporte']);


    // --- 🔧 RUTAS DEL PERSONAL DE TALLER Y MANTENIMIENTOS ---
    // 🔥 ACTUALIZADO: Control total del taller clínico automotriz asignado al Técnico de Flota e IoT
    Route::get('/personal-taller', [PersonalTallerController::class, 'index'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::post('/personal-taller', [PersonalTallerController::class, 'store'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::put('/personal-taller/{id}', [PersonalTallerController::class, 'update'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::delete('/personal-taller/{id}', [PersonalTallerController::class, 'destroy'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    
    Route::apiResource('mantenimientos', MantenimientoController::class)->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::put('mantenimientos/{id}/finalizar', [MantenimientoController::class, 'finalizar'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::get('vehiculos/{id}/historial-mantenimientos', [VehiculoController::class, 'getHistorialMantenimientos'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    Route::get('vehiculos/{id}/exportar-historial-pdf', [VehiculoController::class, 'exportarHistorialPdf'])->middleware('rol:Administrador,Técnico de Flota e IoT');
    
    
    // --- CONTROL LOGÍSTICO DIARIO (Fases 1, 2, 3 y Monitoreo) ---
    Route::post('cuadrillas-material/despachar', [CuadrillaMaterialController::class, 'despacharMaterial'])
         ->middleware('rol:Administrador,Encargado de almacén');
         
    Route::post('cuadrillas-material/restar-transito', [CuadrillaMaterialController::class, 'restarMaterialTransito'])
         ->middleware('rol:Administrador,Técnico,Jefe de Cuadrilla'); 
         
    Route::post('cuadrillas-material/cerrar-jornada', [CuadrillaMaterialController::class, 'cerrarJornada'])
         ->middleware('rol:Administrador,Encargado de almacén');
         
    Route::get('cuadrillas-material/activos', [CuadrillaMaterialController::class, 'obtenerDespachosActivos'])
         ->middleware('rol:Administrador,Encargado de almacén,Técnico,Jefe de Cuadrilla');
         
    Route::get('cuadrillas/{id}/materiales-activos', [BacheController::class, 'obtenerMaterialesActivosCuadrilla'])
         ->middleware('rol:Administrador,Técnico,Jefe de Cuadrilla');
});


// --- NIVEL 3: RUTAS EXCLUSIVAS DE ADMINISTRADOR GLOBAL (Sanctum + Admin) ---
Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    // Gestión de Usuarios de Oficina
    Route::get('/users/trashed', [UserController::class, 'getTrashed']);
    Route::post('/users/{id}/restore', [UserController::class, 'restore']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::get('/roles', [UserController::class, 'getRoles']);

    // Gestión de Roles y Permisos Estrictos del Framework
    Route::get('/permissions', function() {
        return response()->json(Permission::all());
    });
    Route::apiResource('roles-permissions', RoleController::class);

    // Reportes de Sistema y Configuración Global de Microservicios
    Route::get('/reporte-pdf', [BacheController::class, 'descargarReporte']);
    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
    //Route::get('/dashboard-stats', [\App\Http\Controllers\DashboardController::class, 'getStats']);
});