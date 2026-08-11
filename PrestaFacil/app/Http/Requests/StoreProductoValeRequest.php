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
            'clave' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-_]+$/', 'unique:producto_vales,clave'],
            'nombre' => ['required', 'string', 'min:3', 'max:255'],
            'monto_prestamo' => ['required', 'numeric', 'min:100', 'max:1000000'],
            'costo_seguro' => ['required', 'numeric', 'min:0', 'max:100000'],
            'comision_apertura' => ['required', 'numeric', 'min:0', 'max:100'],
            'plazo_quincenas' => ['required', 'integer', 'min:1', 'max:120'],
            'tasa_interes_quincenal' => ['required', 'numeric', 'min:0', 'max:100'],
            'activo' => ['nullable', 'boolean'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'clave.required' => 'La clave del producto es obligatoria.',
            'clave.string' => 'La clave del producto debe ser un texto válido.',
            'clave.max' => 'La clave del producto no debe exceder los 50 caracteres.',
            'clave.regex' => 'La clave solo puede contener letras, números, guiones (-) y guiones bajos (_).',
            'clave.unique' => 'Esta clave de vale ya se encuentra registrada en el sistema.',

            'nombre.required' => 'El nombre comercial del vale es obligatorio.',
            'nombre.string' => 'El nombre comercial debe ser un texto válido.',
            'nombre.min' => 'El nombre comercial debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre comercial no debe exceder los 255 caracteres.',

            'monto_prestamo.required' => 'El monto del préstamo es obligatorio.',
            'monto_prestamo.numeric' => 'El monto del préstamo debe ser un número válido.',
            'monto_prestamo.min' => 'El monto del préstamo debe ser de al menos $100.00 MXN.',
            'monto_prestamo.max' => 'El monto del préstamo no puede exceder $1,000,000.00 MXN.',

            'costo_seguro.required' => 'El costo del seguro es obligatorio (ingresa 0 si no aplica).',
            'costo_seguro.numeric' => 'El costo del seguro debe ser un número válido.',
            'costo_seguro.min' => 'El costo del seguro no puede ser un valor negativo.',
            'costo_seguro.max' => 'El costo del seguro no puede exceder $100,000.00 MXN.',

            'comision_apertura.required' => 'El porcentaje de comisión de apertura es obligatorio (ingresa 0 si no aplica).',
            'comision_apertura.numeric' => 'La comisión de apertura debe ser un número válido.',
            'comision_apertura.min' => 'La comisión de apertura no puede ser menor a 0%.',
            'comision_apertura.max' => 'La comisión de apertura no puede ser mayor a 100%.',

            'plazo_quincenas.required' => 'El plazo en quincenas es obligatorio.',
            'plazo_quincenas.integer' => 'El plazo debe ser un número entero de quincenas.',
            'plazo_quincenas.min' => 'El plazo mínimo permitido es de 1 quincena.',
            'plazo_quincenas.max' => 'El plazo máximo permitido es de 120 quincenas.',

            'tasa_interes_quincenal.required' => 'La tasa de interés quincenal es obligatoria.',
            'tasa_interes_quincenal.numeric' => 'La tasa de interés debe ser un número válido.',
            'tasa_interes_quincenal.min' => 'La tasa de interés no puede ser menor a 0%.',
            'tasa_interes_quincenal.max' => 'La tasa de interés no puede superar el 100%.',

            'descripcion.string' => 'La descripción debe ser un texto.',
            'descripcion.max' => 'La descripción no puede exceder los 1,000 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'clave' => $this->filled('clave') ? strtoupper(trim($this->clave)) : $this->clave,
            'nombre' => $this->filled('nombre') ? trim($this->nombre) : $this->nombre,
            'activo' => $this->has('activo') ? true : false,
        ]);
    }
}
