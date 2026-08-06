<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrestamoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'exists:clientes,id'],
            'producto_vale_id' => ['required', 'exists:producto_vales,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_id.required' => 'Debes seleccionar un cliente.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'producto_vale_id.required' => 'Debes seleccionar un vale activo.',
            'producto_vale_id.exists' => 'El vale seleccionado no es válido.',
        ];
    }
}
