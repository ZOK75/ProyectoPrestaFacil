<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'curp' => ['required', 'string', 'size:18', 'unique:clientes,curp'],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13'],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'lugar_nacimiento' => ['required', 'string', 'max:255'],
            'calle' => ['required', 'string', 'max:255'],
            'colonia' => ['required', 'string', 'max:255'],
            'codigo_postal' => ['required', 'string', 'size:5'],
            'ciudad' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', 'max:255'],
            'pdf_ine' => ['required', 'file', 'mimes:pdf', 'max:5120'], // Máximo 5MB PDF
            'pdf_comprobante' => ['required', 'file', 'mimes:pdf', 'max:5120'], // Máximo 5MB PDF
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre completo del cliente es obligatorio.',
            'curp.required' => 'La CURP es obligatoria.',
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.unique' => 'Esta CURP ya se encuentra registrada.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'lugar_nacimiento.required' => 'El lugar de nacimiento es obligatorio.',
            'calle.required' => 'La calle y número de domicilio son obligatorios.',
            'colonia.required' => 'La colonia es obligatoria.',
            'codigo_postal.required' => 'El código postal es obligatorio.',
            'codigo_postal.size' => 'El código postal debe tener 5 dígitos.',
            'ciudad.required' => 'La ciudad es obligatoria.',
            'estado.required' => 'El estado es obligatorio.',
            'pdf_ine.required' => 'Debes adjuntar la identificación oficial INE en formato PDF.',
            'pdf_ine.mimes' => 'El archivo del INE debe ser en formato PDF.',
            'pdf_ine.max' => 'El archivo del INE no debe superar los 5 MB.',
            'pdf_comprobante.required' => 'Debes adjuntar el comprobante de domicilio en formato PDF.',
            'pdf_comprobante.mimes' => 'El comprobante de domicilio debe ser en formato PDF.',
            'pdf_comprobante.max' => 'El comprobante de domicilio no debe superar los 5 MB.',
        ];
    }
}
