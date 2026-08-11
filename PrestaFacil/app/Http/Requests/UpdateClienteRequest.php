<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clienteId = $this->route('cliente')?->id ?? $this->route('cliente');

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'curp' => [
                'required',
                'string',
                'size:18',
                Rule::unique('clientes', 'curp')->ignore($clienteId),
            ],
            'rfc' => ['nullable', 'string', 'min:12', 'max:13'],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'lugar_nacimiento' => ['required', 'string', 'max:255'],
            'calle' => ['required', 'string', 'max:255'],
            'colonia' => ['required', 'string', 'max:255'],
            'codigo_postal' => ['required', 'string', 'size:5'],
            'ciudad' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', 'max:255'],
            'pdf_ine' => ['nullable', 'file', 'mimes:pdf', 'max:5120'], // Opcional en edición
            'pdf_comprobante' => ['nullable', 'file', 'mimes:pdf', 'max:5120'], // Opcional en edición
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre completo del cliente es obligatorio.',
            'curp.required' => 'La CURP es obligatoria.',
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.unique' => 'Esta CURP ya la pertenece a otro cliente.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'lugar_nacimiento.required' => 'El lugar de nacimiento es obligatorio.',
            'calle.required' => 'La calle y número de domicilio son obligatorios.',
            'colonia.required' => 'La colonia es obligatoria.',
            'codigo_postal.required' => 'El código postal es obligatorio.',
            'codigo_postal.size' => 'El código postal debe tener 5 dígitos.',
            'ciudad.required' => 'La ciudad es obligatoria.',
            'estado.required' => 'El estado es obligatorio.',
            'pdf_ine.mimes' => 'El archivo del INE debe ser en formato PDF.',
            'pdf_ine.max' => 'El archivo del INE no debe superar los 5 MB.',
            'pdf_comprobante.mimes' => 'El comprobante de domicilio debe ser en formato PDF.',
            'pdf_comprobante.max' => 'El comprobante de domicilio no debe superar los 5 MB.',
        ];
    }
}
