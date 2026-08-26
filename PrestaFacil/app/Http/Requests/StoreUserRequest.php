<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $globalRolesIds = \App\Models\Rol::whereIn('nombre', ['Gerente General', 'gerente general', 'Administrador', 'administrador'])->pluck('id')->filter()->implode(',');

        return [
            'name' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z\sñÑáéíóúÁÉÍÓÚ]+$/'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(12)],
            'rol_id' => ['required', 'exists:roles,id'],
            'sucursal_id' => ['nullable', $globalRolesIds ? 'required_unless:rol_id,' . $globalRolesIds : 'nullable', 'exists:sucursales,id'],
            'categoria_distribuidor' => ['nullable', 'in:cobre,plata,oro'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del usuario es obligatorio.',
            'name.min' => 'El nombre del usuario debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre del usuario no debe superar los 50 caracteres.',
            'name.regex' => 'El nombre del usuario solo puede contener letras y espacios.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Este correo electrónico ya está registrado en el sistema.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener un mínimo de 12 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'rol_id.required' => 'Debes seleccionar un rol para el usuario.',
            'rol_id.exists' => 'El rol seleccionado no es válido.',
            'sucursal_id.required' => 'Debes seleccionar una sucursal.',
            'sucursal_id.exists' => 'La sucursal seleccionada no es válida.',
        ];
    }
}
