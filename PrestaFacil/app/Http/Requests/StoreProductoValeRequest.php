<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoValeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clave' => 'required|string|max:50|unique:producto_vales,clave',
            'nombre' => 'required|string|max:255',
            'monto_prestamo' => 'required|numeric|min:100|max:1000000',
            'costo_seguro' => 'required|numeric|min:0|max:100000',
            'plazo_quincenas' => 'required|integer|min:1|max:120',
            'tasa_interes_quincenal' => 'required|numeric|min:0|max:100',
            'activo' => 'nullable|boolean',
            'descripcion' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'clave.required' => 'La clave del producto es obligatoria.',
            'clave.unique' => 'Esta clave de producto ya se encuentra registrada.',
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'monto_prestamo.required' => 'El monto del préstamo es obligatorio.',
            'monto_prestamo.min' => 'El monto del préstamo debe ser de al menos $100.00.',
            'costo_seguro.required' => 'El costo del seguro es obligatorio (puede ser 0).',
            'plazo_quincenas.required' => 'El plazo en quincenas es obligatorio.',
            'plazo_quincenas.min' => 'El plazo debe ser de al menos 1 quincena.',
            'tasa_interes_quincenal.required' => 'La tasa de interés quincenal es obligatoria.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'activo' => $this->has('activo') ? true : false,
        ]);
    }
}
