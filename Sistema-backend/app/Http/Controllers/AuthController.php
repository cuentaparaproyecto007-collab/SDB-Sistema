<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TrustedDevice;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
// 1. IMPORTANTE: Agregamos la fachada para enviar correos
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * PASO 1: Credenciales y Validación de Riesgo
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_id' => 'required|string' 
        ]);

        // AJUSTE: Cargamos la relación 'role' de una vez
        $user = User::where('email', $request->email)->with('role')->first();

        // Verificar credenciales
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        // Verificar si el equipo es de confianza
        $isTrusted = TrustedDevice::where('user_id', $user->id)
            ->where('device_id', $request->device_id)
            ->exists();

        if ($isTrusted) {
            $token = $user->createToken('auth_token')->plainTextToken;
            
            // AJUSTE: Devolvemos los datos del usuario y su ROL
            return response()->json([
                'message' => 'Acceso concedido (Dispositivo de confianza)',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->nombre // Ejemplo: "Administrador"
                ]
            ]);
        }

        // Si NO es de confianza, generar OTP aleatorio
        $otp = rand(100000, 999999);
        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => \Carbon\Carbon::now()->addMinutes(15)
        ]);

        // 2. ENVIAR CORREO A MAILTRAP
        \Mail::raw("Tu código de verificación S.D.B. es: $otp", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Código de Seguridad - Sistema de Gestión Vial');
        });

        // 3. RESPUESTA AL FRONTEND
        return response()->json([
            'message' => 'Se requiere verificación de dos factores (2FA)',
            'requires_2fa' => true,
            'otp_debug' => $otp 
        ], 200);
    }

    /**
     * PASO 2: Verificación de OTP y Registro de Dispositivo
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric',
            'device_id' => 'required|string',
            'remember_device' => 'boolean'
        ]);

        // AJUSTE: Cargamos la relación 'role' para que el frontend sepa quién entró
        $user = User::where('email', $request->email)
            ->where('otp_code', $request->otp)
            ->where('otp_expires_at', '>', \Carbon\Carbon::now())
            ->with('role') // Eager loading del rol
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Código OTP inválido o expirado'], 422);
        }

        // Lógica de dispositivo de confianza que ya tenías
        if ($request->remember_device) {
            TrustedDevice::updateOrCreate([
                'user_id' => $user->id,
                'device_id' => $request->device_id
            ]);
        }

        // Limpieza de seguridad
        $user->update(['otp_code' => null, 'otp_expires_at' => null]);

        // Generación de token de Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // AJUSTE: Devolvemos el objeto 'user' con su rol oficial
        return response()->json([
            'message' => 'Identidad verificada con éxito',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->nombre // Ejemplo: "Administrador" o "Técnico"
            ]
        ]);
    }

    //paso 3 Face-api

   public function verifyFace(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'face_image' => 'required' // El string Base64 que viene de Vue
        ]);

        // 🔥 AJUSTE CLAVE 1: Cargamos 'role.permissions' (la relación anidada)
        $user = User::where('email', $request->email)->with('role.permissions')->first();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // 1. LÓGICA DE REGISTRO INICIAL
        if (!$user->face_photo) {
            $user->update([
                'face_photo' => $request->face_image
            ]);
            
            $token = $user->createToken('auth_token')->plainTextToken;
            
            return response()->json([
                'message' => 'Rostro registrado con éxito. Acceso concedido.',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    // 🔥 AJUSTE CLAVE 2: Enviamos el objeto role completo (incluye los permisos)
                    'role' => $user->role 
                ]
            ], 200);
        }

        // 2. LÓGICA DE COMPARACIÓN (Validada por Face-API en el Frontend)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Identidad biométrica confirmada',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                // 🔥 AJUSTE CLAVE 3: Enviamos el objeto role completo
                'role' => $user->role 
            ]
        ], 200);
    }

    // Dentro de AuthController.php
    public function logout(Request $request)
    {
        // 1. Registro de Auditoría (Crucial para el historial de seguridad)
        \App\Models\AuditoriaAcceso::create([
            'user_id' => $request->user()->id,
            'accion' => "Cierre de sesión manual",
            'ip_origen' => $request->ip(),
            'dispositivo_info' => $request->userAgent()
        ]);

        // 2. Revoca el token que el usuario está usando actualmente
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'res' => true,
            'message' => 'Token eliminado y sesión cerrada correctamente'
        ], 200);
    }
}