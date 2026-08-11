<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto_abonado' => ['required', 'numeric', 'min:1'],
            'monto_multa' => ['nullable', 'numeric', 'min:0'],
            'metodo_pago' => ['required', 'string'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto_abonado.required' => 'El monto del abono es obligatorio.',
            'monto_abonado.min' => 'El abono debe ser mayor a 0.',
            'metodo_pago.required' => 'Selecciona un método de pago.',
        ];
    }
}
