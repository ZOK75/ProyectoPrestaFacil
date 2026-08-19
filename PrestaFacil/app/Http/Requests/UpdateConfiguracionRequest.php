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
            'dia_corte' => ['required', 'integer', 'min:1', 'max:31'],
            'hora_corte' => ['required', 'string'],
            'dia_limite_pago' => ['required', 'integer', 'min:1', 'max:31'],
            'hora_limite_pago' => ['required', 'string'],
            'multa_adeudo' => ['nullable', 'numeric', 'min:0'],
            'comision_cobre' => ['required', 'numeric', 'min:0', 'max:100'],
            'comision_plata' => ['required', 'numeric', 'min:0', 'max:100'],
            'comision_oro' => ['required', 'numeric', 'min:0', 'max:100'],
            'monto_base_puntos' => ['required', 'numeric', 'min:1'],
            'puntos_por_monto_base' => ['required', 'integer', 'min:1'],
            'valor_punto' => ['required', 'numeric', 'min:0'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Mensajes de validación personalizados.
     */
    public function messages(): array
    {
        return [
            'dia_corte.required' => 'El número de día de corte (1-31) es obligatorio.',
            'dia_corte.min' => 'El día de corte debe ser mínimo 1.',
            'dia_corte.max' => 'El día de corte no puede superar el día 31.',
            'hora_corte.required' => 'La hora de corte es obligatoria.',
            'dia_limite_pago.required' => 'El número de día límite de pago (1-31) es obligatorio.',
            'dia_limite_pago.min' => 'El día límite de pago debe ser mínimo 1.',
            'dia_limite_pago.max' => 'El día límite de pago no puede superar el día 31.',
            'hora_limite_pago.required' => 'La hora límite de pago es obligatoria.',
            'multa_adeudo.required' => 'La multa por adeudo es obligatoria.',
            'multa_adeudo.min' => 'La multa por adeudo no puede ser negativa.',
            'comision_cobre.required' => 'El porcentaje de comisión Cobre es obligatorio.',
            'comision_plata.required' => 'El porcentaje de comisión Plata es obligatorio.',
            'comision_oro.required' => 'El porcentaje de comisión Oro es obligatorio.',
            'monto_base_puntos.required' => 'El monto base en productos para generar puntos es obligatorio.',
            'puntos_por_monto_base.required' => 'La cantidad de puntos generados por bloque es obligatoria.',
            'valor_punto.required' => 'El valor monetario de cada punto es obligatorio.',
        ];
    }
}