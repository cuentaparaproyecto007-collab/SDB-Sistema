<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Permitimos que cualquier usuario autenticado lo use
    }

    public function rules(): array
    {
        return [
            'foto_persona' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // Validamos que el string tenga el formato de imagen Base64
                    if (!preg_match('/^data:image\/(jpeg|png|jpg);base64,/', $value)) {
                        $fail('El campo ' . $attribute . ' debe ser una imagen válida en formato Base64 (jpeg, png, jpg).');
                    }
                    
                    // Opcional: Validar el tamaño real del contenido decodificado
                    $base64String = explode(',', $value)[1];
                    $decodedData = base64_decode($base64String);
                    $sizeInKb = strlen($decodedData) / 1024;

                    if ($sizeInKb > 2048) { // Límite de 2MB
                        $fail('La imagen es demasiado pesada (máximo 2MB).');
                    }
                },
            ],
        ];
    }
}