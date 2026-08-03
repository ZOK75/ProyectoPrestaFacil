<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('usuario')?->id ?? $this->route('usuario');

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => 'nullable|string|min:6|confirmed',
            'rol_id' => 'required|exists:roles,id',
            'sucursal_id' => 'required|exists:sucursales,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del usuario es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Este correo ya lo usa otro usuario.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'rol_id.required' => 'Debes seleccionar un rol.',
            'rol_id.exists' => 'El rol seleccionado no es válido.',
            'sucursal_id.required' => 'Debes seleccionar una sucursal.',
            'sucursal_id.exists' => 'La sucursal seleccionada no es válida.',
        ];
    }
}
