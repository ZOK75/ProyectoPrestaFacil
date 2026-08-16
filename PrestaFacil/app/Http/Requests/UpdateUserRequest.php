<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['nullable', 'string', 'confirmed', Password::min(12)],
            'rol_id' => ['required', 'exists:roles,id'],
            'sucursal_id' => ['required', 'exists:sucursales,id'],
            'categoria_distribuidor' => ['nullable', 'in:cobre,plata,oro'],
            'limite_credito' => ['nullable', 'numeric', 'min:0'],
            'referencia_pago_distribuidor' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del usuario es obligatorio.',
            'name.min' => 'El nombre del usuario debe tener al menos 3 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Este correo ya lo usa otro usuario.',
            'password.min' => 'La nueva contraseña debe tener un mínimo de 12 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'rol_id.required' => 'Debes seleccionar un rol.',
            'rol_id.exists' => 'El rol seleccionado no es válido.',
            'sucursal_id.required' => 'Debes seleccionar una sucursal.',
            'sucursal_id.exists' => 'La sucursal seleccionada no es válida.',
        ];
    }
}
