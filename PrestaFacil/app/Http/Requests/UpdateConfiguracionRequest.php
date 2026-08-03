<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'fecha_corte' => ['required', 'date'],
            'fecha_limite_pago' => ['required', 'date', 'after_or_equal:fecha_corte'],
            'multa_adeudo' => ['required', 'numeric', 'min:0'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Mensajes de validación personalizados.
     */
    public function messages(): array
    {
        return [
            'fecha_corte.required' => 'La fecha y hora de corte es obligatoria.',
            'fecha_limite_pago.required' => 'La fecha y hora límite de pago es obligatoria.',
            'fecha_limite_pago.after_or_equal' => 'La fecha límite de pago debe ser igual o posterior a la fecha de corte.',
            'multa_adeudo.required' => 'La multa por adeudo es obligatoria.',
            'multa_adeudo.min' => 'La multa por adeudo no puede ser negativa.',
        ];
    }
}